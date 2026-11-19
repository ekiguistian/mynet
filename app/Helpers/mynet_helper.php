<?php

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
            ['label' => 'Keuangan', 'icon' => '◒', 'url' => '/area/finance'],
            ['label' => 'Absensi', 'icon' => '◷', 'url' => '/area/attendance'],
            ['label' => 'Tugas teknisi', 'icon' => '⌁', 'url' => '/area/work-orders'],
            ['label' => 'Bonus & fee', 'icon' => '✳', 'url' => '/area/earnings'],
            ['label' => 'Aturan bonus', 'icon' => '⚙', 'url' => '/area/earning-rules'],
            ['label' => 'Voucher hotspot', 'icon' => '▦', 'url' => '/area/vouchers'],
            ['label' => 'Router MikroTik', 'icon' => '⌘', 'url' => '/area/routers'],
            ['label' => 'Pengguna', 'icon' => '♙', 'url' => '/area/users'],
            ['label' => 'Cabang', 'icon' => '⌖', 'url' => '/area/branches'],
        ];

        $allow = [
            'superadmin' => ['*'], 'administrator' => ['*'],
            'admin' => ['Pelanggan', 'Paket layanan', 'Tagihan', 'Pembayaran', 'Stok barang', 'Keuangan', 'Absensi', 'Tugas teknisi', 'Bonus & fee', 'Aturan bonus', 'Voucher hotspot', 'Router MikroTik', 'Pengguna'],
            'teknisi' => ['Pelanggan', 'Absensi', 'Tugas teknisi', 'Voucher hotspot', 'Stok barang'],
            'keuangan' => ['Pelanggan', 'Tagihan', 'Pembayaran', 'Keuangan', 'Bonus & fee'],
            'noc' => ['Pelanggan', 'Paket layanan', 'Voucher hotspot', 'Router MikroTik', 'Tugas teknisi'],
            'sales' => ['Pelanggan', 'Tugas teknisi', 'Bonus & fee'],
            'pelanggan' => ['Pelanggan', 'Tagihan', 'Pembayaran'],
        ];
        $visible = $allow[$role] ?? [];

        return array_values(array_filter($all, static function (array $item) use ($visible): bool {
            return in_array('*', $visible, true) || in_array($item['label'], $visible, true) || $item['url'] === '/dashboard';
        }));
    }
}
