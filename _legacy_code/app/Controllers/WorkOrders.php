<?php

namespace App\Controllers;

class WorkOrders extends BaseController
{
    public function create()
    {
        return redirect()->to('/area/work-orders')->with('error', 'Gunakan formulir tugas di halaman Tugas teknisi.');
    }

    public function changeStatus(int $id)
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'noc', 'teknisi'], true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $db = db_connect();
        $work = $db->table('work_orders')->where('id', $id)->get()->getRowArray();
        if (! $work || (! empty(mynet_user('branch_id')) && (int) $work['branch_id'] !== (int) mynet_user('branch_id'))) return redirect()->back()->with('error', 'Tugas tidak ditemukan.');
        if (mynet_user('role') === 'teknisi' && (int) $work['technician_id'] !== (int) mynet_user('id')) return $this->response->setStatusCode(403)->setBody('Tugas ini bukan penugasan Anda.');
        if ($work['status'] === 'completed') return redirect()->back()->with('error', 'Tugas yang sudah selesai tidak dapat diubah kembali.');
        $next = (string) $this->request->getPost('status');
        if (! in_array($next, ['open', 'assigned', 'in_progress', 'completed', 'cancelled'], true)) return redirect()->back()->with('error', 'Status tugas tidak valid.');

        $db->transStart();
        $db->table('work_orders')->where('id', $id)->update([
            'status' => $next,
            'technician_id' => $work['technician_id'] ?: (mynet_user('role') === 'teknisi' ? mynet_user('id') : null),
            'completed_at' => $next === 'completed' ? date('Y-m-d H:i:s') : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($next === 'completed') $this->createEarnings($work);
        $db->transComplete();
        return redirect()->to('/area/work-orders')->with('success', 'Status pekerjaan diperbarui. Bonus dan sales fee dihitung dari aturan cabang.');
    }

    public function payEarning(int $id)
    {
        return redirect()->to('/area/earnings')->with('error', 'Pilih tombol bayar pada baris bonus yang akan dilunasi.');
    }

    private function createEarnings(array $work): void
    {
        $db = db_connect();
        $period = date('Y-m');
        foreach ([['technician_id', 'teknisi'], ['sales_user_id', 'sales']] as [$column, $type]) {
            if (empty($work[$column])) continue;
            $rule = $db->table('earning_rules')->where('role', $type)->where('active', 1)
                ->groupStart()->where('branch_id', $work['branch_id'])->orWhere('branch_id', null)->groupEnd()
                ->orderBy('branch_id', 'DESC')->get()->getRowArray();
            if (! $rule) continue;
            $amount = $rule['calculation_type'] === 'percent'
                ? (float) $work['billable_amount'] * (float) $rule['value'] / 100
                : (float) $rule['value'];
            if ($amount <= 0) continue;
            $earningType = $type === 'teknisi' ? 'teknisi_bonus' : 'sales_fee';
            $alreadyCreated = $db->table('staff_earnings')->where('user_id', $work[$column])
                ->where('work_order_id', $work['id'])->where('earning_type', $earningType)->countAllResults();
            if ($alreadyCreated) continue;
            $db->table('staff_earnings')->insert([
                'branch_id' => $work['branch_id'], 'user_id' => $work[$column], 'work_order_id' => $work['id'],
                'earning_type' => $earningType, 'period' => $period,
                'amount' => $amount, 'status' => 'unpaid', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
