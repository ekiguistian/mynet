<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Services;

class EncryptRouterConnectionSettings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('routers', [
            'connection_encrypted' => ['type' => 'TEXT', 'null' => true],
        ]);

        foreach ($this->db->table('routers')->get()->getResultArray() as $router) {
            $parts = parse_url((string) ($router['base_url'] ?? ''));
            $host = (string) ($router['host'] ?? $parts['host'] ?? '');
            $port = (int) ($router['api_port'] ?? $parts['port'] ?? 8728);
            $encryptedPassword = base64_decode((string) ($router['password_encrypted'] ?? ''), true);
            $password = '';
            if ($encryptedPassword !== false && $encryptedPassword !== '') {
                try { $password = Services::encrypter()->decrypt($encryptedPassword); } catch (\Throwable $e) { $password = ''; }
            }
            $settings = [
                'host' => $host, 'port' => $port, 'username' => (string) ($router['username'] ?? ''),
                'password' => $password, 'ssl' => true, 'verify_ssl' => (bool) ($router['verify_ssl'] ?? true),
            ];
            $blob = base64_encode(Services::encrypter()->encrypt(json_encode($settings, JSON_THROW_ON_ERROR)));
            $this->db->table('routers')->where('id', $router['id'])->update([
                'connection_encrypted' => $blob,
                'base_url' => 'routeros://configured',
                'host' => null,
                'api_port' => null,
                'username' => 'encrypted',
                'password_encrypted' => '',
            ]);
        }
    }

    public function down()
    {
        foreach ($this->db->table('routers')->get()->getResultArray() as $router) {
            $blob = base64_decode((string) ($router['connection_encrypted'] ?? ''), true);
            if ($blob === false) continue;
            try { $settings = json_decode(Services::encrypter()->decrypt($blob), true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $e) { continue; }
            $host = (string) ($settings['host'] ?? '');
            $port = (int) ($settings['port'] ?? 8728);
            $this->db->table('routers')->where('id', $router['id'])->update([
                'base_url' => 'https://' . $authority . ':' . $port,
                'host' => $host,
                'api_port' => $port,
                'username' => (string) ($settings['username'] ?? ''),
                'password_encrypted' => base64_encode(Services::encrypter()->encrypt((string) ($settings['password'] ?? ''))),
                'verify_ssl' => ! empty($settings['verify_ssl']) ? 1 : 0,
            ]);
        }
        $this->forge->dropColumn('routers', 'connection_encrypted');
    }
}
