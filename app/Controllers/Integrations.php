<?php

namespace App\Controllers;

use App\Libraries\IntegrationConfig;
use App\Libraries\RouterOs;
use App\Libraries\WhatsappGateway;
use Throwable;

class Integrations extends BaseController
{
    public function index()
    {
        if ($denied = $this->authorize()) return $denied;
        $config = new IntegrationConfig();
        $wa = $config->get('whatsapp');
        $payment = $config->get('payment');
        $secretStatus = [];
        foreach (['ngirimwa_api_key', 'fonnte_token', 'tripay_api_key', 'tripay_private_key', 'tripay_merchant_code', 'midtrans_server_key', 'xendit_secret_key', 'xendit_webhook_token'] as $field) {
            $secretStatus[$field] = ! empty($wa[$field]) || ! empty($payment[$field]);
            unset($wa[$field], $payment[$field]);
        }
        $routerQuery = db_connect()->table('routers')->select('id, name, branch_id, active')->orderBy('name');
        if (mynet_user('branch_id')) $routerQuery->where('branch_id', mynet_user('branch_id'));
        $branches = db_connect()->table('branches')->select('id, name')->where('status', 'active')->orderBy('name')->get()->getResultArray();
        if (mynet_user('branch_id')) $branches = array_values(array_filter($branches, static fn (array $branch): bool => (int) $branch['id'] === (int) mynet_user('branch_id')));
        $branchWhatsapp = [];
        foreach ($branches as $branch) {
            $branchConfig = $config->getBranchWhatsapp((int) $branch['id']);
            $branchWhatsapp[] = [
                'id' => (int) $branch['id'], 'name' => $branch['name'], 'provider' => $branchConfig['provider'] ?? 'ngirimwa',
                'ngirimwa_saved' => ! empty($branchConfig['ngirimwa_api_key']), 'fonnte_saved' => ! empty($branchConfig['fonnte_token']),
                'technician_group_id' => $branchConfig['technician_group_id'] ?? '', 'finance_group_id' => $branchConfig['finance_group_id'] ?? '',
                'admin_group_id' => $branchConfig['admin_group_id'] ?? '',
            ];
        }
        return view('settings/integrations', [
            'title' => 'Integrasi API', 'whatsapp' => $wa, 'payment' => $payment, 'secretStatus' => $secretStatus,
            'routers' => $routerQuery->get()->getResultArray(),
            'branches' => $branches, 'branchWhatsapp' => $branchWhatsapp,
            'templates' => db_connect()->table('message_templates')->orderBy('name')->get()->getResultArray(),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function saveBranchWhatsapp()
    {
        if ($denied = $this->authorize()) return $denied;
        $branchId = (int) $this->request->getPost('branch_id');
        $branch = db_connect()->table('branches')->where('id', $branchId)->where('status', 'active')->get()->getRowArray();
        if (! $branch || (mynet_user('branch_id') && (int) $branch['id'] !== (int) mynet_user('branch_id'))) return redirect()->back()->with('error', 'Cabang tidak ditemukan atau Anda tidak memiliki akses.');
        $config = new IntegrationConfig();
        $existing = $config->getBranchWhatsapp($branchId);
        $provider = strtolower(trim((string) $this->request->getPost('provider')));
        if (! in_array($provider, ['ngirimwa', 'fonnte'], true)) return redirect()->back()->with('error', 'Pilih gateway WhatsApp yang valid.');
        $data = [
            'provider' => $provider,
            'ngirimwa_api_key' => trim((string) $this->request->getPost('ngirimwa_api_key')) ?: ($existing['ngirimwa_api_key'] ?? ''),
            'fonnte_token' => trim((string) $this->request->getPost('fonnte_token')) ?: ($existing['fonnte_token'] ?? ''),
        ];
        $required = $provider === 'fonnte' ? 'fonnte_token' : 'ngirimwa_api_key';
        if ($data[$required] === '') return redirect()->back()->with('error', 'Token gateway untuk penyedia terpilih wajib diisi.');
        foreach (['technician_group_id', 'finance_group_id', 'admin_group_id'] as $field) {
            $value = trim((string) $this->request->getPost($field));
            if ($value !== '' && ! preg_match('/^[0-9-]{8,40}@g\\.us$/', $value)) return redirect()->back()->with('error', 'ID grup WhatsApp harus berakhiran @g.us.');
            $data[$field] = $value;
        }
        $config->saveBranchWhatsapp($branchId, $data);
        return redirect()->to('/integrations')->with('success', 'Gateway WhatsApp cabang berhasil diperbarui.');
    }

    public function save(string $section)
    {
        if ($denied = $this->authorize()) return $denied;
        $config = new IntegrationConfig();
        $existing = $config->get($section);
        if ($section === 'whatsapp') {
            $data = [
                'provider' => strtolower(trim((string) $this->request->getPost('provider'))),
                'ngirimwa_api_key' => trim((string) $this->request->getPost('ngirimwa_api_key')) ?: ($existing['ngirimwa_api_key'] ?? ''),
                'fonnte_token' => trim((string) $this->request->getPost('fonnte_token')) ?: ($existing['fonnte_token'] ?? ''),
                'test_recipient' => trim((string) $this->request->getPost('test_recipient')),
                'technician_group_id' => $this->postedOrExisting('technician_group_id', $existing),
                'finance_group_id' => $this->postedOrExisting('finance_group_id', $existing),
                'admin_group_id' => $this->postedOrExisting('admin_group_id', $existing),
                'branch_groups' => $existing['branch_groups'] ?? [],
            ];
            $branchGroups = $this->request->getPost('branch_groups');
            if (is_array($branchGroups)) {
                $validBranchIds = array_map('strval', array_column(db_connect()->table('branches')->select('id')->where('status', 'active')->get()->getResultArray(), 'id'));
                $data['branch_groups'] = [];
                foreach ($branchGroups as $branchId => $groups) {
                    if (! in_array((string) $branchId, $validBranchIds, true) || ! is_array($groups)) return redirect()->back()->with('error', 'Cabang grup WhatsApp tidak valid.');
                    foreach (['teknisi', 'keuangan', 'admin'] as $role) {
                        $groupId = trim((string) ($groups[$role] ?? ''));
                        if ($groupId !== '' && ! preg_match('/^[0-9-]{8,40}@g\\.us$/', $groupId)) return redirect()->back()->with('error', 'ID grup harus berakhiran @g.us.');
                        if ($groupId !== '') $data['branch_groups'][(string) $branchId][$role] = $groupId;
                    }
                }
            }
            if (! in_array($data['provider'], ['ngirimwa', 'fonnte'], true)) return redirect()->back()->with('error', 'Pilih penyedia WhatsApp yang valid.');
            foreach (['technician_group_id', 'finance_group_id', 'admin_group_id'] as $groupField) {
                if ($data[$groupField] !== '' && ! preg_match('/^[0-9-]{8,40}@g\.us$/', $data[$groupField])) {
                    return redirect()->back()->with('error', 'ID grup harus berakhiran @g.us.');
                }
            }
        } elseif ($section === 'payment') {
            $data = [
                'provider' => strtolower(trim((string) $this->request->getPost('provider'))),
                'tripay_mode' => $this->enumPost('tripay_mode', ['sandbox', 'production'], 'sandbox'),
                'tripay_method' => strtoupper(trim((string) $this->request->getPost('tripay_method')) ?: 'BRIVA'),
                'tripay_api_key' => trim((string) $this->request->getPost('tripay_api_key')) ?: ($existing['tripay_api_key'] ?? ''),
                'tripay_private_key' => trim((string) $this->request->getPost('tripay_private_key')) ?: ($existing['tripay_private_key'] ?? ''),
                'tripay_merchant_code' => trim((string) $this->request->getPost('tripay_merchant_code')) ?: ($existing['tripay_merchant_code'] ?? ''),
                'midtrans_mode' => $this->enumPost('midtrans_mode', ['sandbox', 'production'], 'sandbox'),
                'midtrans_server_key' => trim((string) $this->request->getPost('midtrans_server_key')) ?: ($existing['midtrans_server_key'] ?? ''),
                'xendit_secret_key' => trim((string) $this->request->getPost('xendit_secret_key')) ?: ($existing['xendit_secret_key'] ?? ''),
                'xendit_webhook_token' => trim((string) $this->request->getPost('xendit_webhook_token')) ?: ($existing['xendit_webhook_token'] ?? ''),
            ];
            if (! in_array($data['provider'], ['tripay', 'midtrans', 'xendit'], true)) return redirect()->back()->with('error', 'Pilih payment gateway yang valid.');
        } else {
            return $this->response->setStatusCode(404)->setBody('Integrasi tidak ditemukan.');
        }
        $config->save($section, $data, (int) mynet_user('id'));
        return redirect()->to('/integrations')->with('success', 'Konfigurasi integrasi berhasil disimpan terenkripsi.');
    }

    public function testWhatsApp()
    {
        if ($denied = $this->authorize()) return $denied;
        $to = trim((string) $this->request->getPost('test_recipient'));
        if ($to === '') return redirect()->back()->with('error', 'Isi nomor tujuan uji terlebih dahulu.');
        try {
            (new WhatsappGateway())->send($to, 'Uji koneksi WhatsApp Mynet pada ' . date('d-m-Y H:i:s'), 'mynet-test-' . date('His'));
            return redirect()->to('/integrations')->with('success', 'Pesan uji diterima oleh gateway WhatsApp.');
        } catch (Throwable $e) {
            return redirect()->to('/integrations')->with('error', $e->getMessage());
        }
    }

    public function testPayment()
    {
        if ($denied = $this->authorize()) return $denied;
        $config = (new IntegrationConfig())->get('payment');
        $required = match ($config['provider'] ?? '') {
            'tripay' => ['tripay_api_key', 'tripay_private_key', 'tripay_merchant_code'],
            'midtrans' => ['midtrans_server_key'],
            'xendit' => ['xendit_secret_key', 'xendit_webhook_token'],
            default => [],
        };
        $missing = array_filter($required, static fn ($key) => empty($config[$key]));
        if (! $required || $missing) return redirect()->to('/integrations')->with('error', 'Konfigurasi gateway belum lengkap: ' . implode(', ', $missing));
        return redirect()->to('/integrations')->with('success', 'Kredensial payment gateway lengkap. Jalankan transaksi uji di sandbox untuk memverifikasi callback.');
    }

    public function testRouter(int $id)
    {
        if ($denied = $this->authorize()) return $denied;
        $query = db_connect()->table('routers')->where('id', $id)->where('active', 1);
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        $router = $query->get()->getRowArray();
        if (! $router) return redirect()->to('/integrations')->with('error', 'Router tidak ditemukan atau tidak aktif.');
        try {
            $identity = (new RouterOs())->testConnection($router);
            return redirect()->to('/integrations')->with('success', 'RouterOS terhubung: ' . $identity);
        } catch (Throwable $e) {
            return redirect()->to('/integrations')->with('error', $e->getMessage());
        }
    }

    public function templates()
    {
        if ($denied = $this->authorize()) return $denied;
        return view('settings/templates', [
            'title' => 'Template pesan WhatsApp',
            'templates' => db_connect()->table('message_templates')->orderBy('name')->get()->getResultArray(),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function saveTemplate()
    {
        if ($denied = $this->authorize()) return $denied;
        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        $code = strtolower(trim((string) $this->request->getPost('code')));
        $body = trim((string) $this->request->getPost('body'));
        if (! preg_match('/^[a-z0-9_]{3,60}$/', $code) || $name === '' || $body === '') return redirect()->back()->with('error', 'Isi kode, nama, dan isi pesan template dengan benar.');
        $db = db_connect();
        $data = ['code' => $code, 'name' => $name, 'body' => $body, 'active' => (int) (bool) $this->request->getPost('active'), 'updated_by' => mynet_user('id'), 'updated_at' => date('Y-m-d H:i:s')];
        if ($id) $db->table('message_templates')->where('id', $id)->update($data);
        else $db->table('message_templates')->insert($data + ['created_by' => mynet_user('id'), 'created_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/integrations/templates')->with('success', 'Template pesan berhasil disimpan.');
    }

    private function enumPost(string $key, array $allowed, string $default): string
    {
        $value = (string) $this->request->getPost($key);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function postedOrExisting(string $key, array $existing): string
    {
        $posted = $this->request->getPost($key);
        return $posted === null ? (string) ($existing[$key] ?? '') : trim((string) $posted);
    }

    private function authorize()
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator'], true)) return $this->response->setStatusCode(403)->setBody(view('errors/html/error_403'));
        return null;
    }
}
