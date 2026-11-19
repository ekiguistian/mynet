<?php

namespace App\Controllers;

class Attendance extends BaseController
{
    public function toggle()
    {
        $user = session('user');
        if ($user['role'] === 'pelanggan') return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $db = db_connect();
        $today = date('Y-m-d');
        $row = $db->table('attendances')->where('user_id', $user['id'])->where('work_date', $today)->get()->getRowArray();
        if (! $row) {
            $db->table('attendances')->insert([
                'branch_id' => $user['branch_id'] ?: (int) (db_connect()->table('branches')->orderBy('id')->get()->getRow('id') ?? 1), 'user_id' => $user['id'], 'work_date' => $today,
                'check_in' => date('Y-m-d H:i:s'), 'status' => 'present', 'created_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->to('/dashboard')->with('success', 'Jam masuk tercatat. Semoga aktivitas lancar.');
        }
        if ($row['check_out']) return redirect()->to('/dashboard')->with('success', 'Absensi hari ini sudah lengkap.');
        $db->table('attendances')->where('id', $row['id'])->update(['check_out' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/dashboard')->with('success', 'Jam pulang tercatat.');
    }
}
