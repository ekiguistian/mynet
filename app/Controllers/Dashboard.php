<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $user = session('user');
        $scope = static function ($builder, string $table) use ($user) {
            $baseTable = $table === 'i' ? 'invoices' : $table;
            if ($user['role'] === 'pelanggan') {
                if ($baseTable === 'customers') $builder->where($table . '.id', $user['customer_id'] ?? 0);
                elseif (in_array($baseTable, ['invoices', 'payments'], true)) $builder->where($table . '.customer_id', $user['customer_id'] ?? 0);
                else $builder->where($table . '.id', 0);
            } elseif (! empty($user['branch_id'])) {
                $builder->where($table . '.branch_id', $user['branch_id']);
            }
            return $builder;
        };

        $customers = $scope($db->table('customers'), 'customers')->where('status', 'active')->countAllResults();
        $canSeeBilling = in_array($user['role'], ['superadmin', 'administrator', 'admin', 'keuangan', 'pelanggan'], true);
        $unpaid = $canSeeBilling ? $scope($db->table('invoices'), 'invoices')->whereIn('status', ['unpaid', 'partial', 'overdue'])->countAllResults() : 0;
        $routers = $scope($db->table('routers'), 'routers')->where('active', 1)->countAllResults();
        $vouchers = $scope($db->table('hotspot_vouchers'), 'hotspot_vouchers')->where('status', 'ready')->countAllResults();
        $workQueueQuery = $scope($db->table('work_orders'), 'work_orders')->whereIn('status', ['open', 'assigned', 'in_progress']);
        if ($user['role'] === 'teknisi') $workQueueQuery->where('technician_id', $user['id']);
        if ($user['role'] === 'sales') $workQueueQuery->where('sales_user_id', $user['id']);
        $workQueue = $workQueueQuery->countAllResults();

        $financeRoles = ['superadmin', 'administrator', 'admin', 'keuangan'];
        $canSeeFinance = in_array($user['role'], $financeRoles, true);
        $incomeQuery = $scope($db->table('finance_entries'), 'finance_entries')
            ->selectSum('amount')->where('entry_type', 'income')->where('transaction_date >=', date('Y-m-01'));
        $income = $canSeeFinance ? (float) ($incomeQuery->get()->getRow()->amount ?? 0) : 0.0;
        $expenseQuery = $scope($db->table('finance_entries'), 'finance_entries')
            ->selectSum('amount')->where('entry_type', 'expense')->where('transaction_date >=', date('Y-m-01'));
        $expenses = $canSeeFinance ? (float) ($expenseQuery->get()->getRow()->amount ?? 0) : 0.0;
        $daysInMonth = (int) date('t');
        $cashflowBars = array_fill(0, 12, ['income' => 0, 'expense' => 0]);
        if ($canSeeFinance) {
            $dailyEntries = $scope($db->table('finance_entries'), 'finance_entries')
                ->select('transaction_date, entry_type, SUM(amount) AS total')
                ->where('transaction_date >=', date('Y-m-01'))->where('transaction_date <=', date('Y-m-d'))
                ->groupBy(['transaction_date', 'entry_type'])->get()->getResultArray();
            foreach ($dailyEntries as $entry) {
                $day = (int) date('j', strtotime($entry['transaction_date']));
                $bucket = min(11, (int) floor(($day - 1) * 12 / $daysInMonth));
                $key = $entry['entry_type'] === 'income' ? 'income' : 'expense';
                $cashflowBars[$bucket][$key] += (float) $entry['total'];
            }
            $peak = max(1, ...array_map(static fn (array $bar): float => max($bar['income'], $bar['expense']), $cashflowBars));
            foreach ($cashflowBars as &$bar) {
                $bar = [
                    'income' => (int) round($bar['income'] / $peak * 100),
                    'expense' => (int) round($bar['expense'] / $peak * 100),
                ];
            }
            unset($bar);
        }
        $cashflowLabels = [];
        foreach ([0, 2, 4, 6, 8, 10, 11] as $index) {
            $cashflowLabels[] = sprintf('%02d', min($daysInMonth, (int) floor($index * $daysInMonth / 12) + 1));
        }

        $latestInvoices = [];
        if ($canSeeFinance || $user['role'] === 'pelanggan') {
            $invoiceQuery = $scope($db->table('invoices i'), 'i')
                ->select('i.invoice_no, i.period, i.total, i.status, i.due_date, c.full_name AS customer_name')
                ->join('customers c', 'c.id = i.customer_id', 'left');
            if ($user['role'] === 'pelanggan') $invoiceQuery->where('i.customer_id', $user['customer_id'] ?? 0);
            $latestInvoices = $invoiceQuery->orderBy('i.id', 'DESC')->limit(6)->get()->getResultArray();
            foreach ($latestInvoices as &$invoice) {
                if (in_array($invoice['status'], ['unpaid', 'partial'], true) && $invoice['due_date'] < date('Y-m-d')) $invoice['status'] = 'overdue';
            }
            unset($invoice);
        }

        $attendance = null;
        if ($user['role'] !== 'pelanggan') {
            $attendance = $db->table('attendances')->where('user_id', $user['id'])->where('work_date', date('Y-m-d'))->get()->getRowArray();
        }

        return view('dashboard/index', [
            'title' => 'Ringkasan',
            'stats' => [
                ['label' => 'Pelanggan aktif', 'value' => number_format($customers), 'hint' => 'layanan berjalan', 'icon' => '◉', 'tone' => 'blue'],
                ['label' => $canSeeBilling ? 'Tagihan terbuka' : 'Pekerjaan terbuka', 'value' => number_format($canSeeBilling ? $unpaid : $workQueue), 'hint' => $canSeeBilling ? 'perlu ditindaklanjuti' : 'perlu dikerjakan', 'icon' => $canSeeBilling ? '▧' : '⌁', 'tone' => 'orange'],
                ['label' => $canSeeFinance ? 'Pemasukan bulan ini' : 'Voucher tersedia', 'value' => $canSeeFinance ? mynet_money($income) : number_format($vouchers), 'hint' => $canSeeFinance ? 'arus masuk tercatat' : 'siap dibagikan', 'icon' => $canSeeFinance ? '↗' : '▦', 'tone' => 'green'],
                ['label' => 'Stok menipis', 'value' => number_format($this->lowStockCount($user)), 'hint' => 'barang di bawah batas', 'icon' => '◫', 'tone' => 'red'],
            ],
            'income' => $income,
            'expenses' => $expenses,
            'net' => $income - $expenses,
            'readyVouchers' => $vouchers,
            'activeRouters' => $routers,
            'latestInvoices' => $latestInvoices,
            'canSeeFinance' => $canSeeFinance,
            'cashflowBars' => $cashflowBars,
            'cashflowLabels' => $cashflowLabels,
            'attendance' => $attendance,
            'menus' => mynet_menus($user['role']),
        ]);
    }

    private function lowStockCount(array $user): int
    {
        if ($user['role'] === 'pelanggan') return 0;
        $query = db_connect()->table('products')->where('active', 1)->where('stock_qty <= minimum_stock', null, false);
        if (! empty($user['branch_id'])) {
            $query->where('branch_id', $user['branch_id']);
        }
        return $query->countAllResults();
    }
}
