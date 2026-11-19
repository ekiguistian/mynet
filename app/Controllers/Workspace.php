<?php

namespace App\Controllers;

use Config\Services;

class Workspace extends BaseController
{
    private array $modules = [
        'branches' => ['title' => 'Cabang / area', 'table' => 'branches', 'roles' => ['superadmin', 'administrator'], 'create' => ['superadmin', 'administrator'], 'columns' => ['name' => 'Nama cabang', 'code' => 'Kode', 'phone' => 'Telepon', 'status' => 'Status'], 'fields' => ['name' => ['Nama cabang', 'text', true], 'code' => ['Kode cabang', 'text', true], 'phone' => ['Telepon', 'text', false], 'address' => ['Alamat', 'textarea', false]]],
        'plans' => ['title' => 'Paket layanan', 'table' => 'plans', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => ['superadmin', 'administrator', 'admin'], 'columns' => ['name' => 'Nama paket', 'service_type' => 'Jenis', 'speed_download' => 'Unduh', 'speed_upload' => 'Unggah', 'monthly_price' => 'Tarif / bulan', 'active' => 'Aktif'], 'fields' => ['name' => ['Nama paket', 'text', true], 'service_type' => ['Jenis layanan', 'select:pppoe,hotspot', true], 'speed_download' => ['Kecepatan unduh', 'text', false], 'speed_upload' => ['Kecepatan unggah', 'text', false], 'monthly_price' => ['Tarif bulanan (Rp)', 'number', true], 'hotspot_profile' => ['Profil hotspot MikroTik', 'text', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'customers' => ['title' => 'Pelanggan', 'table' => 'customers', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'noc', 'keuangan', 'sales', 'pelanggan'], 'create' => ['superadmin', 'administrator', 'admin', 'sales'], 'columns' => ['customer_code' => 'ID pelanggan', 'full_name' => 'Nama', 'phone' => 'WhatsApp', 'plan_name' => 'Paket', 'service_type' => 'Jenis', 'status' => 'Status'], 'fields' => ['full_name' => ['Nama lengkap', 'text', true], 'phone' => ['Nomor WhatsApp', 'text', false], 'email' => ['Email', 'email', false], 'address' => ['Alamat pemasangan', 'textarea', false], 'plan_id' => ['Paket layanan', 'options:plans', true], 'service_type' => ['Jenis layanan', 'select:pppoe,hotspot', true], 'mikrotik_username' => ['Username PPPoE / hotspot', 'text', false], 'installed_at' => ['Tanggal aktif', 'date', false], 'notes' => ['Catatan', 'textarea', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'routers' => ['title' => 'Router MikroTik', 'table' => 'routers', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => ['superadmin', 'administrator', 'admin'], 'columns' => ['name' => 'Nama', 'base_url' => 'Alamat REST API', 'username' => 'Pengguna API', 'verify_ssl' => 'Verifikasi TLS', 'active' => 'Aktif'], 'fields' => ['name' => ['Nama router', 'text', true], 'base_url' => ['URL router (HTTPS)', 'url', true], 'username' => ['Pengguna API', 'text', true], 'password' => ['Kata sandi API', 'password', true], 'verify_ssl' => ['Verifikasi sertifikat TLS', 'select:1,0', true], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'products' => ['title' => 'Stok barang', 'table' => 'products', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'keuangan'], 'create' => ['superadmin', 'administrator', 'admin', 'keuangan'], 'columns' => ['sku' => 'SKU', 'name' => 'Barang', 'stock_qty' => 'Stok', 'minimum_stock' => 'Batas minimum', 'cost_price' => 'Harga modal', 'sale_price' => 'Harga jual'], 'fields' => ['sku' => ['SKU', 'text', true], 'name' => ['Nama barang', 'text', true], 'unit' => ['Satuan', 'text', true], 'minimum_stock' => ['Batas stok minimum', 'number', false], 'cost_price' => ['Harga modal per satuan', 'number', false], 'sale_price' => ['Harga jual', 'number', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'invoices' => ['title' => 'Tagihan pelanggan', 'table' => 'invoices', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan', 'pelanggan'], 'create' => [], 'columns' => ['invoice_no' => 'No. tagihan', 'customer_name' => 'Pelanggan', 'period' => 'Periode', 'total' => 'Total', 'due_date' => 'Jatuh tempo', 'status' => 'Status']],
        'payments' => ['title' => 'Pembayaran', 'table' => 'payments', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan', 'pelanggan'], 'create' => [], 'columns' => ['external_reference' => 'Referensi', 'customer_name' => 'Pelanggan', 'provider' => 'Gateway', 'amount' => 'Jumlah', 'status' => 'Status', 'paid_at' => 'Waktu bayar']],
        'finance' => ['title' => 'Buku kas', 'table' => 'finance_entries', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan'], 'create' => ['superadmin', 'administrator', 'admin', 'keuangan'], 'columns' => ['transaction_date' => 'Tanggal', 'entry_type' => 'Jenis', 'category' => 'Kategori', 'amount' => 'Jumlah', 'description' => 'Keterangan'], 'fields' => ['transaction_date' => ['Tanggal transaksi', 'date', true], 'category' => ['Kategori biaya', 'text', true], 'amount' => ['Jumlah (Rp)', 'number', true], 'description' => ['Keterangan', 'textarea', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'attendance' => ['title' => 'Absensi tim', 'table' => 'attendances', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'keuangan', 'noc', 'sales'], 'create' => [], 'columns' => ['user_name' => 'Nama', 'work_date' => 'Tanggal', 'check_in' => 'Masuk', 'check_out' => 'Pulang', 'status' => 'Status']],
        'work-orders' => ['title' => 'Tugas teknisi & sales', 'table' => 'work_orders', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'noc', 'sales'], 'create' => ['superadmin', 'administrator', 'admin', 'noc', 'sales'], 'columns' => ['work_order_no' => 'No. tugas', 'title' => 'Pekerjaan', 'customer_name' => 'Pelanggan', 'technician_name' => 'Teknisi', 'sales_name' => 'Sales', 'billable_amount' => 'Nilai', 'status' => 'Status'], 'fields' => ['title' => ['Judul pekerjaan', 'text', true], 'customer_id' => ['Pelanggan (opsional)', 'options:customers', false], 'technician_id' => ['Teknisi (opsional)', 'options:technicians', false], 'sales_user_id' => ['Sales (opsional)', 'options:sales', false], 'billable_amount' => ['Nilai pekerjaan (Rp)', 'number', false], 'scheduled_at' => ['Jadwal', 'datetime-local', false], 'description' => ['Rincian pekerjaan', 'textarea', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'earnings' => ['title' => 'Bonus teknisi & sales fee', 'table' => 'staff_earnings', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan', 'teknisi', 'sales'], 'create' => [], 'columns' => ['user_name' => 'Penerima', 'earning_type' => 'Jenis fee', 'period' => 'Periode', 'amount' => 'Nilai', 'status' => 'Status', 'work_order_no' => 'Pekerjaan']],
        'earning-rules' => ['title' => 'Aturan bonus & sales fee', 'table' => 'earning_rules', 'roles' => ['superadmin', 'administrator', 'admin'], 'create' => ['superadmin', 'administrator', 'admin'], 'columns' => ['role' => 'Peran penerima', 'calculation_type' => 'Perhitungan', 'value' => 'Nilai', 'active' => 'Aktif'], 'fields' => ['role' => ['Peran penerima', 'select:teknisi,sales', true], 'calculation_type' => ['Metode hitung', 'select:fixed,percent', true], 'value' => ['Nominal rupiah atau persentase', 'number', true], 'branch_id' => ['Cabang (kosong = semua cabang)', 'options:branches', false]]],
        'vouchers' => ['title' => 'Voucher hotspot', 'table' => 'hotspot_vouchers', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'noc'], 'create' => [], 'columns' => ['username' => 'Username', 'password' => 'Kata sandi', 'profile' => 'Profil', 'status' => 'Status', 'created_at' => 'Dibuat']],
            'users' => ['title' => 'Pengguna & peran', 'table' => 'users', 'roles' => ['superadmin', 'administrator', 'admin'], 'create' => ['superadmin', 'administrator', 'admin'], 'columns' => ['full_name' => 'Nama', 'username' => 'Username', 'role' => 'Peran', 'branch_name' => 'Cabang', 'active' => 'Aktif'], 'fields' => ['full_name' => ['Nama lengkap', 'text', true], 'username' => ['Username / email login', 'text', true], 'email' => ['Email pemulihan', 'email', false], 'phone' => ['Telepon', 'text', false], 'role' => ['Peran', 'select:admin,teknisi,keuangan,noc,sales,pelanggan,administrator', true], 'customer_id' => ['Profil pelanggan (untuk akun pelanggan)', 'options:customers', false], 'password' => ['Kata sandi awal (min. 12 karakter)', 'password', true], 'branch_id' => ['Cabang', 'options:branches', false]]],
    ];

    public function index(string $slug)
    {
        $module = $this->module($slug);
        if ($slug === 'products' && mynet_user('role') === 'teknisi') {
            unset($module['columns']['cost_price'], $module['columns']['sale_price']);
        }
        if ($slug === 'work-orders' && ! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'keuangan'], true)) {
            unset($module['columns']['billable_amount']);
        }
        if ($denied = $this->authorize($module['roles'])) return $denied;
        $builder = $this->queryFor($slug, $module);
        $rows = $builder->orderBy($module['table'] . '.id', 'DESC')->limit(100)->get()->getResultArray();
        if ($slug === 'invoices') {
            foreach ($rows as &$row) {
                if (in_array($row['status'], ['unpaid', 'partial'], true) && $row['due_date'] < date('Y-m-d')) $row['status'] = 'overdue';
            }
            unset($row);
        }
        $links = [];
        if ($slug === 'invoices') {
            $links[] = ['text' => 'Generate tagihan bulan ini', 'action' => '/billing/generate', 'label' => 'Generate tagihan'];
        }
        if ($slug === 'attendance') {
            $links[] = ['text' => 'Catat masuk / pulang hari ini', 'action' => '/attendance/toggle', 'label' => 'Absensi sekarang'];
        }
        if ($slug === 'vouchers') {
            $links[] = ['text' => 'Buat username acak dan sinkronkan ke RouterOS v7.', 'action' => '/vouchers/generate', 'label' => 'Generate voucher'];
        }
        if ($slug === 'products') {
            $links[] = ['text' => 'Catat stok masuk atau pemakaian barang.', 'action' => null, 'label' => 'Atur stok'];
        }
        $openInvoices = [];
        if ($slug === 'payments') {
            $open = db_connect()->table('invoices')->whereIn('status', ['unpaid', 'partial', 'overdue']);
            if (mynet_user('branch_id')) $open->where('branch_id', mynet_user('branch_id'));
            if (mynet_user('role') === 'pelanggan') $open->where('customer_id', mynet_user('customer_id') ?? 0);
            $openInvoices = $open->orderBy('due_date')->limit(100)->get()->getResultArray();
        }
        $routers = [];
        if ($slug === 'vouchers') {
            $routerQuery = db_connect()->table('routers')->select('id, name')->where('active', 1);
            if (mynet_user('branch_id')) $routerQuery->where('branch_id', mynet_user('branch_id'));
            $routers = $routerQuery->orderBy('name')->get()->getResultArray();
        }

        return view('workspace/index', [
            'title' => $module['title'], 'module' => $slug, 'definition' => $module,
            'rows' => $rows, 'menus' => mynet_menus(mynet_user('role')),
            'canCreate' => in_array(mynet_user('role'), $module['create'], true),
            'actions' => $links, 'openInvoices' => $openInvoices, 'routers' => $routers,
        ]);
    }

    public function newForm(string $slug)
    {
        $module = $this->module($slug);
        if ($denied = $this->authorize($module['create'])) return $denied;
        return view('workspace/form', [
            'title' => 'Tambah ' . strtolower($module['title']), 'module' => $slug,
            'definition' => $module, 'fields' => $this->fields($module),
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function store(string $slug)
    {
        $module = $this->module($slug);
        if ($denied = $this->authorize($module['create'])) return $denied;
        $posted = $this->request->getPost();
        $data = [];
        foreach ($module['fields'] ?? [] as $key => $field) {
            $value = $posted[$key] ?? null;
            if ($field[2] && ($value === null || trim((string) $value) === '')) {
                return redirect()->back()->withInput()->with('error', $field[0] . ' wajib diisi.');
            }
            if ($value !== null && $value !== '') $data[$key] = is_string($value) ? trim($value) : $value;
        }

        $user = session('user');
        $branchId = $this->selectedBranch($posted);
        if (in_array($slug, ['plans', 'customers', 'routers', 'products', 'finance', 'work-orders'], true)) $data['branch_id'] = $branchId;
        if ($slug === 'earning-rules') $data['branch_id'] = mynet_user('branch_id') ?: (! empty($posted['branch_id']) ? (int) $posted['branch_id'] : null);
        if ($slug === 'customers') {
            $data['customer_code'] = 'C' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
            $data['status'] = 'active';
        } elseif ($slug === 'products') {
            $data['stock_qty'] = 0;
            $data['active'] = 1;
        } elseif ($slug === 'finance') {
            $data['entry_type'] = 'expense';
            $data['user_id'] = $user['id'];
        } elseif ($slug === 'routers') {
            $data['password_encrypted'] = base64_encode(Services::encrypter()->encrypt((string) ($posted['password'] ?? '')));
            unset($data['password']);
            $data['active'] = 1;
        } elseif ($slug === 'users') {
            if (strlen((string) $posted['password']) < 12) return redirect()->back()->withInput()->with('error', 'Kata sandi awal minimal 12 karakter.');
            $data['password_hash'] = password_hash((string) $posted['password'], PASSWORD_DEFAULT);
            $data['force_password_change'] = 1;
            $data['active'] = 1;
            unset($data['password']);
            if (mynet_user('role') === 'admin') $data['role'] = 'admin';
            if ($data['role'] === 'administrator' && mynet_user('role') !== 'superadmin') return redirect()->back()->with('error', 'Hanya administrator utama yang dapat membuat administrator.');
            if ($data['role'] === 'pelanggan' && empty($data['customer_id'])) return redirect()->back()->withInput()->with('error', 'Pilih profil pelanggan untuk akun pelanggan.');
            if ($data['role'] !== 'pelanggan') $data['customer_id'] = null;
            $data['branch_id'] = mynet_user('branch_id') ?: (! empty($data['branch_id']) ? (int) $data['branch_id'] : null);
            if ($data['role'] === 'pelanggan') {
                $customer = db_connect()->table('customers')->where('id', $data['customer_id'])->get()->getRowArray();
                if (! $customer || ! $this->inBranch($customer['branch_id'])) return redirect()->back()->withInput()->with('error', 'Profil pelanggan tidak ditemukan di cabang Anda.');
                $data['branch_id'] = $customer['branch_id'];
            }
        } elseif ($slug === 'work-orders') {
            $data['work_order_no'] = 'WO-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $data['created_by'] = $user['id'];
            $data['status'] = 'open';
        }

        if (! empty($data['branch_id']) && ! db_connect()->table('branches')->where('id', $data['branch_id'])->where('status', 'active')->countAllResults()) {
            return redirect()->back()->withInput()->with('error', 'Cabang tidak ditemukan atau sedang nonaktif.');
        }
        if ($slug === 'customers') {
            $plan = db_connect()->table('plans')->where('id', (int) $data['plan_id'])->where('active', 1)->get()->getRowArray();
            if (! $plan || (! empty($plan['branch_id']) && (int) $plan['branch_id'] !== (int) $data['branch_id'])) return redirect()->back()->withInput()->with('error', 'Paket tidak aktif atau berada di cabang lain.');
        }
        if ($slug === 'work-orders') {
            if (! empty($data['customer_id'])) {
                $customer = db_connect()->table('customers')->where('id', (int) $data['customer_id'])->where('branch_id', $data['branch_id'])->countAllResults();
                if (! $customer) return redirect()->back()->withInput()->with('error', 'Pelanggan tidak ditemukan pada cabang tugas.');
            }
            foreach (['technician_id' => 'teknisi', 'sales_user_id' => 'sales'] as $field => $role) {
                if (empty($data[$field])) continue;
                $assignee = db_connect()->table('users')->where('id', (int) $data[$field])->where('branch_id', $data['branch_id'])->where('role', $role)->where('active', 1)->countAllResults();
                if (! $assignee) return redirect()->back()->withInput()->with('error', 'Penanggung jawab tidak aktif atau berada di cabang lain.');
            }
            if (! empty($data['scheduled_at'])) {
                $data['scheduled_at'] = str_replace('T', ' ', (string) $data['scheduled_at']);
                if (strlen($data['scheduled_at']) === 16) $data['scheduled_at'] .= ':00';
            }
        }

        if (array_key_exists('transaction_date', $data) && empty($data['transaction_date'])) $data['transaction_date'] = date('Y-m-d');
        if ($slug === 'finance' && empty($data['transaction_date'])) $data['transaction_date'] = date('Y-m-d');
        $data['created_at'] = date('Y-m-d H:i:s');
        if (in_array($slug, ['branches', 'plans', 'customers', 'routers', 'products', 'users', 'work-orders', 'earning-rules'], true)) $data['updated_at'] = date('Y-m-d H:i:s');

        db_connect()->table($module['table'])->insert($data);
        return redirect()->to('/area/' . $slug)->with('success', $module['title'] . ' berhasil disimpan.');
    }

    public function action(string $slug)
    {
        if (in_array($slug, ['customers', 'plans', 'products', 'users', 'branches', 'earning-rules'], true) && $this->request->getPost('id') !== null) {
            $allowed = match ($slug) {
                'customers' => ['superadmin', 'administrator', 'admin', 'sales'],
                'plans', 'branches', 'users', 'earning-rules' => ['superadmin', 'administrator', 'admin'],
                'products' => ['superadmin', 'administrator', 'admin', 'keuangan'],
                default => [],
            };
            if ($denied = $this->authorize($allowed)) return $denied;
            $table = $this->modules[$slug]['table'];
            $record = db_connect()->table($table)->where('id', (int) $this->request->getPost('id'))->get()->getRowArray();
            if (! $record || (isset($record['branch_id']) && ! $this->inBranch($record['branch_id']))) return redirect()->back()->with('error', 'Data tidak ditemukan.');
            $field = in_array($slug, ['customers', 'branches'], true) ? 'status' : 'active';
            $current = $record[$field] ?? ($field === 'status' ? 'active' : 1);
            $next = $field === 'status' ? ($current === 'active' ? ($slug === 'customers' ? 'suspended' : 'inactive') : 'active') : (empty($current) ? 1 : 0);
            db_connect()->table($table)->where('id', $record['id'])->update([$field => $next, 'updated_at' => date('Y-m-d H:i:s')]);
            return redirect()->back()->with('success', 'Status data berhasil diperbarui.');
        }
        if ($slug === 'products') {
            if ($denied = $this->authorize(['superadmin', 'administrator', 'admin', 'keuangan', 'teknisi'])) return $denied;
            return $this->adjustStock();
        }
        if ($slug === 'payments') {
            if ($denied = $this->authorize(['superadmin', 'administrator', 'admin', 'keuangan'])) return $denied;
            return $this->manualPayment();
        }
        if ($slug === 'earnings') {
            if ($denied = $this->authorize(['superadmin', 'administrator', 'admin', 'keuangan'])) return $denied;
            $earning = db_connect()->table('staff_earnings')->where('id', (int) $this->request->getPost('earning_id'))->get()->getRowArray();
            if (! $earning || $earning['status'] !== 'unpaid' || ! $this->inBranch($earning['branch_id'])) return redirect()->back()->with('error', 'Bonus tidak ditemukan atau sudah dibayar.');
            $db = db_connect();
            $db->transStart();
            $db->table('staff_earnings')->where('id', $earning['id'])->where('status', 'unpaid')
                ->update(['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
            if ($db->affectedRows() !== 1) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Bonus sudah dibayar oleh pengguna lain.');
            }
            $db->table('finance_entries')->insert([
                'branch_id' => $earning['branch_id'], 'entry_type' => 'expense', 'category' => $earning['earning_type'],
                'amount' => $earning['amount'], 'description' => 'Pembayaran bonus / fee staf', 'reference' => 'EARNING-' . $earning['id'],
                'transaction_date' => date('Y-m-d'), 'user_id' => mynet_user('id'), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $db->transComplete();
            return redirect()->to('/area/earnings')->with('success', 'Bonus / fee sudah dibayarkan dan dicatat sebagai biaya.');
        }
        return redirect()->back()->with('error', 'Aksi tidak tersedia.');
    }

    private function adjustStock()
    {
        $productId = (int) $this->request->getPost('product_id');
        $qty = (float) $this->request->getPost('quantity');
        $direction = (string) $this->request->getPost('direction');
        $unitCost = (float) $this->request->getPost('unit_cost');
        $db = db_connect();
        $product = $db->table('products')->where('id', $productId)->get()->getRowArray();
        if (! $product || ! $this->inBranch($product['branch_id']) || $qty <= 0 || ! in_array($direction, ['in', 'out'], true)) {
            return redirect()->back()->with('error', 'Periksa barang dan jumlah pergerakan stok.');
        }
        if (mynet_user('role') === 'teknisi' && $direction !== 'out') return redirect()->back()->with('error', 'Teknisi hanya dapat mencatat pemakaian barang.');
        if ($direction === 'in' && $unitCost <= 0) return redirect()->back()->with('error', 'Isi biaya modal satuan agar pembelian tercatat di buku kas.');
        if ($direction === 'out' && $qty > (float) $product['stock_qty']) return redirect()->back()->with('error', 'Stok tidak mencukupi.');

        $db->transStart();
        $stockUpdate = $db->table('products')->set('stock_qty', 'stock_qty ' . ($direction === 'in' ? '+' : '-') . ' ' . $qty, false)
            ->where('id', $productId);
        if ($direction === 'out') $stockUpdate->where('stock_qty >=', $qty);
        $stockUpdate->update(['updated_at' => date('Y-m-d H:i:s')]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Stok berubah atau tidak mencukupi. Muat ulang lalu coba lagi.');
        }
        $db->table('inventory_movements')->insert([
            'branch_id' => $product['branch_id'], 'product_id' => $productId, 'direction' => $direction,
            'quantity' => $qty, 'unit_cost' => $unitCost, 'reason' => trim((string) $this->request->getPost('reason')),
            'user_id' => mynet_user('id'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($direction === 'in' && $unitCost > 0) {
            $db->table('finance_entries')->insert([
                'branch_id' => $product['branch_id'], 'entry_type' => 'expense', 'category' => 'Pembelian stok',
                'amount' => $qty * $unitCost, 'description' => 'Stok masuk: ' . $product['name'], 'reference' => 'STOCK-' . $productId,
                'transaction_date' => date('Y-m-d'), 'user_id' => mynet_user('id'), 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        return redirect()->to('/area/products')->with('success', 'Stok barang berhasil diperbarui.');
    }

    private function manualPayment()
    {
        $db = db_connect();
        $invoiceId = (int) $this->request->getPost('invoice_id');
        $db->transBegin();
        $invoice = $db->query('SELECT * FROM invoices WHERE id = ? FOR UPDATE', [$invoiceId])->getRowArray();
        if (! $invoice || ! $this->inBranch($invoice['branch_id']) || $invoice['status'] === 'paid') {
            $db->transRollback();
            return redirect()->back()->with('error', 'Tagihan tidak tersedia untuk dicatat.');
        }
        $amount = (float) $this->request->getPost('amount');
        $alreadyPaid = (float) $db->table('payments')->selectSum('amount')->where('invoice_id', $invoiceId)->where('status', 'paid')->get()->getRow()->amount;
        if ($amount <= 0 || $amount > (float) $invoice['total'] - $alreadyPaid) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Jumlah pembayaran melebihi sisa tagihan.');
        }
        $db->table('payments')->insert([
            'branch_id' => $invoice['branch_id'], 'invoice_id' => $invoiceId, 'customer_id' => $invoice['customer_id'],
            'provider' => 'manual', 'payment_method' => trim((string) $this->request->getPost('payment_method')) ?: 'Kasir',
            'external_reference' => 'MAN-' . date('ymdHis') . strtoupper(bin2hex(random_bytes(3))), 'amount' => $amount,
            'status' => 'paid', 'paid_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $paid = (float) $db->table('payments')->selectSum('amount')->where('invoice_id', $invoiceId)->where('status', 'paid')->get()->getRow()->amount;
        $status = $paid >= (float) $invoice['total'] ? 'paid' : 'partial';
        $db->table('invoices')->where('id', $invoiceId)->update(['status' => $status, 'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null]);
        $db->table('finance_entries')->insert([
            'branch_id' => $invoice['branch_id'], 'entry_type' => 'income', 'category' => 'Pembayaran tagihan',
            'amount' => $amount, 'description' => 'Pembayaran ' . $invoice['invoice_no'], 'reference' => $invoice['invoice_no'],
            'transaction_date' => date('Y-m-d'), 'user_id' => mynet_user('id'), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transCommit();
        return redirect()->to('/area/payments')->with('success', 'Pembayaran berhasil dicatat.');
    }

    private function queryFor(string $slug, array $module)
    {
        $table = $module['table'];
        $db = db_connect();
        $select = $table . '.*';
        $builder = $db->table($table);
        if ($slug === 'customers') {
            $select .= ', plans.name AS plan_name';
            $builder->join('plans', 'plans.id = customers.plan_id', 'left');
        } elseif ($slug === 'invoices' || $slug === 'payments') {
            $select .= ', customers.full_name AS customer_name';
            $builder->join('customers', 'customers.id = ' . $table . '.customer_id', 'left');
        } elseif ($slug === 'attendance') {
            $select .= ', users.full_name AS user_name';
            $builder->join('users', 'users.id = attendances.user_id', 'left');
        } elseif ($slug === 'work-orders') {
            $select .= ', customers.full_name AS customer_name, tech.full_name AS technician_name, sales.full_name AS sales_name';
            $builder->join('customers', 'customers.id = work_orders.customer_id', 'left')
                ->join('users tech', 'tech.id = work_orders.technician_id', 'left')
                ->join('users sales', 'sales.id = work_orders.sales_user_id', 'left');
        } elseif ($slug === 'earnings') {
            $select .= ', users.full_name AS user_name, work_orders.work_order_no';
            $builder->join('users', 'users.id = staff_earnings.user_id', 'left')
                ->join('work_orders', 'work_orders.id = staff_earnings.work_order_id', 'left');
        } elseif ($slug === 'users') {
            $select = 'users.id, users.branch_id, users.full_name, users.username, users.email, users.phone, users.role, users.active, branches.name AS branch_name';
            $builder->join('branches', 'branches.id = users.branch_id', 'left');
        } elseif ($slug === 'branches') {
            $select .= ", CASE WHEN branches.status = 'active' THEN 1 ELSE 0 END AS active";
        } elseif ($slug === 'products') {
            $select .= ', CASE WHEN ' . $table . '.stock_qty <= ' . $table . '.minimum_stock THEN 1 ELSE 0 END AS low_stock';
        }
        $builder->select($select);

        $user = session('user');
        if ($user['role'] === 'pelanggan') {
            if ($slug === 'customers') $builder->where('customers.id', $user['customer_id'] ?? 0);
            elseif (in_array($slug, ['invoices', 'payments'], true)) $builder->where($table . '.customer_id', $user['customer_id'] ?? 0);
            elseif ($slug === 'work-orders') $builder->where('work_orders.id', 0);
        } elseif (! empty($user['branch_id']) && in_array($slug, ['plans', 'customers', 'routers', 'products', 'invoices', 'payments', 'finance', 'attendance', 'work-orders', 'earnings', 'earning-rules', 'vouchers', 'users'], true)) {
            $builder->where($table . '.branch_id', $user['branch_id']);
        }
        if ($slug === 'work-orders' && $user['role'] === 'teknisi') $builder->where('work_orders.technician_id', $user['id']);
        if ($slug === 'work-orders' && $user['role'] === 'sales') $builder->where('work_orders.sales_user_id', $user['id']);
        if ($slug === 'earnings' && in_array($user['role'], ['teknisi', 'sales'], true)) $builder->where('staff_earnings.user_id', $user['id']);
        if ($slug === 'attendance' && in_array($user['role'], ['teknisi', 'sales', 'noc'], true)) $builder->where('attendances.user_id', $user['id']);
        return $builder;
    }

    private function module(string $slug): array
    {
        if (! isset($this->modules[$slug])) throw new \CodeIgniter\Exceptions\PageNotFoundException('Halaman tidak ditemukan.');
        return $this->modules[$slug];
    }

    private function authorize(array $roles)
    {
        if (! in_array(mynet_user('role'), $roles, true)) return $this->response->setStatusCode(403)->setBody(view('errors/html/error_403'));
        return null;
    }

    private function selectedBranch(array $posted): int
    {
        $user = session('user');
        if (! empty($user['branch_id'])) return (int) $user['branch_id'];
        if (in_array($user['role'], ['superadmin', 'administrator'], true) && ! empty($posted['branch_id'])) return (int) $posted['branch_id'];
        return (int) (db_connect()->table('branches')->where('status', 'active')->orderBy('id')->get()->getRow('id') ?? 1);
    }

    private function inBranch($branchId): bool
    {
        $user = session('user');
        return empty($user['branch_id']) || (int) $user['branch_id'] === (int) $branchId;
    }

    private function fields(array $module): array
    {
        $fields = [];
        foreach ($module['fields'] ?? [] as $name => [$label, $type, $required]) {
            $options = [];
            if (str_starts_with($type, 'options:')) $options = $this->fieldOptions(substr($type, 8));
            $fields[] = compact('name', 'label', 'type', 'required', 'options');
        }
        return $fields;
    }

    private function fieldOptions(string $source): array
    {
        $db = db_connect();
        $query = match ($source) {
            'plans' => $db->table('plans')->select('id, name')->where('active', 1),
            'customers' => $db->table('customers')->select('id, full_name AS name')->where('status', 'active'),
            'technicians' => $db->table('users')->select('id, full_name AS name')->where('active', 1)->where('role', 'teknisi'),
            'sales' => $db->table('users')->select('id, full_name AS name')->where('active', 1)->where('role', 'sales'),
            'branches' => $db->table('branches')->select('id, name')->where('status', 'active'),
            default => null,
        };
        if (! $query) return [];
        $user = session('user');
        if (! empty($user['branch_id'])) {
            if ($source === 'branches') $query->where('id', $user['branch_id']);
            elseif (in_array($source, ['plans', 'customers', 'technicians', 'sales'], true)) $query->where('branch_id', $user['branch_id']);
        }
        return $query->orderBy('name')->get()->getResultArray();
    }
}
