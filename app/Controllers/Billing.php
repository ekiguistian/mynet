<?php

namespace App\Controllers;

use App\Libraries\PaymentGateway;
use Throwable;

class Billing extends BaseController
{
    public function generate()
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'keuangan'], true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $db = db_connect();
        $period = (string) ($this->request->getPost('period') ?: date('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) return redirect()->back()->with('error', 'Format periode tidak valid.');
        $customers = $db->table('customers c')->select('c.*, p.monthly_price')
            ->join('plans p', 'p.id = c.plan_id', 'left')->where('c.status', 'active');
        if (! empty(mynet_user('branch_id'))) $customers->where('c.branch_id', mynet_user('branch_id'));
        $list = $customers->get()->getResultArray();
        $due = (new \DateTimeImmutable($period . '-01'))->modify('first day of next month')->setDate(
            (int) (new \DateTimeImmutable($period . '-01'))->modify('first day of next month')->format('Y'),
            (int) (new \DateTimeImmutable($period . '-01'))->modify('first day of next month')->format('m'), 10
        )->format('Y-m-d');
        $created = 0;
        $db->transStart();
        foreach ($list as $customer) {
            if ($db->table('invoices')->where('customer_id', $customer['id'])->where('period', $period)->countAllResults() > 0) continue;
            $amount = (float) ($customer['monthly_price'] ?? 0);
            if ($amount <= 0) continue;
            $invoiceNo = 'INV-' . str_replace('-', '', $period) . '-' . $customer['customer_code'];
            $db->table('invoices')->insert([
                'branch_id' => $customer['branch_id'], 'customer_id' => $customer['id'], 'invoice_no' => $invoiceNo,
                'period' => $period, 'amount' => $amount, 'discount' => 0, 'total' => $amount,
                'status' => 'unpaid', 'due_date' => $due, 'created_by' => mynet_user('id'), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }
        $db->transComplete();
        return redirect()->to('/area/invoices')->with('success', $created . ' tagihan dibuat untuk periode ' . $period . '. Tagihan yang sudah ada dilewati.');
    }

    public function pay(int $invoiceId)
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'keuangan', 'pelanggan'], true)) {
            return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        }
        $db = db_connect();
        $invoice = $db->table('invoices')->where('id', $invoiceId)->get()->getRowArray();
        $user = session('user');
        if (! $invoice || (! empty($user['branch_id']) && (int) $invoice['branch_id'] !== (int) $user['branch_id'])
            || ($user['role'] === 'pelanggan' && (int) $invoice['customer_id'] !== (int) ($user['customer_id'] ?? 0))) {
            return $this->response->setStatusCode(404)->setBody('Tagihan tidak ditemukan.');
        }
        if ($invoice['status'] === 'paid') return redirect()->back()->with('success', 'Tagihan ini sudah lunas.');
        $pending = $db->table('payments')->where('invoice_id', $invoiceId)->where('provider', strtolower((string) env('PAYMENT_PROVIDER')))
            ->where('status', 'pending')->where('checkout_url !=', '')->orderBy('id', 'DESC')->get()->getRowArray();
        if ($pending && $pending['checkout_url']) return redirect()->to($pending['checkout_url']);
        $customer = $db->table('customers')->where('id', $invoice['customer_id'])->get()->getRowArray();
        $paid = (float) $db->table('payments')->selectSum('amount')->where('invoice_id', $invoiceId)->where('status', 'paid')->get()->getRow()->amount;
        $amount = (float) $invoice['total'] - $paid;
        if ($amount <= 0) return redirect()->back()->with('error', 'Tidak ada sisa tagihan untuk dibayar.');
        $provider = strtolower((string) env('PAYMENT_PROVIDER'));
        $reference = 'MN-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $db->table('payments')->insert([
            'branch_id' => $invoice['branch_id'], 'invoice_id' => $invoiceId, 'customer_id' => $invoice['customer_id'],
            'provider' => $provider, 'external_reference' => $reference, 'amount' => $amount, 'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $paymentId = (int) $db->insertID();
        try {
            $result = (new PaymentGateway())->create($provider, ['amount' => $amount, 'external_reference' => $reference], $invoice, $customer);
            $db->table('payments')->where('id', $paymentId)->update([
                'checkout_url' => $result['checkout_url'],
                'raw_payload' => json_encode($result['payload'], JSON_UNESCAPED_SLASHES), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->to($result['checkout_url']);
        } catch (Throwable $e) {
            $db->table('payments')->where('id', $paymentId)->update(['status' => 'failed', 'updated_at' => date('Y-m-d H:i:s')]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
