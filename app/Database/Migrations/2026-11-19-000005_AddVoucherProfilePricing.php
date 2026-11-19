<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoucherProfilePricing extends Migration
{
    public function up()
    {
        $this->forge->addColumn('hotspot_vouchers', [
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '14,0', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('hotspot_vouchers', 'unit_price');
    }
}
