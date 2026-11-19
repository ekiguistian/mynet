<?php

namespace App\Controllers;

use App\Libraries\RouterOs;
use Throwable;

class Vouchers extends BaseController
{
    public function profiles(int $routerId)
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'noc'], true)) return $this->response->setStatusCode(403)->setJSON(['error' => 'Tidak memiliki akses.']);
        $router = db_connect()->table('routers')->where('id', $routerId)->where('active', 1)->get()->getRowArray();
        if (! $router || ! $this->inBranch($router['branch_id'])) return $this->response->setStatusCode(404)->setJSON(['error' => 'Router tidak ditemukan pada cabang Anda.']);
        try {
            return $this->response->setJSON(['profiles' => (new RouterOs())->hotspotProfiles($router)]);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function generate()
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'noc'], true)) return $this->response->setStatusCode(403)->setBody('Tidak memiliki akses.');
        $count = (int) $this->request->getPost('count');
        $profile = trim((string) $this->request->getPost('profile'));
        $routerId = (int) $this->request->getPost('router_id');
        $postedBranchId = (int) $this->request->getPost('branch_id');
        if ($count < 1 || $count > 100 || $profile === '' || $routerId < 1) return redirect()->back()->with('error', 'Pilih cabang, router, profil hotspot, dan jumlah 1–100 voucher.');

        $db = db_connect();
        $user = session('user');
        $branchId = ! empty($user['branch_id']) ? (int) $user['branch_id'] : $postedBranchId;
        if (! $db->table('branches')->where('id', $branchId)->where('status', 'active')->countAllResults()) return redirect()->back()->with('error', 'Pilih cabang aktif.');
        $router = $db->table('routers')->where('id', $routerId)->where('branch_id', $branchId)->where('active', 1)->get()->getRowArray();
        if (! $router) return redirect()->back()->with('error', 'Pilih router aktif dari cabang yang dipilih.');
        try {
            $profiles = (new RouterOs())->hotspotProfiles($router);
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        $selectedProfile = null;
        foreach ($profiles as $item) if ($item['name'] === $profile) { $selectedProfile = $item; break; }
        if (! $selectedProfile) return redirect()->back()->with('error', 'Profil tidak ditemukan pada router terpilih. Muat ulang daftar profil.');
        $unitPrice = $selectedProfile['price'];

        $created = 0;
        $failed = 0;
        for ($i = 0; $i < $count; $i++) {
            $username = 'HS' . strtoupper(bin2hex(random_bytes(4)));
            $password = strtoupper(bin2hex(random_bytes(3)));
            $status = 'syncing';
            $db->table('hotspot_vouchers')->insert([
                'branch_id' => $branchId, 'router_id' => $router['id'] ?? null, 'profile' => $profile,
                'username' => $username, 'password' => $password, 'status' => $status,
                'unit_price' => $unitPrice, 'generated_by' => $user['id'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $voucherId = (int) $db->insertID();
            try {
                (new RouterOs())->createHotspotUser($router, $username, $password, $profile);
                $db->table('hotspot_vouchers')->where('id', $voucherId)->update(['status' => 'ready']);
                $created++;
            } catch (Throwable $e) {
                $db->table('hotspot_vouchers')->where('id', $voucherId)->update(['status' => 'sync_failed']);
                $failed++;
                log_message('error', 'Mynet voucher sync failed for router {router}: {error}', ['router' => $router['name'], 'error' => $e->getMessage()]);
            }
        }
        $message = $created . ' voucher berhasil dibuat';
        if ($failed) $message .= ', ' . $failed . ' gagal disinkronkan ke router';
        return redirect()->to('/area/vouchers')->with($failed ? 'warning' : 'success', $message . '.');
    }

    private function inBranch($branchId): bool
    {
        return empty(mynet_user('branch_id')) || (int) mynet_user('branch_id') === (int) $branchId;
    }
}
