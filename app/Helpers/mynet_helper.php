<?php

if (! function_exists('mynet_user')) {
    function mynet_user(?string $key = null)
    {
        $user = session('user') ?? [];

        return $key === null ? $user : ($user[$key] ?? null);
    }
}

if (! function_exists('mynet_money')) {
    function mynet_money($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}

if (! function_exists('mynet_role_label')) {
    function mynet_role_label(string $role): string
    {
        return [
            'superadmin' => 'Administrator utama', 'administrator' => 'Administrator', 'admin' => 'Admin',
            'teknisi' => 'Teknisi', 'keuangan' => 'Keuangan', 'noc' => 'NOC', 'pelanggan' => 'Pelanggan',
            'sales' => 'Sales',
        ][$role] ?? ucfirst($role);
    }
}

if (! function_exists('mynet_menus')) {
    function mynet_menus(string $role): array
    {
        $all = [
            ['label' => 'Ringkasan', 'icon' => '⌂', 'url' => '/dashboard'],
            ['label' => 'Pelanggan', 'icon' => '◉', 'url' => '/area/customers'],
            ['label' => 'Paket layanan', 'icon' => '▤', 'url' => '/area/plans'],
            ['label' => 'Tagihan', 'icon' => '▧', 'url' => '/area/invoices'],
            ['label' => 'Pembayaran', 'icon' => '⇄', 'url' => '/area/payments'],
            ['label' => 'Stok barang', 'icon' => '◫', 'url' => '/area/products'],
            ['label' => 'Serial ONT/ONU', 'icon' => '▣', 'url' => '/area/serials'],
            ['label' => 'Keuangan', 'icon' => '◒', 'url' => '/area/finance'],
            ['label' => 'Absensi', 'icon' => '◷', 'url' => '/area/attendance'],
            ['label' => 'Tugas teknisi', 'icon' => '⌁', 'url' => '/area/work-orders'],
            ['label' => 'Bonus & fee', 'icon' => '✳', 'url' => '/area/earnings'],
            ['label' => 'Aturan bonus', 'icon' => '⚙', 'url' => '/area/earning-rules'],
            ['label' => 'Voucher hotspot', 'icon' => '▦', 'url' => '/area/vouchers'],
            ['label' => 'Router MikroTik', 'icon' => '⌘', 'url' => '/area/routers'],
            ['label' => 'ODC', 'icon' => '⌕', 'url' => '/area/odcs'],
            ['label' => 'Jalur / core ODC', 'icon' => '⌁', 'url' => '/area/fiber-cores'],
            ['label' => 'ODP dan port', 'icon' => '⌖', 'url' => '/area/odps'],
            ['label' => 'Pengguna', 'icon' => '♙', 'url' => '/area/users'],
            ['label' => 'Cabang', 'icon' => '⌖', 'url' => '/area/branches'],
            ['label' => 'Tiket maintenance', 'icon' => '▣', 'url' => '/tickets'],
            ['label' => 'Antrean WhatsApp', 'icon' => '◷', 'url' => '/notifications/queue'],
            ['label' => 'Pengaturan aplikasi', 'icon' => '⚙', 'url' => '/settings'],
            ['label' => 'Integrasi API', 'icon' => '⌘', 'url' => '/integrations'],
            ['label' => 'Template WhatsApp', 'icon' => '✉', 'url' => '/integrations/templates'],
            ['label' => 'API eksternal', 'icon' => '⇄', 'url' => '/settings/api-keys'],
            ['label' => 'Versi aplikasi', 'icon' => '↻', 'url' => '/version'],
        ];

        $allow = [
            'superadmin' => ['*'], 'administrator' => ['*'],
            'admin' => ['Pelanggan', 'Paket layanan', 'Tagihan', 'Pembayaran', 'Stok barang', 'Serial ONT/ONU', 'Keuangan', 'Absensi', 'Tugas teknisi', 'Bonus & fee', 'Aturan bonus', 'Voucher hotspot', 'Router MikroTik', 'ODC', 'Jalur / core ODC', 'ODP dan port', 'Pengguna', 'Tiket maintenance', 'Antrean WhatsApp'],
            'teknisi' => ['Pelanggan', 'Absensi', 'Tugas teknisi', 'Voucher hotspot', 'Stok barang', 'Serial ONT/ONU', 'Tiket maintenance'],
            'keuangan' => ['Pelanggan', 'Tagihan', 'Pembayaran', 'Keuangan', 'Bonus & fee', 'Serial ONT/ONU', 'Antrean WhatsApp'],
            'noc' => ['Pelanggan', 'Paket layanan', 'Voucher hotspot', 'Router MikroTik', 'ODC', 'Jalur / core ODC', 'ODP dan port', 'Tugas teknisi', 'Tiket maintenance', 'Antrean WhatsApp'],
            'sales' => ['Pelanggan', 'Tugas teknisi', 'Bonus & fee', 'Tiket maintenance'],
            'pelanggan' => ['Pelanggan', 'Tagihan', 'Pembayaran', 'Tiket maintenance'],
        ];
        $visible = $allow[$role] ?? [];

        return array_values(array_filter($all, static function (array $item) use ($visible): bool {
            return in_array('*', $visible, true) || in_array($item['label'], $visible, true) || $item['url'] === '/dashboard';
        }));
    }
}

if (! function_exists('mynet_setting')) {
    function mynet_setting(string $key, ?string $default = null): ?string
    {
        static $settings = null;
        if ($settings === null) $settings = (new \App\Libraries\AppSettings())->all();
        return $settings[$key] ?? $default;
    }
}
