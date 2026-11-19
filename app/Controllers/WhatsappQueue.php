<?php

namespace App\Controllers;

use App\Libraries\WhatsappQueueService;

class WhatsappQueue extends BaseController
{
    private array $roles = ['superadmin', 'administrator', 'admin', 'keuangan', 'noc'];

    public function index()
    {
        if (! in_array(mynet_user('role'), $this->roles, true)) return $this->response->setStatusCode(403)->setBody(view('errors/html/error_403'));
        $query = db_connect()->table('whatsapp_queue')->orderBy('id', 'DESC')->limit(100);
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        $customers = db_connect()->table('customers')->select('id, full_name, customer_code')->where('status', 'active')->orderBy('full_name');
        if (mynet_user('branch_id')) $customers->where('branch_id', mynet_user('branch_id'));
        return view('notifications/queue', [
            'title' => 'Antrean WhatsApp', 'queue' => $query->get()->getResultArray(),
            'customers' => $customers->get()->getResultArray(),
            'templates' => db_connect()->table('message_templates')->where('active', 1)->orderBy('name')->get()->getResultArray(),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function schedule()
    {
        if (! in_array(mynet_user('role'), $this->roles, true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $group = (string) $this->request->getPost('target_group');
        if (! in_array($group, ['teknisi', 'keuangan', 'admin', 'pelanggan'], true)) return redirect()->back()->with('error', 'Pilih grup penerima yang valid.');
        $templateId = (int) $this->request->getPost('template_id') ?: null;
        $message = trim((string) $this->request->getPost('message'));
        if ($templateId) {
            $template = db_connect()->table('message_templates')->where('id', $templateId)->where('active', 1)->get()->getRowArray();
            if ($template) $message = $template['body'];
        }
        if ($message === '' || mb_strlen($message) > 4000) return redirect()->back()->with('error', 'Isi pesan wajib diisi dan maksimal 4.000 karakter.');
        $scheduledAt = str_replace('T', ' ', (string) $this->request->getPost('scheduled_at'));
        if (strlen($scheduledAt) === 16) $scheduledAt .= ':00';
        $time = strtotime($scheduledAt);
        if (! $time || $time < time() - 60) return redirect()->back()->withInput()->with('error', 'Jadwal harus valid dan tidak berada di waktu lampau.');
        $scheduledAt = date('Y-m-d H:i:s', $time);
        $customerId = null;
        if ($group === 'pelanggan') {
            $customerId = (int) $this->request->getPost('customer_id');
            $customer = db_connect()->table('customers')->where('id', $customerId)->where('status', 'active')->get()->getRowArray();
            if (! $customer || ! $this->inBranch($customer['branch_id'])) return redirect()->back()->with('error', 'Pelanggan tidak ditemukan di cabang Anda.');
        }
        (new WhatsappQueueService())->enqueue(
            $group, $customerId ? (int) $customer['branch_id'] : (mynet_user('branch_id') ? (int) mynet_user('branch_id') : null),
            $message, $scheduledAt, (int) mynet_user('id'), $customerId, null, $templateId
        );
        return redirect()->to('/notifications/queue')->with('success', 'Pesan dijadwalkan dan masuk ke antrean WhatsApp. Pastikan cron worker diaktifkan.');
    }

    public function retry(int $id)
    {
        if (! in_array(mynet_user('role'), $this->roles, true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $query = db_connect()->table('whatsapp_queue')->where('id', $id)->where('status', 'failed');
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        $query->update(['status' => 'pending', 'scheduled_at' => date('Y-m-d H:i:s'), 'last_error' => null]);
        return redirect()->to('/notifications/queue')->with('success', 'Pesan gagal dijadwalkan ulang untuk dikirim sekarang.');
    }

    private function inBranch($branchId): bool
    {
        return empty(mynet_user('branch_id')) || (int) mynet_user('branch_id') === (int) $branchId;
    }
}
