<?php

namespace App\Controllers;

use App\Libraries\RouterOs;
use Config\Services;

class Routers extends BaseController
{
    private function allowed(): bool
    {
        return in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin'], true);
    }

    public function edit(int $id)
    {
        if (! $this->allowed()) return redirect()->to('/area/routers')->with('error', 'Tidak memiliki akses mengubah router.');
        $router = db_connect()->table('routers')->where('id', $id)->get()->getRowArray();
        if (! $router || ! $this->inBranch($router['branch_id'])) return redirect()->to('/area/routers')->with('error', 'Router tidak ditemukan pada cabang Anda.');
        $branches = db_connect()->table('branches')->select('id, name')->where('status', 'active')->orderBy('name')->get()->getResultArray();
        $settings = (new RouterOs())->connectionSettings($router);
        return view('routers/edit', ['router' => $router, 'settings' => $settings, 'branches' => $branches, 'menus' => mynet_menus(mynet_user('role')), 'title' => 'Edit router']);
    }

    public function update(int $id)
    {
        if (! $this->allowed()) return redirect()->to('/area/routers')->with('error', 'Tidak memiliki akses mengubah router.');
        $db = db_connect();
        $router = $db->table('routers')->where('id', $id)->get()->getRowArray();
        if (! $router || ! $this->inBranch($router['branch_id'])) return redirect()->to('/area/routers')->with('error', 'Router tidak ditemukan pada cabang Anda.');
        $host = trim((string) $this->request->getPost('host'));
        $name = trim((string) $this->request->getPost('name'));
        $username = trim((string) $this->request->getPost('username'));
        $port = filter_var($this->request->getPost('api_port'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        $currentSettings = (new RouterOs())->connectionSettings($router);
        $branchId = mynet_user('branch_id') ?: (int) $this->request->getPost('branch_id');
        $branch = $branchId ? $db->table('branches')->where('id', $branchId)->where('status', 'active')->countAllResults() : 0;
        $validHost = filter_var($host, FILTER_VALIDATE_IP) || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        if ($name === '' || strlen($name) > 120 || ! $validHost || str_contains($host, '/') || $port === false || $username === '' || ! $branch || ! in_array((string) $this->request->getPost('api_ssl'), ['0', '1'], true) || ! in_array((string) $this->request->getPost('verify_ssl'), ['0', '1'], true)) {
            return redirect()->back()->withInput()->with('error', 'Periksa nama, host/IP, port API, username, dan cabang.');
        }
        $password = (string) $this->request->getPost('password');
        $settings = [
            'host' => $host, 'port' => $port, 'username' => $username,
            'password' => $password !== '' ? $password : (string) $currentSettings['password'],
            'ssl' => (string) $this->request->getPost('api_ssl') === '1',
            'verify_ssl' => (string) $this->request->getPost('verify_ssl') === '1',
        ];
        $data = [
            'name' => $name, 'host' => null, 'api_port' => null, 'base_url' => 'routeros://configured',
            'username' => 'encrypted', 'password_encrypted' => '',
            'connection_encrypted' => base64_encode(Services::encrypter()->encrypt(json_encode($settings, JSON_THROW_ON_ERROR))),
            'branch_id' => $branchId, 'verify_ssl' => (int) $settings['verify_ssl'], 'updated_at' => date('Y-m-d H:i:s'),
        ];
        $db->table('routers')->where('id', $id)->update($data);
        return redirect()->to('/area/routers')->with('success', 'Konfigurasi router berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->allowed()) return redirect()->to('/area/routers')->with('error', 'Tidak memiliki akses menghapus router.');
        $db = db_connect();
        $router = $db->table('routers')->where('id', $id)->get()->getRowArray();
        if (! $router || ! $this->inBranch($router['branch_id'])) return redirect()->to('/area/routers')->with('error', 'Router tidak ditemukan pada cabang Anda.');
        $customers = $db->table('customers')->where('mikrotik_router_id', $id)->countAllResults();
        $vouchers = $db->table('hotspot_vouchers')->where('router_id', $id)->countAllResults();
        if ($customers || $vouchers) return redirect()->to('/area/routers')->with('error', 'Router tidak dapat dihapus karena masih dipakai pelanggan atau riwayat voucher. Nonaktifkan routernya sebagai gantinya.');
        $db->table('routers')->where('id', $id)->delete();
        return redirect()->to('/area/routers')->with('success', 'Router berhasil dihapus.');
    }

    private function inBranch($branchId): bool
    {
        return ! mynet_user('branch_id') || (int) mynet_user('branch_id') === (int) $branchId;
    }
}
