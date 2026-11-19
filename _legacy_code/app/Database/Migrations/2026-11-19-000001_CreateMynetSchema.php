<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMynetSchema extends Migration
{
    public function up()
    {
        $this->create('branches', [
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'code' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'address' => ['type' => 'TEXT', 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['code']]);

        $this->create('plans', [
            'branch_id' => ['type' => 'INTEGER', 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'service_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pppoe'],
            'speed_download' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'speed_upload' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'monthly_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'hotspot_profile' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['branch_id', 'name']);

        $this->create('users', [
            'branch_id' => ['type' => 'INTEGER', 'null' => true],
            'customer_id' => ['type' => 'INTEGER', 'null' => true],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'username' => ['type' => 'VARCHAR', 'constraint' => 80],
            'email' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'admin'],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'force_password_change' => ['type' => 'INTEGER', 'default' => 0],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['username'], ['email'], 'branch_id']);

        $this->create('customers', [
            'branch_id' => ['type' => 'INTEGER'],
            'plan_id' => ['type' => 'INTEGER', 'null' => true],
            'customer_code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'full_name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'email' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'address' => ['type' => 'TEXT', 'null' => true],
            'service_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pppoe'],
            'mikrotik_username' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'installed_at' => ['type' => 'DATE', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['customer_code'], 'branch_id', 'status']);

        $this->create('routers', [
            'branch_id' => ['type' => 'INTEGER'],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'base_url' => ['type' => 'VARCHAR', 'constraint' => 255],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'password_encrypted' => ['type' => 'TEXT'],
            'verify_ssl' => ['type' => 'INTEGER', 'default' => 1],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['branch_id', 'name']);

        $this->create('products', [
            'branch_id' => ['type' => 'INTEGER'],
            'sku' => ['type' => 'VARCHAR', 'constraint' => 60],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'unit' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pcs'],
            'stock_qty' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'minimum_stock' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'cost_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'sale_price' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['branch_id', 'sku']]);

        $this->create('inventory_movements', [
            'branch_id' => ['type' => 'INTEGER'],
            'product_id' => ['type' => 'INTEGER'],
            'direction' => ['type' => 'VARCHAR', 'constraint' => 10],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'user_id' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['branch_id', 'product_id', 'created_at']);

        $this->create('invoices', [
            'branch_id' => ['type' => 'INTEGER'],
            'customer_id' => ['type' => 'INTEGER'],
            'invoice_no' => ['type' => 'VARCHAR', 'constraint' => 50],
            'period' => ['type' => 'VARCHAR', 'constraint' => 7],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'discount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'unpaid'],
            'due_date' => ['type' => 'DATE'],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['invoice_no'], ['customer_id', 'period'], 'branch_id', 'status']);

        $this->create('payments', [
            'branch_id' => ['type' => 'INTEGER'],
            'invoice_id' => ['type' => 'INTEGER'],
            'customer_id' => ['type' => 'INTEGER'],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'manual'],
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'external_reference' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'checkout_url' => ['type' => 'TEXT', 'null' => true],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'raw_payload' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['external_reference'], 'invoice_id', 'branch_id', 'status']);

        $this->create('finance_entries', [
            'branch_id' => ['type' => 'INTEGER'],
            'entry_type' => ['type' => 'VARCHAR', 'constraint' => 10],
            'category' => ['type' => 'VARCHAR', 'constraint' => 80],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'transaction_date' => ['type' => 'DATE'],
            'user_id' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['branch_id', 'entry_type', 'transaction_date']);

        $this->create('attendances', [
            'branch_id' => ['type' => 'INTEGER'],
            'user_id' => ['type' => 'INTEGER'],
            'work_date' => ['type' => 'DATE'],
            'check_in' => ['type' => 'DATETIME', 'null' => true],
            'check_out' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'present'],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['user_id', 'work_date'], 'branch_id']);

        $this->create('work_orders', [
            'branch_id' => ['type' => 'INTEGER'],
            'customer_id' => ['type' => 'INTEGER', 'null' => true],
            'technician_id' => ['type' => 'INTEGER', 'null' => true],
            'sales_user_id' => ['type' => 'INTEGER', 'null' => true],
            'work_order_no' => ['type' => 'VARCHAR', 'constraint' => 50],
            'title' => ['type' => 'VARCHAR', 'constraint' => 160],
            'description' => ['type' => 'TEXT', 'null' => true],
            'billable_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['work_order_no'], 'branch_id', 'status']);

        $this->create('earning_rules', [
            'branch_id' => ['type' => 'INTEGER', 'null' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 30],
            'calculation_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'fixed'],
            'value' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['branch_id', 'role', 'active']);

        $this->create('staff_earnings', [
            'branch_id' => ['type' => 'INTEGER'],
            'user_id' => ['type' => 'INTEGER'],
            'work_order_id' => ['type' => 'INTEGER'],
            'earning_type' => ['type' => 'VARCHAR', 'constraint' => 20],
            'period' => ['type' => 'VARCHAR', 'constraint' => 7],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'unpaid'],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['user_id', 'work_order_id', 'earning_type'], 'branch_id', 'period', 'status']);

        $this->create('hotspot_vouchers', [
            'branch_id' => ['type' => 'INTEGER'],
            'router_id' => ['type' => 'INTEGER', 'null' => true],
            'profile' => ['type' => 'VARCHAR', 'constraint' => 80],
            'username' => ['type' => 'VARCHAR', 'constraint' => 100],
            'password' => ['type' => 'VARCHAR', 'constraint' => 100],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ready'],
            'generated_by' => ['type' => 'INTEGER'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['username'], 'branch_id', 'router_id', 'status']);

        $this->create('webhook_events', [
            'provider' => ['type' => 'VARCHAR', 'constraint' => 20],
            'event_key' => ['type' => 'VARCHAR', 'constraint' => 160],
            'payload_hash' => ['type' => 'VARCHAR', 'constraint' => 64],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['provider', 'event_key']]);

        $this->create('message_logs', [
            'customer_id' => ['type' => 'INTEGER', 'null' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 20],
            'recipient' => ['type' => 'VARCHAR', 'constraint' => 30],
            'message' => ['type' => 'TEXT'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'queued'],
            'provider_reference' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], ['customer_id', 'status', 'created_at']);
    }

    public function down()
    {
        foreach ([
            'message_logs', 'webhook_events', 'hotspot_vouchers', 'staff_earnings', 'earning_rules',
            'work_orders', 'attendances', 'finance_entries', 'payments', 'invoices', 'inventory_movements',
            'products', 'routers', 'customers', 'users', 'plans', 'branches',
        ] as $table) {
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
            if (is_array($index)) {
                $this->forge->addUniqueKey($index);
            } else {
                $this->forge->addKey($index);
            }
        }

        $this->forge->createTable($table, true);
    }
}
