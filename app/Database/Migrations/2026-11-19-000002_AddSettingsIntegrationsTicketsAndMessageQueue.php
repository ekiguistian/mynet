<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSettingsIntegrationsTicketsAndMessageQueue extends Migration
{
    public function up()
    {
        $this->create('app_settings', [
            'setting_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['setting_key']]);

        $this->create('integration_settings', [
            'section' => ['type' => 'VARCHAR', 'constraint' => 40],
            'encrypted_value' => ['type' => 'TEXT'],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['section']]);

        $this->create('api_keys', [
            'branch_id' => ['type' => 'INTEGER', 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'prefix' => ['type' => 'VARCHAR', 'constraint' => 24],
            'secret_hash' => ['type' => 'VARCHAR', 'constraint' => 64],
            'scopes' => ['type' => 'TEXT'],
            'created_by' => ['type' => 'INTEGER'],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['prefix'], ['secret_hash'], 'branch_id', 'revoked_at']);

        $this->create('message_templates', [
            'code' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'body' => ['type' => 'TEXT'],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['code'], 'active']);

        $this->create('whatsapp_queue', [
            'branch_id' => ['type' => 'INTEGER', 'null' => true],
            'target_group' => ['type' => 'VARCHAR', 'constraint' => 20],
            'target_customer_id' => ['type' => 'INTEGER', 'null' => true],
            'target_user_id' => ['type' => 'INTEGER', 'null' => true],
            'template_id' => ['type' => 'INTEGER', 'null' => true],
            'context_json' => ['type' => 'TEXT', 'null' => true],
            'message' => ['type' => 'TEXT'],
            'scheduled_at' => ['type' => 'DATETIME'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'attempts' => ['type' => 'INTEGER', 'default' => 0],
            'processing_at' => ['type' => 'DATETIME', 'null' => true],
            'last_error' => ['type' => 'TEXT', 'null' => true],
            'sent_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['branch_id', 'status', 'scheduled_at']);

        $this->create('maintenance_tickets', [
            'branch_id' => ['type' => 'INTEGER'],
            'customer_id' => ['type' => 'INTEGER', 'null' => true],
            'ticket_no' => ['type' => 'VARCHAR', 'constraint' => 50],
            'title' => ['type' => 'VARCHAR', 'constraint' => 160],
            'category' => ['type' => 'VARCHAR', 'constraint' => 40],
            'description' => ['type' => 'TEXT'],
            'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'assigned_to' => ['type' => 'INTEGER', 'null' => true],
            'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
            'resolution' => ['type' => 'TEXT', 'null' => true],
            'opened_by' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['ticket_no'], 'branch_id', 'customer_id', 'assigned_to', 'status', 'priority']);

        $this->create('ticket_updates', [
            'ticket_id' => ['type' => 'INTEGER'],
            'user_id' => ['type' => 'INTEGER'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'comment' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['ticket_id', 'user_id', 'created_at']);
    }

    public function down()
    {
        foreach (['ticket_updates', 'maintenance_tickets', 'whatsapp_queue', 'message_templates', 'api_keys', 'integration_settings', 'app_settings'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function create(string $table, array $fields, array $indexes = []): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'unsigned' => true, 'auto_increment' => true],
        ] + $fields);
        $this->forge->addKey('id', true);
        foreach ($indexes as $index) {
            if (is_array($index)) $this->forge->addUniqueKey($index);
            else $this->forge->addKey($index);
        }
        $this->forge->createTable($table, true);
    }
}
