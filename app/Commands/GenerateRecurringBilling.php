<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GenerateRecurringBilling extends BaseCommand
{
    protected $group = 'Mynet';
    protected $name = 'billing:generate-recurring';
    protected $description = 'Buat tagihan bulanan otomatis untuk pelanggan yang jatuh tempo hari ini.';
    protected $usage = 'billing:generate-recurring';

    public function run(array $params)
    {
        $db = db_connect();
        $today = date('Y-m-d');
        $period = date('Y-m');
        $customers = $db->table('customers c')->select('c.*, p.monthly_price')->join('plans p', 'p.id = c.plan_id', 'left')
            ->where('c.status', 'active')->where('c.auto_billing', 1)->where('c.due_day', (int) date('j'))->get()->getResultArray();
        $created = 0;
        $db->transStart();
        foreach ($customers as $customer) {
            $exists = $db->table('invoices')->where('customer_id', $customer['id'])->where('period', $period)->countAllResults();
            if ($exists || (float) $customer['monthly_price'] <= 0) continue;
            $invoiceNo = 'INV-' . str_replace('-', '', $period) . '-' . $customer['customer_code'];
            $db->table('invoices')->insert([
                'branch_id' => $customer['branch_id'], 'customer_id' => $customer['id'], 'invoice_no' => $invoiceNo,
                'period' => $period, 'amount' => $customer['monthly_price'], 'discount' => 0, 'total' => $customer['monthly_price'],
                'status' => 'unpaid', 'due_date' => $today, 'created_by' => 0, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }
        $db->transComplete();
        CLI::write('Tagihan otomatis dibuat: ' . $created . ' (' . $period . ', ' . $today . ').', 'green');
    }
}
