<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\IntegrationSetting;
use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use RuntimeException;

class MikrotikProvisioningService
{
    public function testConnection(Router $router): string
    {
        $client = new Client($this->configuration($router));
        $resources = $client->query(new Query('/system/resource/print'))->read();

        return (string) ($resources[0]['version'] ?? 'Koneksi berhasil');
    }

    public function syncPppoe(Customer $customer): void
    {
        if ($customer->service_type !== 'pppoe') {
            throw new RuntimeException('Sinkronisasi router tersedia untuk pelanggan PPPoE.');
        }

        $router = $customer->router;

        if (! $router || ! $router->active || blank($customer->mikrotik_username)) {
            throw new RuntimeException('Pilih router aktif dan isi username PPPoE pelanggan terlebih dahulu.');
        }

        $client = new Client($this->configuration($router));
        $existing = $client->query((new Query('/ppp/secret/print'))->where('name', $customer->mikrotik_username))->read();
        $secret = collect($existing)->first(fn (array $item): bool => ($item['name'] ?? null) === $customer->mikrotik_username);
        $plan = $customer->plan;

        if ($secret) {
            $query = (new Query('/ppp/secret/set'))
                ->equal('.id', $secret['.id'])
                ->equal('service', 'pppoe')
                ->equal('profile', $plan?->hotspot_profile ?: 'default')
                ->equal('comment', 'mynet '.$customer->customer_code);

            if (filled($customer->static_ip)) {
                $query->equal('remote-address', $customer->static_ip);
            }

            if (filled($customer->mikrotik_password)) {
                $query->equal('password', $customer->mikrotik_password);
            }
        } else {
            if (blank($customer->mikrotik_password)) {
                throw new RuntimeException('Password PPPoE wajib diisi untuk membuat akun baru.');
            }

            $query = (new Query('/ppp/secret/add'))
                ->equal('name', $customer->mikrotik_username)
                ->equal('password', $customer->mikrotik_password)
                ->equal('service', 'pppoe')
                ->equal('profile', $plan?->hotspot_profile ?: 'default')
                ->equal('comment', 'mynet '.$customer->customer_code);

            if (filled($customer->static_ip)) {
                $query->equal('remote-address', $customer->static_ip);
            }
        }

        $client->query($query)->read();

        $updated = $client->query((new Query('/ppp/secret/print'))->where('name', $customer->mikrotik_username))->read();
        $updatedSecret = collect($updated)->first(fn (array $item): bool => ($item['name'] ?? null) === $customer->mikrotik_username);

        if ($updatedSecret && isset($updatedSecret['.id'])) {
            $command = $customer->status === 'active' ? '/ppp/secret/enable' : '/ppp/secret/disable';
            $client->query((new Query($command))->equal('.id', $updatedSecret['.id']))->read();
        }
    }

    /** @return array{host: string, user: string, pass: string, port: int, ssl: bool, timeout: int, socket_timeout: int} */
    private function configuration(Router $router): array
    {
        $defaults = IntegrationSetting::query()->where('section', 'mikrotik')->first()?->encrypted_value;
        $defaults = is_array($defaults) ? $defaults : [];
        $host = $router->host ?: ($defaults['host'] ?? null);
        $username = $router->username ?: ($defaults['username'] ?? null);
        $password = $router->password ?: ($defaults['password'] ?? null);

        if (blank($host) || blank($username) || blank($password)) {
            throw new RuntimeException('Host atau kredensial API router belum lengkap.');
        }

        $useDefaults = blank($router->host);

        return [
            'host' => $host,
            'user' => $username,
            'pass' => $password,
            'port' => (int) ($router->api_port ?: ($defaults['port'] ?? ($router->api_ssl ? 8729 : 8728))),
            'ssl' => $useDefaults ? (bool) ($defaults['use_tls'] ?? false) : (bool) $router->api_ssl,
            'timeout' => 5,
            'socket_timeout' => 5,
        ];
    }
}
