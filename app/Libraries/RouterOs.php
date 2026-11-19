<?php

namespace App\Libraries;

use Config\Services;
use RouterOS\Client;
use RouterOS\Query;
use RuntimeException;

class RouterOs
{
    public function hotspotProfiles(array $router): array
    {
        $profiles = $this->query($router, '/ip/hotspot/user/profile/print');
        return array_values(array_map(static function (array $profile): array {
            $comment = trim((string) ($profile['comment'] ?? ''));
            return [
                'name' => (string) ($profile['name'] ?? ''),
                'comment' => $comment,
                'price' => preg_match('/^[0-9]{1,14}$/', $comment) ? (int) $comment : null,
            ];
        }, array_filter($profiles, static fn ($item): bool => is_array($item) && ! empty($item['name']))));
    }

    public function testConnection(array $router): string
    {
        $identity = $this->query($router, '/system/identity/print');
        return (string) ($identity[0]['name'] ?? $router['name'] ?? 'RouterOS');
    }

    public function monitor(array $router): array
    {
        $identity = $this->query($router, '/system/identity/print');
        $resource = $this->query($router, '/system/resource/print');
        return [
            'identity' => (string) ($identity[0]['name'] ?? $router['name']),
            'uptime' => (string) ($resource[0]['uptime'] ?? ''),
            'cpu' => isset($resource[0]['cpu-load']) ? (int) $resource[0]['cpu-load'] : null,
            'memory_used' => max(0, (int) ($resource[0]['total-memory'] ?? 0) - (int) ($resource[0]['free-memory'] ?? 0)),
            'memory_total' => (int) ($resource[0]['total-memory'] ?? 0),
        ];
    }

    public function systemResource(array $router): array
    {
        $result = $this->query($router, '/system/resource/print');
        if (! isset($result[0]['uptime'])) throw new RuntimeException('Data system resource RouterOS tidak lengkap.');
        return $result[0];
    }

    public function createHotspotUser(array $router, string $username, string $password, string $profile): void
    {
        $this->query($router, '/ip/hotspot/user/add', ['name' => $username, 'password' => $password, 'profile' => $profile]);
    }

    public function provisionPppoe(array $router, string $username, string $password, string $profile, string $mode): void
    {
        $secrets = $this->query($router, '/ppp/secret/print');
        $existing = null;
        foreach ($secrets as $secret) {
            if (($secret['name'] ?? '') === $username) { $existing = $secret; break; }
        }
        if ($mode === 'sync' && empty($existing['.id'])) throw new RuntimeException('Username PPPoE belum ada di router untuk disinkronkan.');
        if ($mode !== 'sync' && $existing) throw new RuntimeException('Username PPPoE sudah ada di router; pilih Sinkronisasi.');
        $attrs = ['name' => $username, 'password' => $password, 'service' => 'pppoe', 'profile' => $profile];
        if ($existing) $attrs['.id'] = $existing['.id'];
        $this->query($router, $existing ? '/ppp/secret/set' : '/ppp/secret/add', $attrs);
    }

    public function syncStaticIpQueue(array $router, string $queueName, string $ipAddress, string $downloadSpeed, string $uploadSpeed): void
    {
        $queues = $this->query($router, '/queue/simple/print');
        $existing = null;
        foreach ($queues as $queue) {
            if (($queue['name'] ?? '') === $queueName) { $existing = $queue; break; }
        }
        $target = $ipAddress . (str_contains($ipAddress, ':') ? '/128' : '/32');
        $attrs = [
            'name' => $queueName, 'target' => $target,
            'max-limit' => $this->routerRate($uploadSpeed) . '/' . $this->routerRate($downloadSpeed),
            'comment' => 'Mynet static IP ' . $queueName,
        ];
        if ($existing) $attrs['.id'] = $existing['.id'];
        $this->query($router, $existing ? '/queue/simple/set' : '/queue/simple/add', $attrs);
    }

    private function query(array $router, string $path, array $attributes = []): array
    {
        $settings = $this->connectionSettings($router);
        $host = trim((string) $settings['host']);
        $port = (int) $settings['port'];
        if ($host === '' || $port < 1 || $port > 65535) throw new RuntimeException('Alamat atau port API RouterOS tidak valid.');

        try {
            $ssl = ! empty($settings['ssl']);
            $client = new Client([
                'host' => $host,
                'user' => (string) $settings['username'],
                'pass' => (string) $settings['password'],
                'port' => $port,
                'ssl' => $ssl,
                'ssl_options' => ['verify_peer' => (bool) $settings['verify_ssl'], 'verify_peer_name' => (bool) $settings['verify_ssl'], 'allow_self_signed' => ! (bool) $settings['verify_ssl']],
                'timeout' => min(8, max(2, (int) env('MIKROTIK_TIMEOUT', 5))),
                'socket_timeout' => 8,
                'attempts' => 1,
                'throw_timeout_exception' => true,
            ]);
            return $client->query(new Query($path, $attributes))->read();
        } catch (\Throwable $e) {
            throw new RuntimeException('Koneksi API RouterOS gagal: ' . $e->getMessage(), 0, $e);
        }
    }

    public function connectionSettings(array $router): array
    {
        $encrypted = base64_decode((string) ($router['connection_encrypted'] ?? ''), true);
        if ($encrypted !== false && $encrypted !== '') {
            try {
                $settings = json_decode(Services::encrypter()->decrypt($encrypted), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($settings)) return $settings;
            } catch (\Throwable $e) {
                throw new RuntimeException('Konfigurasi router terenkripsi tidak dapat dibaca. Periksa encryption key aplikasi.', 0, $e);
            }
        }

        // Fallback for records created before encrypted connection settings migration.
        $encryptedPassword = base64_decode((string) ($router['password_encrypted'] ?? ''), true);
        if ($encryptedPassword === false) throw new RuntimeException('Kredensial router tidak valid.');
        try {
            return [
                'host' => (string) ($router['host'] ?? parse_url((string) ($router['base_url'] ?? ''), PHP_URL_HOST) ?? ''),
                'port' => (int) ($router['api_port'] ?? parse_url((string) ($router['base_url'] ?? ''), PHP_URL_PORT) ?? 8728),
                'username' => (string) ($router['username'] ?? ''),
                'password' => Services::encrypter()->decrypt($encryptedPassword),
                'ssl' => true,
                'verify_ssl' => (bool) ($router['verify_ssl'] ?? true),
            ];
        } catch (\Throwable $e) {
            throw new RuntimeException('Kredensial router tidak valid.', 0, $e);
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
