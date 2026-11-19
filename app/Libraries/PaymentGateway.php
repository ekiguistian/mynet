<?php

namespace App\Libraries;

use Config\Services;
use RuntimeException;

class PaymentGateway
{
    public function create(string $provider, array $payment, array $invoice, array $customer): array
    {
        return match ($provider) {
            'tripay' => $this->tripay($payment, $invoice, $customer),
            'midtrans' => $this->midtrans($payment, $invoice, $customer),
            'xendit' => $this->xendit($payment, $invoice, $customer),
            default => throw new RuntimeException('Pilih payment gateway yang aktif pada menu Integrasi API.'),
        };
    }

    private function tripay(array $payment, array $invoice, array $customer): array
    {
        $config = (new IntegrationConfig())->get('payment');
        $apiKey = (string) ($config['tripay_api_key'] ?? '');
        $privateKey = (string) ($config['tripay_private_key'] ?? '');
        $merchantCode = (string) ($config['tripay_merchant_code'] ?? '');
        if ($apiKey === '' || $privateKey === '' || $merchantCode === '') throw new RuntimeException('Kredensial Tripay belum lengkap.');
        $amount = (int) round((float) $payment['amount']);
        $merchantRef = $payment['external_reference'];
        $payload = [
            'method' => $config['tripay_method'] ?? 'BRIVA',
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $customer['full_name'],
            'customer_email' => $customer['email'] ?: 'billing@mynet.local',
            'customer_phone' => $customer['phone'] ?: '',
            'order_items' => [[
                'sku' => $invoice['invoice_no'], 'name' => 'Tagihan internet ' . $invoice['period'],
                'price' => $amount, 'quantity' => 1,
            ]],
            'return_url' => site_url('/area/invoices'),
            'expired_time' => time() + 86400,
            'signature' => hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey),
        ];
        $sandbox = (string) ($config['tripay_mode'] ?? 'sandbox') !== 'production';
        $url = $sandbox ? 'https://tripay.co.id/api-sandbox/transaction/create' : 'https://tripay.co.id/api/transaction/create';
        $response = $this->request($url, [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ], 'POST', http_build_query($payload));
        $data = $response['data'] ?? [];
        if (empty($response['success']) || empty($data['checkout_url'])) throw new RuntimeException($response['message'] ?? 'Tripay tidak mengembalikan tautan pembayaran.');
        return ['reference' => $data['reference'] ?? $merchantRef, 'checkout_url' => $data['checkout_url'], 'payload' => $response];
    }

    private function midtrans(array $payment, array $invoice, array $customer): array
    {
        $config = (new IntegrationConfig())->get('payment');
        $serverKey = (string) ($config['midtrans_server_key'] ?? '');
        if ($serverKey === '') throw new RuntimeException('MIDTRANS_SERVER_KEY belum diatur.');
        $sandbox = (string) ($config['midtrans_mode'] ?? 'sandbox') !== 'production';
        $url = $sandbox ? 'https://app.sandbox.midtrans.com/snap/v1/transactions' : 'https://app.midtrans.com/snap/v1/transactions';
        $payload = [
            'transaction_details' => [
                'order_id' => $payment['external_reference'],
                'gross_amount' => (int) round((float) $payment['amount']),
            ],
            'customer_details' => [
                'first_name' => $customer['full_name'], 'email' => $customer['email'] ?: 'billing@mynet.local',
                'phone' => $customer['phone'] ?: '',
            ],
            'item_details' => [[
                'id' => $invoice['invoice_no'], 'price' => (int) round((float) $payment['amount']), 'quantity' => 1,
                'name' => 'Tagihan internet ' . $invoice['period'],
            ]],
        ];
        $response = $this->request($url, [
            'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
            'Content-Type' => 'application/json', 'Accept' => 'application/json',
        ], 'POST', json_encode($payload, JSON_THROW_ON_ERROR));
        if (empty($response['redirect_url'])) throw new RuntimeException($response['error_messages'][0] ?? 'Midtrans tidak mengembalikan tautan pembayaran.');
        return ['reference' => $payment['external_reference'], 'checkout_url' => $response['redirect_url'], 'payload' => $response];
    }

    private function xendit(array $payment, array $invoice, array $customer): array
    {
        $config = (new IntegrationConfig())->get('payment');
        $secretKey = (string) ($config['xendit_secret_key'] ?? '');
        if ($secretKey === '') throw new RuntimeException('XENDIT_SECRET_KEY belum diatur.');
        $payload = [
            'external_id' => $payment['external_reference'],
            'amount' => (int) round((float) $payment['amount']),
            'description' => 'Tagihan ' . $invoice['invoice_no'] . ' periode ' . $invoice['period'],
            'payer_email' => $customer['email'] ?: 'billing@mynet.local',
            'customer' => [
                'given_names' => $customer['full_name'], 'email' => $customer['email'] ?: 'billing@mynet.local',
                'mobile_number' => $customer['phone'] ?: null,
            ],
            'currency' => 'IDR', 'invoice_duration' => 86400,
            'success_redirect_url' => site_url('/area/invoices'),
            'failure_redirect_url' => site_url('/area/invoices'),
        ];
        $response = $this->request('https://api.xendit.co/v2/invoices', [
            'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
            'Content-Type' => 'application/json', 'Accept' => 'application/json',
        ], 'POST', json_encode($payload, JSON_THROW_ON_ERROR));
        if (empty($response['invoice_url'])) throw new RuntimeException($response['message'] ?? 'Xendit tidak mengembalikan tautan pembayaran.');
        return ['reference' => $payment['external_reference'], 'checkout_url' => $response['invoice_url'], 'payload' => $response];
    }

    private function request(string $url, array $headers, string $method, string $body): array
    {
        try {
            $client = Services::curlrequest(['timeout' => 25, 'http_errors' => false, 'verify' => true]);
            $response = $client->request($method, $url, ['headers' => $headers, 'body' => $body]);
            $result = json_decode((string) $response->getBody(), true);
            if (! is_array($result)) throw new RuntimeException('Gateway mengembalikan respons yang tidak terbaca.');
            return $result;
        } catch (\Throwable $e) {
            throw new RuntimeException('Koneksi payment gateway gagal: ' . $e->getMessage(), 0, $e);
        }
    }
}
