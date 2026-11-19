<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProvisionInitialSuperadmin extends Migration
{
    private const INITIAL_USERNAME = 'mynet';
    private const INITIAL_PASSWORD_HASH = '$2y$12$BPxj4BqErw7EQHI284OBUetTnxTzpgbj26J59Tb2G7knE63YYHLCi';

    public function up()
    {
        $users = $this->db->table('users');
        $account = $users->where('username', self::INITIAL_USERNAME)->get()->getRowArray();

        if (! $account) {
            $account = $this->db->table('users')
                ->where('username', 'administrator@mynet.local')
                ->get()->getRowArray();
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'branch_id' => null,
            'customer_id' => null,
            'full_name' => 'Administrator Mynet',
            'username' => self::INITIAL_USERNAME,
            'password_hash' => self::INITIAL_PASSWORD_HASH,
            'role' => 'superadmin',
            'active' => 1,
            'force_password_change' => 1,
            'updated_at' => $now,
        ];

        if ($account) {
            $this->db->table('users')->where('id', $account['id'])->update($data);
            return;
        }

        $data['created_at'] = $now;
        $this->db->table('users')->insert($data);
    }

    public function down()
    {
        // Keep the provisioned account when rolling back unrelated schema changes.
    }
}
