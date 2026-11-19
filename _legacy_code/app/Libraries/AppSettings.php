<?php

namespace App\Libraries;

class AppSettings
{
    public function all(): array
    {
        $rows = db_connect()->table('app_settings')->get()->getResultArray();
        $settings = [];
        foreach ($rows as $row) $settings[$row['setting_key']] = $row['setting_value'];
        return $settings;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, ?string $value, ?int $userId = null): void
    {
        $db = db_connect();
        $existing = $db->table('app_settings')->select('id')->where('setting_key', $key)->get()->getRowArray();
        $data = ['setting_value' => $value, 'updated_by' => $userId, 'updated_at' => date('Y-m-d H:i:s')];
        if ($existing) $db->table('app_settings')->where('id', $existing['id'])->update($data);
        else $db->table('app_settings')->insert(['setting_key' => $key] + $data);
    }
}
