<?php

namespace App\Libraries;

use Config\Services;
use RuntimeException;

class RouterOs
{
    public function createHotspotUser(array $router, string $username, string $password, string $profile): void
    {
        $baseUrl = rtrim((string) $router['base_url'], '/');
        if (! str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException('REST API router wajib menggunakan HTTPS.');
        }
        $encrypted = base64_decode((string) $router['password_encrypted'], true);
        if ($encrypted === false) throw new RuntimeException('Kredensial router tidak valid.');
        $routerPassword = Services::encrypter()->decrypt($encrypted);
        $verify = (bool) $router['verify_ssl'];
        try {
            $client = Services::curlrequest(['timeout' => (int) env('MIKROTIK_TIMEOUT', 15), 'http_errors' => false, 'verify' => $verify]);
            $response = $client->request('PUT', $baseUrl . '/rest/ip/hotspot/user', [
                'auth' => [$router['username'], $routerPassword],
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body' => json_encode(['name' => $username, 'password' => $password, 'profile' => $profile], JSON_THROW_ON_ERROR),
            ]);
            if ((int) $response->getStatusCode() < 200 || (int) $response->getStatusCode() >= 300) {
                throw new RuntimeException('RouterOS menolak pembuatan voucher (HTTP ' . $response->getStatusCode() . ').');
            }
        } catch (\Throwable $e) {
            throw new RuntimeException('Sinkronisasi RouterOS gagal: ' . $e->getMessage(), 0, $e);
        }
    }
}
