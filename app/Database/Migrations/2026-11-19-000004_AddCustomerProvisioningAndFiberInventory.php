<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerProvisioningAndFiberInventory extends Migration
{
    public function up()
    {
        $this->forge->addColumn('customers', [
            'sales_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'branch_id'],
            'created_by' => ['type' => 'INTEGER', 'null' => true, 'after' => 'sales_user_id'],
            'connection_mode' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'create'],
            'static_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'mikrotik_router_id' => ['type' => 'INTEGER', 'null' => true],
            'due_day' => ['type' => 'INTEGER', 'default' => 20],
            'auto_billing' => ['type' => 'INTEGER', 'default' => 1],
            'device_serial' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'odp_port_id' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addColumn('products', [
            'category' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'other'],
        ]);

        $this->create('product_serials', [
            'branch_id' => ['type' => 'INTEGER'],
            'product_id' => ['type' => 'INTEGER'],
            'serial_number' => ['type' => 'VARCHAR', 'constraint' => 120],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'available'],
            'customer_id' => ['type' => 'INTEGER', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['serial_number'], 'branch_id', 'product_id', 'status']);

        $this->create('fiber_odcs', [
            'branch_id' => ['type' => 'INTEGER'],
            'code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'location' => ['type' => 'TEXT', 'null' => true],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['branch_id', 'code'], 'branch_id']);

        $this->create('fiber_cores', [
            'odc_id' => ['type' => 'INTEGER'],
            'core_number' => ['type' => 'INTEGER'],
            'color' => ['type' => 'VARCHAR', 'constraint' => 40],
            'route_label' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'available'],
        ], [['odc_id', 'core_number']]);

        $this->create('fiber_odps', [
            'branch_id' => ['type' => 'INTEGER'],
            'odc_id' => ['type' => 'INTEGER'],
            'core_id' => ['type' => 'INTEGER'],
            'code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'location' => ['type' => 'TEXT', 'null' => true],
            'active' => ['type' => 'INTEGER', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ], [['branch_id', 'code'], 'branch_id', 'odc_id', 'core_id']);

        $this->create('fiber_odp_ports', [
            'odp_id' => ['type' => 'INTEGER'],
            'port_number' => ['type' => 'INTEGER'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'available'],
            'customer_id' => ['type' => 'INTEGER', 'null' => true],
        ], [['odp_id', 'port_number'], 'customer_id', 'status']);
    }

    public function down()
    {
        foreach (['fiber_odp_ports', 'fiber_odps', 'fiber_cores', 'fiber_odcs', 'product_serials'] as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->forge->dropColumn('products', ['category']);
        $this->forge->dropColumn('customers', ['sales_user_id', 'created_by', 'connection_mode', 'static_ip', 'mikrotik_router_id', 'due_day', 'auto_billing', 'device_serial', 'odp_port_id']);
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
