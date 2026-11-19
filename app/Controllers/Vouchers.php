<?php

namespace App\Controllers;

use App\Libraries\RouterOs;
use Throwable;

class Vouchers extends BaseController
{
    public function generate()
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'noc'], true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $count = (int) $this->request->getPost('count');
        $profile = trim((string) $this->request->getPost('profile'));
        $routerId = (int) $this->request->getPost('router_id');
        if ($count < 1 || $count > 100 || $profile === '') return redirect()->back()->with('error', 'Pilih profil hotspot dan jumlah 1–100 voucher.');

        $db = db_connect();
        $user = session('user');
        $branchId = (int) ($user['branch_id'] ?: ($db->table('branches')->where('status', 'active')->orderBy('id')->get()->getRow('id') ?? 1));
        $router = null;
        if ($routerId) {
            $query = $db->table('routers')->where('id', $routerId)->where('active', 1);
            if (! empty($user['branch_id'])) $query->where('branch_id', $user['branch_id']);
            $router = $query->get()->getRowArray();
            if (! $router) return redirect()->back()->with('error', 'Router tidak ditemukan atau bukan milik cabang ini.');
            $branchId = (int) $router['branch_id'];
        }

        $created = 0;
        $failed = 0;
        for ($i = 0; $i < $count; $i++) {
            $username = 'HS' . strtoupper(bin2hex(random_bytes(4)));
            $password = strtoupper(bin2hex(random_bytes(3)));
            $status = $router ? 'syncing' : 'ready';
            $db->table('hotspot_vouchers')->insert([
                'branch_id' => $branchId, 'router_id' => $router['id'] ?? null, 'profile' => $profile,
                'username' => $username, 'password' => $password, 'status' => $status,
                'generated_by' => $user['id'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $voucherId = (int) $db->insertID();
            try {
                if ($router) (new RouterOs())->createHotspotUser($router, $username, $password, $profile);
                $db->table('hotspot_vouchers')->where('id', $voucherId)->update(['status' => 'ready']);
                $created++;
            } catch (Throwable $e) {
                $db->table('hotspot_vouchers')->where('id', $voucherId)->update(['status' => 'sync_failed']);
                $failed++;
                log_message('error', 'Mynet voucher sync failed for router {router}: {error}', ['router' => $router['name'] ?? 'local', 'error' => $e->getMessage()]);
            }
        }
        $message = $created . ' voucher berhasil dibuat';
        if ($failed) $message .= ', ' . $failed . ' gagal disinkronkan ke router';
        return redirect()->to('/area/vouchers')->with($failed ? 'warning' : 'success', $message . '.');
    }
}
