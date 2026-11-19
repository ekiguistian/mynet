<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRouterHostAndApiPort extends Migration
{
    public function up()
    {
        $this->forge->addColumn('routers', [
            'host' => ['type' => 'VARCHAR', 'constraint' => 253, 'null' => true],
            'api_port' => ['type' => 'INTEGER', 'null' => true],
        ]);

        foreach ($this->db->table('routers')->select('id, base_url')->get()->getResultArray() as $router) {
            $parts = parse_url((string) $router['base_url']);
            if (! is_array($parts) || empty($parts['host'])) continue;
            $this->db->table('routers')->where('id', $router['id'])->update([
                'host' => $parts['host'], 'api_port' => (int) ($parts['port'] ?? 443),
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('routers', ['host', 'api_port']);
    }
}
