<?php

namespace App\Controllers;

use App\Libraries\AppSettings as SettingsStore;

class SystemSettings extends BaseController
{
    private array $administrators = ['superadmin', 'administrator'];

    public function index()
    {
        if ($denied = $this->authorize()) return $denied;
        return view('settings/app', [
            'title' => 'Pengaturan aplikasi',
            'settings' => (new SettingsStore())->all(),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function save()
    {
        if ($denied = $this->authorize()) return $denied;
        $posted = $this->request->getPost();
        $values = [
            'site_name' => trim((string) ($posted['site_name'] ?? 'Mynet Billing')),
            'site_tagline' => trim((string) ($posted['site_tagline'] ?? '')),
            'public_phone' => trim((string) ($posted['public_phone'] ?? '')),
            'public_address' => trim((string) ($posted['public_address'] ?? '')),
            'canonical_url' => trim((string) ($posted['canonical_url'] ?? '')),
            'seo_description' => trim((string) ($posted['seo_description'] ?? '')),
            'seo_keywords' => trim((string) ($posted['seo_keywords'] ?? '')),
            'seo_robots' => (string) ($posted['seo_robots'] ?? 'noindex,nofollow'),
            'google_site_verification' => trim((string) ($posted['google_site_verification'] ?? '')),
            'google_tag_id' => strtoupper(trim((string) ($posted['google_tag_id'] ?? ''))),
            'social_facebook' => trim((string) ($posted['social_facebook'] ?? '')),
            'social_instagram' => trim((string) ($posted['social_instagram'] ?? '')),
            'social_tiktok' => trim((string) ($posted['social_tiktok'] ?? '')),
            'social_youtube' => trim((string) ($posted['social_youtube'] ?? '')),
            'social_x' => trim((string) ($posted['social_x'] ?? '')),
            'social_image_url' => trim((string) ($posted['social_image_url'] ?? '')),
        ];
        if ($values['site_name'] === '' || mb_strlen($values['site_name']) > 120) return redirect()->back()->withInput()->with('error', 'Nama aplikasi wajib diisi dan maksimal 120 karakter.');
        if ($values['canonical_url'] !== '' && ! $this->isWebUrl($values['canonical_url'])) return redirect()->back()->withInput()->with('error', 'URL canonical harus menggunakan HTTP atau HTTPS.');
        if ($values['google_tag_id'] !== '' && ! preg_match('/^(G-[A-Z0-9]+|GTM-[A-Z0-9]+|AW-[0-9]+)$/', $values['google_tag_id'])) {
            return redirect()->back()->withInput()->with('error', 'ID Google Tag harus berbentuk G-..., GTM-..., atau AW-....');
        }
        if (! in_array($values['seo_robots'], ['index,follow', 'noindex,nofollow'], true)) $values['seo_robots'] = 'index,follow';
        if ($values['google_site_verification'] !== '' && ! preg_match('/^[A-Za-z0-9_-]{8,200}$/', $values['google_site_verification'])) return redirect()->back()->withInput()->with('error', 'Kode verifikasi Google Search Console tidak valid.');
        foreach (['social_facebook', 'social_instagram', 'social_tiktok', 'social_youtube', 'social_x', 'social_image_url'] as $key) {
            if ($values[$key] !== '' && ! $this->isWebUrl($values[$key])) return redirect()->back()->withInput()->with('error', 'URL media sosial dan gambar harus menggunakan HTTP atau HTTPS.');
        }

        $files = [];
        foreach (['logo_file' => ['logo', ['image/png' => 'png', 'image/jpeg' => 'jpg']], 'favicon_file' => ['favicon', ['image/png' => 'png', 'image/vnd.microsoft.icon' => 'ico', 'image/x-icon' => 'ico']]] as $field => [$name, $types]) {
            $file = $this->request->getFile($field);
            if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) continue;
            $mime = $file->getMimeType();
            if (! $file->isValid() || ! isset($types[$mime]) || $file->getSizeByUnit('mb') > 2) return redirect()->back()->withInput()->with('error', 'Unggah logo PNG/JPG atau favicon PNG/ICO maksimal 2 MB.');
            $files[] = [$file, $name, $types[$mime]];
        }

        $store = new SettingsStore();
        foreach ($values as $key => $value) $store->set($key, $value, (int) mynet_user('id'));
        foreach ($files as [$file, $name, $extension]) {
            $directory = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'branding';
            if (! is_dir($directory)) mkdir($directory, 0755, true);
            $filename = $name . '-' . bin2hex(random_bytes(5)) . '.' . $extension;
            $file->move($directory, $filename, true);
            $store->set($name . '_url', '/uploads/branding/' . $filename, (int) mynet_user('id'));
        }

        return redirect()->to('/settings')->with('success', 'Pengaturan tampilan dan SEO berhasil disimpan.');
    }

    public function apiKeys()
    {
        if ($denied = $this->authorize()) return $denied;
        $query = db_connect()->table('api_keys')->orderBy('id', 'DESC');
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        return view('settings/api_keys', [
            'title' => 'API eksternal', 'keys' => $query->get()->getResultArray(),
            'newKey' => session()->getFlashdata('new_api_key'),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function createApiKey()
    {
        if ($denied = $this->authorize()) return $denied;
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '' || mb_strlen($name) > 100) return redirect()->back()->withInput()->with('error', 'Nama API key wajib diisi (maksimal 100 karakter).');
        $allowedScopes = ['customers_read', 'invoices_read', 'tickets_read', 'tickets_write'];
        $scopes = array_values(array_intersect($allowedScopes, (array) $this->request->getPost('scopes')));
        if (! $scopes) return redirect()->back()->with('error', 'Pilih minimal satu izin API.');
        $token = 'mynet_live_' . bin2hex(random_bytes(32));
        $db = db_connect();
        $db->table('api_keys')->insert([
            'branch_id' => mynet_user('branch_id'), 'name' => $name,
            'prefix' => substr($token, 0, 22), 'secret_hash' => hash('sha256', $token),
            'scopes' => json_encode($scopes), 'created_by' => mynet_user('id'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/settings/api-keys')->with('new_api_key', $token)->with('success', 'API key dibuat. Salin sekarang; nilainya tidak akan ditampilkan lagi.');
    }

    public function revokeApiKey()
    {
        if ($denied = $this->authorize()) return $denied;
        $query = db_connect()->table('api_keys')->where('id', (int) $this->request->getPost('id'))->where('revoked_at', null);
        if (mynet_user('branch_id')) $query->where('branch_id', mynet_user('branch_id'));
        $query->update(['revoked_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/settings/api-keys')->with('success', 'API key sudah dicabut.');
    }

    private function authorize()
    {
        if (! in_array(mynet_user('role'), $this->administrators, true)) return $this->response->setStatusCode(403)->setBody(view('errors/html/error_403'));
        return null;
    }

    private function isWebUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) return false;
        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
