<?php

namespace App\Libraries;

use Config\Services;
use RuntimeException;

class IntegrationConfig
{
    public function getBranchWhatsapp(int $branchId): array
    {
        $row = db_connect()->table('branch_whatsapp_integrations')->where('branch_id', $branchId)->get()->getRowArray();
        if (! $row) return $this->get('whatsapp');
        $encrypted = base64_decode((string) $row['encrypted_value'], true);
        if ($encrypted === false) throw new RuntimeException('Konfigurasi WhatsApp cabang tidak dapat dibaca.');
        $decoded = json_decode(Services::encrypter()->decrypt($encrypted), true);
        if (! is_array($decoded)) throw new RuntimeException('Konfigurasi WhatsApp cabang tidak valid.');
        return $decoded;
    }

    public function saveBranchWhatsapp(int $branchId, array $configuration): void
    {
        $encrypted = base64_encode(Services::encrypter()->encrypt(json_encode($configuration, JSON_THROW_ON_ERROR)));
        $db = db_connect();
        $row = $db->table('branch_whatsapp_integrations')->select('id')->where('branch_id', $branchId)->get()->getRowArray();
        $data = ['encrypted_value' => $encrypted, 'updated_at' => date('Y-m-d H:i:s')];
        if ($row) $db->table('branch_whatsapp_integrations')->where('id', $row['id'])->update($data);
        else $db->table('branch_whatsapp_integrations')->insert(['branch_id' => $branchId] + $data);
    }

    public function get(string $section): array
    {
        $row = db_connect()->table('integration_settings')->where('section', $section)->get()->getRowArray();
        if ($row) {
            $encrypted = base64_decode((string) $row['encrypted_value'], true);
            if ($encrypted === false) throw new RuntimeException('Konfigurasi integrasi tidak dapat dibaca.');
            $decoded = json_decode(Services::encrypter()->decrypt($encrypted), true);
            if (! is_array($decoded)) throw new RuntimeException('Konfigurasi integrasi tidak valid.');
            return $decoded;
        }

        return match ($section) {
            'whatsapp' => [
                'provider' => (string) env('WA_PROVIDER'),
                'ngirimwa_api_key' => (string) env('NGIRIMWA_API_KEY'),
                'fonnte_token' => (string) env('FONNTE_TOKEN'),
                'technician_group_id' => '',
                'finance_group_id' => '',
                'admin_group_id' => '',
            ],
            'payment' => [
                'provider' => (string) env('PAYMENT_PROVIDER'),
                'tripay_mode' => (string) env('TRIPAY_MODE', 'sandbox'),
                'tripay_api_key' => (string) env('TRIPAY_API_KEY'),
                'tripay_private_key' => (string) env('TRIPAY_PRIVATE_KEY'),
                'tripay_merchant_code' => (string) env('TRIPAY_MERCHANT_CODE'),
                'tripay_method' => (string) env('TRIPAY_METHOD', 'BRIVA'),
                'midtrans_mode' => (string) env('MIDTRANS_MODE', 'sandbox'),
                'midtrans_server_key' => (string) env('MIDTRANS_SERVER_KEY'),
                'xendit_secret_key' => (string) env('XENDIT_SECRET_KEY'),
                'xendit_webhook_token' => (string) env('XENDIT_WEBHOOK_TOKEN'),
            ],
            default => [],
        };
    }

    public function save(string $section, array $configuration, ?int $userId = null): void
    {
        $encrypted = base64_encode(Services::encrypter()->encrypt(json_encode($configuration, JSON_THROW_ON_ERROR)));
        $db = db_connect();
        $row = $db->table('integration_settings')->select('id')->where('section', $section)->get()->getRowArray();
        $data = ['encrypted_value' => $encrypted, 'updated_by' => $userId, 'updated_at' => date('Y-m-d H:i:s')];
        if ($row) $db->table('integration_settings')->where('id', $row['id'])->update($data);
        else $db->table('integration_settings')->insert(['section' => $section] + $data);
    }
}
