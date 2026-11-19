<?php

namespace App\Controllers;

class PaymentWebhook extends BaseController
{
    public function handle(string $provider)
    {
        $provider = strtolower($provider);
        $raw = (string) $this->request->getBody();
        $payload = json_decode($raw, true);
        if (! is_array($payload)) return $this->json(['success' => false, 'message' => 'Invalid JSON'], 400);

        $verified = match ($provider) {
            'tripay' => $this->verifyTripay($raw),
            'midtrans' => $this->verifyMidtrans($payload),
            'xendit' => $this->verifyXendit(),
            default => false,
        };
        if (! $verified) return $this->json(['success' => false, 'message' => 'Invalid signature'], 401);

        [$reference, $newStatus, $eventKey] = match ($provider) {
            'tripay' => [
                (string) ($payload['merchant_ref'] ?? ''),
                strtoupper((string) ($payload['status'] ?? '')) === 'PAID' ? 'paid' : (strtoupper((string) ($payload['status'] ?? '')) === 'EXPIRED' ? 'expired' : 'pending'),
                (string) ($payload['reference'] ?? $payload['merchant_ref'] ?? '') . ':' . (string) ($payload['status'] ?? ''),
            ],
            'midtrans' => [
                (string) ($payload['order_id'] ?? ''),
                $this->midtransStatus($payload),
                (string) ($payload['transaction_id'] ?? $payload['order_id'] ?? '') . ':' . (string) ($payload['transaction_status'] ?? ''),
            ],
            'xendit' => [
                (string) ($payload['external_id'] ?? ''),
                strtoupper((string) ($payload['status'] ?? '')) === 'PAID' ? 'paid' : (strtoupper((string) ($payload['status'] ?? '')) === 'EXPIRED' ? 'expired' : 'pending'),
                (string) ($payload['id'] ?? $payload['external_id'] ?? '') . ':' . (string) ($payload['status'] ?? ''),
            ],
        };
        if ($reference === '') return $this->json(['success' => false, 'message' => 'Missing payment reference'], 400);

        $db = db_connect();
        if ($db->table('webhook_events')->where('provider', $provider)->where('event_key', $eventKey)->countAllResults() > 0) {
            return $this->json(['success' => true, 'message' => 'Already processed']);
        }
        $payment = $db->table('payments')->where('provider', $provider)->where('external_reference', $reference)->get()->getRowArray();
        if (! $payment) return $this->json(['success' => false, 'message' => 'Payment not found'], 404);
        if ($payment['status'] === 'paid' && $newStatus !== 'paid') return $this->json(['success' => true, 'message' => 'Payment already settled']);

        $reportedAmount = $this->reportedAmount($provider, $payload);
        if ($reportedAmount !== null && abs($reportedAmount - (float) $payment['amount']) > 0.01) {
            return $this->json(['success' => false, 'message' => 'Amount mismatch'], 400);
        }

        $db->transBegin();
        $payment = $db->query('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$payment['id']])->getRowArray();
        if ($payment['status'] === 'paid' && $newStatus !== 'paid') {
            $db->transRollback();
            return $this->json(['success' => true, 'message' => 'Payment already settled']);
        }
        $wasPaid = $payment['status'] === 'paid';
        $db->table('payments')->where('id', $payment['id'])->update([
            'status' => $newStatus,
            'payment_method' => $payload['payment_method'] ?? $payload['payment_method_code'] ?? $payload['payment_type'] ?? $payment['payment_method'],
            'paid_at' => $newStatus === 'paid' ? date('Y-m-d H:i:s') : null,
            'raw_payload' => $raw,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($newStatus === 'paid') {
            $invoice = $db->query('SELECT * FROM invoices WHERE id = ? FOR UPDATE', [$payment['invoice_id']])->getRowArray();
            $paidTotal = (float) $db->table('payments')->selectSum('amount')->where('invoice_id', $payment['invoice_id'])
                ->whereIn('status', ['paid'])->get()->getRow()->amount;
            if ($paidTotal >= (float) $invoice['total']) {
                $db->table('invoices')->where('id', $invoice['id'])->update(['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
            } else {
                $db->table('invoices')->where('id', $invoice['id'])->update(['status' => 'partial', 'paid_at' => null]);
            }
            if (! $wasPaid) {
                $db->table('finance_entries')->insert([
                    'branch_id' => $payment['branch_id'], 'entry_type' => 'income', 'category' => 'Pembayaran gateway',
                    'amount' => $payment['amount'], 'description' => 'Pembayaran tagihan ' . $invoice['invoice_no'],
                    'reference' => $reference, 'transaction_date' => date('Y-m-d'), 'user_id' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        $db->table('webhook_events')->insert([
            'provider' => $provider, 'event_key' => $eventKey, 'payload_hash' => hash('sha256', $raw),
            'processed_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transCommit();

        return $this->json(['success' => true]);
    }

    private function verifyTripay(string $raw): bool
    {
        $privateKey = (string) env('TRIPAY_PRIVATE_KEY');
        $signature = $this->request->getHeaderLine('X-Callback-Signature');
        return $privateKey !== '' && $signature !== '' && hash_equals(hash_hmac('sha256', $raw, $privateKey), $signature)
            && $this->request->getHeaderLine('X-Callback-Event') === 'payment_status';
    }

    private function verifyMidtrans(array $payload): bool
    {
        $serverKey = (string) env('MIDTRANS_SERVER_KEY');
        $signature = (string) ($payload['signature_key'] ?? '');
        if ($serverKey === '' || $signature === '') return false;
        $expected = hash('sha512', (string) ($payload['order_id'] ?? '') . (string) ($payload['status_code'] ?? '')
            . (string) ($payload['gross_amount'] ?? '') . $serverKey);
        return hash_equals($expected, $signature);
    }

    private function verifyXendit(): bool
    {
        $token = (string) env('XENDIT_WEBHOOK_TOKEN');
        return $token !== '' && hash_equals($token, $this->request->getHeaderLine('x-callback-token'));
    }

    private function midtransStatus(array $payload): string
    {
        $status = (string) ($payload['transaction_status'] ?? '');
        if ($status === 'settlement') return 'paid';
        if ($status === 'capture' && (($payload['fraud_status'] ?? '') === 'accept')) return 'paid';
        if (in_array($status, ['expire'], true)) return 'expired';
        if (in_array($status, ['deny', 'cancel', 'failure'], true)) return 'failed';
        return 'pending';
    }

    private function reportedAmount(string $provider, array $payload): ?float
    {
        $value = match ($provider) {
            'tripay' => $payload['total_amount'] ?? null,
            'midtrans' => $payload['gross_amount'] ?? null,
            'xendit' => $payload['paid_amount'] ?? ($payload['amount'] ?? null),
            default => null,
        };
        return $value === null ? null : (float) $value;
    }

    private function json(array $body, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setJSON($body);
    }
}
