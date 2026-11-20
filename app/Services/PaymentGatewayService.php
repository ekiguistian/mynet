<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentGatewayService
{
    public function createCheckout(Payment $payment): string
    {
        if ($payment->status !== 'pending') {
            throw new RuntimeException('Tautan pembayaran hanya dapat dibuat untuk pembayaran yang menunggu.');
        }

        if (filled($payment->checkout_url)) {
            return $payment->checkout_url;
        }

        $config = $this->gatewayConfig();
        $provider = strtolower($payment->provider);

        if ($provider !== strtolower($config['provider'] ?? '')) {
            throw new RuntimeException('Pilih gateway yang sama dengan gateway aktif pada pengaturan integrasi.');
        }

        $reference = 'MYN-P'.$payment->getKey();
        $invoice = $payment->invoice()->with('customer')->firstOrFail();
        $customer = $invoice->customer;
        $response = match ($provider) {
            'midtrans' => $this->createMidtransCheckout($payment, $invoice, $customer, $config, $reference),
            'xendit' => $this->createXenditCheckout($payment, $invoice, $customer, $config, $reference),
            'tripay' => $this->createTripayCheckout($payment, $invoice, $customer, $config, $reference),
            default => throw new RuntimeException('Provider pembayaran belum didukung untuk pembuatan checkout.'),
        };

        $payment->forceFill([
            'external_reference' => $response['external_reference'] ?? $reference,
            'checkout_url' => $response['checkout_url'],
            'raw_payload' => $response['payload'] ?? null,
        ])->save();

        return $payment->checkout_url;
    }

    /** @return array{duplicate: bool, payment: Payment} */
    public function processWebhook(string $provider, Request $request): array
    {
        $provider = strtolower($provider);
        $config = $this->gatewayConfig();

        if ($provider !== strtolower($config['provider'] ?? '')) {
            throw new HttpException(404, 'Payment provider is not active.');
        }

        $body = $request->getContent();
        $payload = json_decode($body, true);

        if (! is_array($payload)) {
            throw new HttpException(400, 'Invalid webhook payload.');
        }

        $this->assertWebhookSignature($provider, $request, $payload, $body, $config);
        [$reference, $status, $eventKey, $amount] = $this->webhookDetails($provider, $payload);

        if (blank($reference) || blank($eventKey)) {
            throw new HttpException(400, 'Webhook reference is missing.');
        }

        return DB::transaction(function () use ($provider, $payload, $reference, $status, $eventKey, $amount, $body): array {
            $event = WebhookEvent::query()->firstOrCreate(
                ['provider' => $provider, 'event_key' => Str::limit($eventKey, 160, '')],
                ['payload_hash' => hash('sha256', $body)],
            );

            if ($event->processed_at !== null) {
                $payment = Payment::query()->where('external_reference', $reference)->first();

                return ['duplicate' => true, 'payment' => $payment];
            }

            $payment = Payment::query()->where('provider', $provider)
                ->where('external_reference', $reference)
                ->lockForUpdate()->first();

            if (! $payment && $provider !== 'tripay') {
                $payment = Payment::query()->where('provider', $provider)
                    ->whereHas('invoice', fn ($query) => $query->where('invoice_no', $reference))
                    ->where('status', 'pending')->latest()->lockForUpdate()->first();
            }

            if (! $payment && $provider === 'tripay') {
                $payment = Payment::query()->where('provider', $provider)
                    ->whereHas('invoice', fn ($query) => $query->where('invoice_no', $reference))
                    ->where('status', 'pending')->latest()->lockForUpdate()->first();
            }

            if (! $payment) {
                throw new NotFoundHttpException('Payment not found for webhook reference.');
            }

            if ($amount !== null && $this->amountInMinorUnits($amount) < $this->amountInMinorUnits($payment->amount)) {
                throw new HttpException(422, 'Webhook amount is lower than the pending payment.');
            }

            if ($payment->status !== 'paid' && $status !== null) {
                $payment->forceFill([
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? now() : $payment->paid_at,
                    'payment_method' => data_get($payload, 'payment_type') ?? data_get($payload, 'payment_method') ?? $payment->payment_method,
                    'raw_payload' => $payload,
                ])->save();
            }

            $event->forceFill(['processed_at' => now()])->save();

            return ['duplicate' => false, 'payment' => $payment->fresh()];
        });
    }

    private function createMidtransCheckout(Payment $payment, $invoice, $customer, array $config, string $reference): array
    {
        if (blank($config['server_key'] ?? null)) {
            throw new RuntimeException('Server key Midtrans belum diatur.');
        }

        $baseUrl = ($config['sandbox'] ?? true) ? 'https://app.sandbox.midtrans.com' : 'https://app.midtrans.com';
        $payload = [
            'transaction_details' => ['order_id' => $reference, 'gross_amount' => (int) round((float) $payment->amount)],
            'customer_details' => [
                'first_name' => Str::limit((string) ($customer?->full_name ?: 'Pelanggan mynet'), 50, ''),
                'email' => $customer?->email,
                'phone' => $customer?->phone,
            ],
            'item_details' => [[
                'id' => Str::limit($invoice->invoice_no, 50, ''),
                'price' => (int) round((float) $payment->amount),
                'quantity' => 1,
                'name' => Str::limit('Tagihan internet '.$invoice->period, 50, ''),
            ]],
        ];

        $response = Http::acceptJson()->asJson()->withBasicAuth($config['server_key'], '')
            ->timeout(20)->post($baseUrl.'/snap/v1/transactions', $payload)->throw()->json();
        $checkoutUrl = $response['redirect_url'] ?? null;

        if (blank($checkoutUrl) || ! str_starts_with($checkoutUrl, 'https://app.')) {
            throw new RuntimeException('Midtrans tidak mengembalikan URL checkout yang valid.');
        }

        return ['checkout_url' => $checkoutUrl, 'external_reference' => $reference, 'payload' => $response];
    }

    private function createXenditCheckout(Payment $payment, $invoice, $customer, array $config, string $reference): array
    {
        if (blank($config['secret_key'] ?? null)) {
            throw new RuntimeException('Secret key Xendit belum diatur.');
        }

        $payload = [
            'external_id' => $reference,
            'amount' => (int) round((float) $payment->amount),
            'description' => 'Tagihan internet '.$invoice->invoice_no,
            'payer_email' => $customer?->email,
            'currency' => 'IDR',
            'invoice_duration' => 86400,
            'success_redirect_url' => url('/admin/invoices'),
            'failure_redirect_url' => url('/admin/invoices'),
        ];

        $response = Http::acceptJson()->asJson()->withBasicAuth($config['secret_key'], '')
            ->timeout(20)->post('https://api.xendit.co/v2/invoices', $payload)->throw()->json();
        $checkoutUrl = $response['invoice_url'] ?? null;

        if (blank($checkoutUrl) || ! str_starts_with($checkoutUrl, 'https://')) {
            throw new RuntimeException('Xendit tidak mengembalikan URL invoice yang valid.');
        }

        return ['checkout_url' => $checkoutUrl, 'external_reference' => $reference, 'payload' => $response];
    }

    private function createTripayCheckout(Payment $payment, $invoice, $customer, array $config, string $reference): array
    {
        foreach (['api_key', 'secret_key', 'merchant_code'] as $field) {
            if (blank($config[$field] ?? null)) {
                throw new RuntimeException('API key, private key, dan kode merchant TriPay wajib diatur.');
            }
        }

        if (blank($customer?->email)) {
            throw new RuntimeException('Email pelanggan wajib diisi untuk checkout TriPay.');
        }

        $method = $config['default_method'] ?? 'BRIVA';
        $signature = hash_hmac(
            'sha256',
            $config['merchant_code'].$reference.(int) round((float) $payment->amount),
            $config['secret_key'],
        );
        $payload = [
            'method' => $method,
            'merchant_ref' => $reference,
            'amount' => (int) round((float) $payment->amount),
            'customer_name' => $customer?->full_name ?: 'Pelanggan mynet',
            'customer_email' => $customer?->email,
            'customer_phone' => $customer?->phone,
            'order_items' => [[
                'sku' => $invoice->invoice_no,
                'name' => 'Tagihan internet '.$invoice->period,
                'price' => (int) round((float) $payment->amount),
                'quantity' => 1,
            ]],
            'callback_url' => url('/api/webhooks/payments/tripay'),
            'return_url' => url('/admin/invoices'),
            'signature' => $signature,
        ];
        $endpoint = ($config['sandbox'] ?? true) ? 'https://tripay.co.id/api-sandbox/transaction/create' : 'https://tripay.co.id/api/transaction/create';
        $response = Http::acceptJson()->asForm()->withToken($config['api_key'])
            ->timeout(20)->post($endpoint, $payload)->throw()->json();
        $data = $response['data'] ?? [];
        $checkoutUrl = $data['checkout_url'] ?? null;

        if (blank($checkoutUrl) || ! str_starts_with($checkoutUrl, 'https://')) {
            throw new RuntimeException('TriPay tidak mengembalikan URL checkout yang valid.');
        }

        return ['checkout_url' => $checkoutUrl, 'external_reference' => $reference, 'payload' => $response];
    }

    private function assertWebhookSignature(string $provider, Request $request, array $payload, string $body, array $config): void
    {
        $valid = match ($provider) {
            'midtrans' => $this->validMidtransSignature($payload, $config),
            'xendit' => filled($config['secret_key'] ?? null) && hash_equals((string) $config['secret_key'], (string) $request->header('x-callback-token')),
            'tripay' => filled($config['secret_key'] ?? null) && hash_equals(hash_hmac('sha256', $body, $config['secret_key']), (string) $request->header('x-callback-signature')),
            default => false,
        };

        if (! $valid) {
            throw new HttpException(401, 'Invalid payment webhook signature.');
        }
    }

    private function validMidtransSignature(array $payload, array $config): bool
    {
        if (blank($config['server_key'] ?? null) || blank($payload['order_id'] ?? null) || blank($payload['status_code'] ?? null) || ! isset($payload['gross_amount'], $payload['signature_key'])) {
            return false;
        }

        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$config['server_key']);

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    /** @return array{0: string|null, 1: string|null, 2: string, 3: mixed} */
    private function webhookDetails(string $provider, array $payload): array
    {
        return match ($provider) {
            'midtrans' => [
                $payload['order_id'] ?? null,
                $this->midtransStatus($payload),
                ($payload['transaction_id'] ?? $payload['order_id'] ?? 'event').':'.($payload['transaction_status'] ?? 'unknown'),
                $payload['gross_amount'] ?? null,
            ],
            'xendit' => [
                $payload['external_id'] ?? $payload['reference_id'] ?? null,
                $this->mapStatus((string) ($payload['status'] ?? '')),
                (string) ($payload['id'] ?? hash('sha256', json_encode($payload))),
                $payload['amount'] ?? null,
            ],
            'tripay' => [
                $payload['merchant_ref'] ?? null,
                $this->mapStatus((string) ($payload['status'] ?? '')),
                (string) ($payload['reference'] ?? 'event').':'.($payload['status'] ?? 'unknown'),
                $payload['total_amount'] ?? $payload['amount'] ?? null,
            ],
            default => [null, null, '', null],
        };
    }

    private function midtransStatus(array $payload): ?string
    {
        $status = strtolower((string) ($payload['transaction_status'] ?? ''));

        if ($status === 'capture' && ($payload['fraud_status'] ?? null) !== 'accept') {
            return 'pending';
        }

        return $this->mapStatus($status);
    }

    private function mapStatus(string $status): ?string
    {
        return match (strtolower($status)) {
            'settlement', 'capture', 'paid', 'succeeded', 'success', 'completed' => 'paid',
            'expired', 'expire' => 'expired',
            'deny', 'failed', 'failure', 'cancel', 'canceled', 'cancelled' => 'failed',
            'pending', 'unpaid', 'active' => 'pending',
            default => null,
        };
    }

    private function gatewayConfig(): array
    {
        $config = IntegrationSetting::query()->where('section', 'payment_gateway')->first()?->encrypted_value;

        return is_array($config) ? $config : [];
    }

    private function amountInMinorUnits(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
