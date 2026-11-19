<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReconcileOnuSerialStock extends Migration
{
    public function up()
    {
        // Available serial records are the authoritative on-hand quantity for ONT/ONU products.
        $this->db->query("UPDATE products SET stock_qty = (SELECT COUNT(*) FROM product_serials WHERE product_id = products.id AND status = 'available') WHERE category = 'ont_onu'");
    }

    public function down()
    {
        // The prior stock quantity is not recoverable after serial reconciliation.
    }
}
