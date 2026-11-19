<?php

namespace App\Libraries;

class ApiKeyService
{
    private static ?array $current = null;

    public function resolve(string $token): ?array
    {
        if (! str_starts_with($token, 'mynet_live_')) return null;
        $hash = hash('sha256', $token);
        $key = db_connect()->table('api_keys')->where('secret_hash', $hash)->where('revoked_at', null)->get()->getRowArray();
        return $key && hash_equals($key['secret_hash'], $hash) ? $key : null;
    }

    public static function setCurrent(array $key): void
    {
        self::$current = $key;
    }

    public static function current(): ?array
    {
        return self::$current;
    }
}
