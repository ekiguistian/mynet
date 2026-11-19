<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MynetSeeder extends Seeder
{
    public function run()
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');

        if ($db->table('branches')->countAllResults() === 0) {
            $db->table('branches')->insert([
                'name' => 'Kantor Pusat', 'code' => 'PUSAT', 'status' => 'active', 'created_at' => $now,
            ]);
        }
        $branch = $db->table('branches')->orderBy('id')->get()->getRowArray();
        $branchId = (int) $branch['id'];

        if ($db->table('plans')->countAllResults() === 0) {
            $db->table('plans')->insertBatch([
                ['branch_id' => $branchId, 'name' => 'Home 20 Mbps', 'service_type' => 'pppoe', 'speed_download' => '20 Mbps', 'speed_upload' => '10 Mbps', 'monthly_price' => 185000, 'active' => 1, 'created_at' => $now],
                ['branch_id' => $branchId, 'name' => 'Home 50 Mbps', 'service_type' => 'pppoe', 'speed_download' => '50 Mbps', 'speed_upload' => '25 Mbps', 'monthly_price' => 275000, 'active' => 1, 'created_at' => $now],
                ['branch_id' => $branchId, 'name' => 'Hotspot Harian', 'service_type' => 'hotspot', 'speed_download' => '10 Mbps', 'speed_upload' => '5 Mbps', 'monthly_price' => 10000, 'hotspot_profile' => 'default', 'active' => 1, 'created_at' => $now],
            ]);
        }

        if ($db->table('earning_rules')->countAllResults() === 0) {
            $db->table('earning_rules')->insertBatch([
                ['branch_id' => $branchId, 'role' => 'teknisi', 'calculation_type' => 'fixed', 'value' => 25000, 'active' => 1, 'created_at' => $now],
                ['branch_id' => $branchId, 'role' => 'sales', 'calculation_type' => 'percent', 'value' => 5, 'active' => 1, 'created_at' => $now],
            ]);
        }

        if ($db->table('message_templates')->countAllResults() === 0) {
            $db->table('message_templates')->insertBatch([
                ['code' => 'maintenance_new', 'name' => 'Tiket maintenance baru', 'body' => 'Tiket {{nomor_tiket}} baru dari {{nama}} masuk ke antrean. Mohon ditinjau.', 'active' => 1, 'created_at' => $now],
                ['code' => 'maintenance_assigned', 'name' => 'Penugasan teknisi', 'body' => 'Halo {{nama}}, Anda mendapat tiket maintenance {{nomor_tiket}}. Silakan periksa antrean tugas.', 'active' => 1, 'created_at' => $now],
                ['code' => 'maintenance_update', 'name' => 'Pembaruan tiket pelanggan', 'body' => 'Halo {{nama}}, status tiket maintenance {{nomor_tiket}} diperbarui menjadi {{status}}.', 'active' => 1, 'created_at' => $now],
                ['code' => 'invoice_reminder', 'name' => 'Pengingat tagihan', 'body' => 'Halo {{nama}}, tagihan {{nomor_tagihan}} periode {{periode}} sebesar {{total}} menunggu pembayaran.', 'active' => 1, 'created_at' => $now],
            ]);
        }

        $username = 'administrator@mynet.local';
        if (! $db->table('users')->where('username', $username)->countAllResults()) {
            $seedPassword = (string) env('MYNET_SEED_ADMIN_PASSWORD');
            if (strlen($seedPassword) < 12) {
                throw new \RuntimeException('Atur MYNET_SEED_ADMIN_PASSWORD minimal 12 karakter sebelum menjalankan seeder.');
            }
            $db->table('users')->insert([
                'branch_id' => null,
                'full_name' => 'Administrator Mynet',
                'username' => $username,
                'password_hash' => password_hash($seedPassword, PASSWORD_DEFAULT),
                'role' => 'superadmin',
                'active' => 1,
                'force_password_change' => 1,
                'created_at' => $now,
            ]);
        }
    }
}
