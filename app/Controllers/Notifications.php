<?php

namespace App\Controllers;

use App\Libraries\WhatsappGateway;
use Throwable;

class Notifications extends BaseController
{
    public function bill(int $id)
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'keuangan'], true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $db = db_connect();
        $row = $db->table('invoices i')->select('i.*, c.full_name, c.phone')
            ->join('customers c', 'c.id = i.customer_id')->where('i.id', $id)->get()->getRowArray();
        if (! $row || (mynet_user('branch_id') && (int) $row['branch_id'] !== (int) mynet_user('branch_id'))) return redirect()->back()->with('error', 'Tagihan tidak ditemukan.');
        if (empty($row['phone'])) return redirect()->back()->with('error', 'Nomor WhatsApp pelanggan belum diisi.');
        $message = 'Halo ' . $row['full_name'] . ', tagihan internet ' . $row['invoice_no'] . ' periode ' . $row['period']
            . ' sebesar ' . mynet_money($row['total']) . ' jatuh tempo ' . $row['due_date'] . '. Mohon lakukan pembayaran. Terima kasih.';
        try {
            $result = (new WhatsappGateway())->send($row['phone'], $message, $row['invoice_no']);
            $db->table('message_logs')->insert([
                'customer_id' => $row['customer_id'], 'provider' => $result['provider'], 'recipient' => $row['phone'],
                'message' => $message, 'status' => 'queued', 'provider_reference' => $result['reference'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->back()->with('success', 'Pengingat tagihan masuk antrean WhatsApp.');
        } catch (Throwable $e) {
            $db->table('message_logs')->insert([
                'customer_id' => $row['customer_id'], 'provider' => (string) env('WA_PROVIDER'), 'recipient' => $row['phone'],
                'message' => $message, 'status' => 'failed', 'created_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
