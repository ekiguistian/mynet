<?php

namespace App\Libraries;

use Config\Services;
use RuntimeException;

class RouterOs
{
    public function hotspotProfiles(array $router): array
    {
        $baseUrl = rtrim((string) $router['base_url'], '/');
        if (! str_starts_with($baseUrl, 'https://')) throw new RuntimeException('REST API router wajib menggunakan HTTPS.');
        $encrypted = base64_decode((string) $router['password_encrypted'], true);
        if ($encrypted === false) throw new RuntimeException('Kredensial router tidak valid.');
        $password = Services::encrypter()->decrypt($encrypted);
        $client = Services::curlrequest(['timeout' => (int) env('MIKROTIK_TIMEOUT', 15), 'http_errors' => false, 'verify' => (bool) $router['verify_ssl']]);
        try {
            $response = $client->request('GET', $baseUrl . '/rest/ip/hotspot/user/profile', [
                'auth' => [$router['username'], $password], 'headers' => ['Accept' => 'application/json'],
            ]);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) throw new RuntimeException('RouterOS mengembalikan HTTP ' . $response->getStatusCode() . '.');
            $profiles = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($profiles)) throw new RuntimeException('Daftar profil MikroTik tidak valid.');
            return array_values(array_map(static function (array $profile): array {
                $comment = trim((string) ($profile['comment'] ?? ''));
                $price = preg_match('/^[0-9]{1,14}$/', $comment) ? (int) $comment : null;
                return ['name' => (string) ($profile['name'] ?? ''), 'comment' => $comment, 'price' => $price];
            }, array_filter($profiles, static fn ($item): bool => is_array($item) && ! empty($item['name']))));
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal membaca profil hotspot MikroTik: ' . $e->getMessage(), 0, $e);
        }
    }

    public function testConnection(array $router): string
    {
        $baseUrl = rtrim((string) $router['base_url'], '/');
        if (! str_starts_with($baseUrl, 'https://')) throw new RuntimeException('REST API router wajib menggunakan HTTPS.');
        $encrypted = base64_decode((string) $router['password_encrypted'], true);
        if ($encrypted === false) throw new RuntimeException('Kredensial router tidak valid.');
        try {
            $password = Services::encrypter()->decrypt($encrypted);
            $client = Services::curlrequest(['timeout' => (int) env('MIKROTIK_TIMEOUT', 15), 'http_errors' => false, 'verify' => (bool) $router['verify_ssl']]);
            $response = $client->request('GET', $baseUrl . '/rest/system/identity', [
                'auth' => [$router['username'], $password], 'headers' => ['Accept' => 'application/json'],
            ]);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) throw new RuntimeException('RouterOS mengembalikan HTTP ' . $response->getStatusCode() . '.');
            $identity = json_decode((string) $response->getBody(), true);
            return (string) ($identity[0]['name'] ?? $identity['name'] ?? $router['name']);
        } catch (\Throwable $e) {
            throw new RuntimeException('Uji koneksi RouterOS gagal: ' . $e->getMessage(), 0, $e);
        }
    }

    public function systemResource(array $router): array
    {
        $baseUrl = rtrim((string) $router['base_url'], '/');
        if (! str_starts_with($baseUrl, 'https://')) throw new RuntimeException('REST API router wajib menggunakan HTTPS.');
        $encrypted = base64_decode((string) $router['password_encrypted'], true);
        if ($encrypted === false) throw new RuntimeException('Kredensial router tidak valid.');
        try {
            $client = Services::curlrequest(['timeout' => 5, 'http_errors' => false, 'verify' => (bool) ($router['verify_ssl'] ?? true)]);
            $response = $client->request('GET', $baseUrl . '/rest/system/resource', [
                'auth' => [$router['username'], Services::encrypter()->decrypt($encrypted)], 'headers' => ['Accept' => 'application/json'],
            ]);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) throw new RuntimeException('RouterOS mengembalikan HTTP ' . $response->getStatusCode() . '.');
            $resource = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if (isset($resource[0]) && is_array($resource[0])) $resource = $resource[0];
            if (! is_array($resource) || ! isset($resource['uptime'])) throw new RuntimeException('Data system resource RouterOS tidak lengkap.');
            return $resource;
        } catch (\Throwable $e) {
            throw new RuntimeException('Gagal membaca system resource: ' . $e->getMessage(), 0, $e);
        }
    }

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

    public function provisionPppoe(array $router, string $username, string $password, string $profile, string $mode): void
    {
        $baseUrl = rtrim((string) $router['base_url'], '/');
        if (! str_starts_with($baseUrl, 'https://')) throw new RuntimeException('REST API router wajib menggunakan HTTPS.');
        $encrypted = base64_decode((string) $router['password_encrypted'], true);
        if ($encrypted === false) throw new RuntimeException('Kredensial router tidak valid.');
        $client = Services::curlrequest(['timeout' => (int) env('MIKROTIK_TIMEOUT', 15), 'http_errors' => false, 'verify' => (bool) $router['verify_ssl']]);
        $options = ['auth' => [$router['username'], Services::encrypter()->decrypt($encrypted)], 'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json']];
        $url = $baseUrl . '/rest/ppp/secret';
        try {
            $lookup = $client->request('GET', $url . '?name=' . rawurlencode($username), $options);
            if ($lookup->getStatusCode() < 200 || $lookup->getStatusCode() >= 300) throw new RuntimeException('Gagal membaca secret PPP pada RouterOS.');
            $found = json_decode((string) $lookup->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $existing = is_array($found) ? ($found[0] ?? null) : null;
            $payload = ['name' => $username, 'password' => $password, 'service' => 'pppoe', 'profile' => $profile];
            if ($mode === 'sync') {
                if (! is_array($existing) || empty($existing['.id'])) throw new RuntimeException('Username PPPoE belum ada di router untuk disinkronkan.');
                $response = $client->request('PATCH', $url . '/' . rawurlencode((string) $existing['.id']), $options + ['body' => json_encode($payload, JSON_THROW_ON_ERROR)]);
            } else {
                if (is_array($existing)) throw new RuntimeException('Username PPPoE sudah ada di router; pilih Sinkronisasi.');
                $response = $client->request('PUT', $url, $options + ['body' => json_encode($payload, JSON_THROW_ON_ERROR)]);
            }
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) throw new RuntimeException('RouterOS menolak perubahan PPPoE (HTTP ' . $response->getStatusCode() . ').');
        } catch (\Throwable $e) {
            throw new RuntimeException('Provisioning PPPoE gagal: ' . $e->getMessage(), 0, $e);
        }
    }

    public function syncStaticIpQueue(array $router, string $queueName, string $ipAddress, string $downloadSpeed, string $uploadSpeed): void
    {
        $baseUrl = rtrim((string) $router['base_url'], '/');
        if (! str_starts_with($baseUrl, 'https://')) throw new RuntimeException('REST API router wajib menggunakan HTTPS.');
        $encrypted = base64_decode((string) $router['password_encrypted'], true);
        if ($encrypted === false) throw new RuntimeException('Kredensial router tidak valid.');
        $target = $ipAddress . (str_contains($ipAddress, ':') ? '/128' : '/32');
        $payload = [
            'name' => $queueName,
            'target' => $target,
            'max-limit' => $this->routerRate($uploadSpeed) . '/' . $this->routerRate($downloadSpeed),
            'comment' => 'Mynet static IP ' . $queueName,
        ];
        $client = Services::curlrequest(['timeout' => (int) env('MIKROTIK_TIMEOUT', 15), 'http_errors' => false, 'verify' => (bool) $router['verify_ssl']]);
        $options = ['auth' => [$router['username'], Services::encrypter()->decrypt($encrypted)], 'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json']];
        $url = $baseUrl . '/rest/queue/simple';
        try {
            $lookup = $client->request('GET', $url . '?name=' . rawurlencode($queueName), $options);
            if ($lookup->getStatusCode() < 200 || $lookup->getStatusCode() >= 300) throw new RuntimeException('Gagal membaca Simple Queue pada RouterOS.');
            $found = json_decode((string) $lookup->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $existing = is_array($found) ? ($found[0] ?? null) : null;
            if (is_array($existing) && ! empty($existing['.id'])) {
                $response = $client->request('PATCH', $url . '/' . rawurlencode((string) $existing['.id']), $options + ['body' => json_encode($payload, JSON_THROW_ON_ERROR)]);
            } else {
                $response = $client->request('PUT', $url, $options + ['body' => json_encode($payload, JSON_THROW_ON_ERROR)]);
            }
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) throw new RuntimeException('RouterOS menolak sinkronisasi Simple Queue (HTTP ' . $response->getStatusCode() . ').');
        } catch (\Throwable $e) {
            throw new RuntimeException('Sinkronisasi Simple Queue gagal: ' . $e->getMessage(), 0, $e);
        }
    }

    private function routerRate(string $rate): string
    {
        $value = strtolower(trim($rate));
        if (! preg_match('/^(\d+(?:\.\d+)?)\s*(bps|k|kbps|kbit\/s|m|mbps|mbit\/s|g|gbps|gbit\/s)$/', $value, $matches)) {
            throw new RuntimeException('Kecepatan paket harus memiliki satuan bps, K, Mbps, atau Gbps.');
        }
        $unit = match ($matches[2]) {
            'bps' => '', 'k', 'kbps', 'kbit/s' => 'K', 'm', 'mbps', 'mbit/s' => 'M', default => 'G',
        };
        return $matches[1] . $unit;
    }
}
