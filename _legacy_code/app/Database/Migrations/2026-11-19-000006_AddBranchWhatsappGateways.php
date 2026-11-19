<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBranchWhatsappGateways extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
            'branch_id' => ['type' => 'INTEGER'],
            'encrypted_value' => ['type' => 'TEXT'],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('branch_id');
        $this->forge->createTable('branch_whatsapp_integrations', true);
    }

    public function down()
    {
        $this->forge->dropTable('branch_whatsapp_integrations', true);
    }
}
