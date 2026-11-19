<?php

namespace App\Controllers;

use Config\Services;

class Workspace extends BaseController
{
    private array $modules = [
        'branches' => ['title' => 'Cabang / area', 'table' => 'branches', 'roles' => ['superadmin', 'administrator'], 'create' => ['superadmin', 'administrator'], 'columns' => ['name' => 'Nama cabang', 'code' => 'Kode', 'phone' => 'Telepon', 'status' => 'Status'], 'fields' => ['name' => ['Nama cabang', 'text', true], 'code' => ['Kode cabang', 'text', true], 'phone' => ['Telepon', 'text', false], 'address' => ['Alamat', 'textarea', false], 'wa_provider' => ['WhatsApp gateway cabang', 'select:ngirimwa,fonnte', true], 'wa_api_key' => ['API key NgirimWA', 'password', false], 'wa_token' => ['Token Fonnte', 'password', false], 'technician_group_id' => ['ID grup teknisi (opsional)', 'text', false], 'finance_group_id' => ['ID grup finance (opsional)', 'text', false], 'admin_group_id' => ['ID grup admin (opsional)', 'text', false]]],
        'odcs' => ['title' => 'ODC', 'table' => 'fiber_odcs', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => ['superadmin', 'administrator', 'admin', 'noc'], 'columns' => ['code' => 'Kode ODC', 'name' => 'Nama ODC', 'branch_name' => 'Cabang', 'active' => 'Aktif'], 'fields' => ['branch_id' => ['Cabang', 'options:branches', true], 'code' => ['Kode ODC', 'text', true], 'name' => ['Nama ODC', 'text', true], 'location' => ['Lokasi', 'textarea', false]]],
        'fiber-cores' => ['title' => 'Jalur / core ODC', 'table' => 'fiber_cores', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => ['superadmin', 'administrator', 'admin', 'noc'], 'columns' => ['odc_name' => 'ODC', 'core_number' => 'Nomor core', 'color' => 'Warna core', 'route_label' => 'Jalur', 'status' => 'Status'], 'fields' => ['odc_id' => ['ODC induk', 'options:odcs', true], 'core_number' => ['Nomor core', 'number', true], 'color' => ['Warna core', 'text', true], 'route_label' => ['Nama jalur', 'text', true]]],
        'odps' => ['title' => 'ODP dan port pelanggan', 'table' => 'fiber_odps', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => ['superadmin', 'administrator', 'admin', 'noc'], 'columns' => ['code' => 'Kode ODP', 'name' => 'Nama ODP', 'odc_name' => 'ODC induk', 'route_label' => 'Jalur / core', 'active' => 'Aktif'], 'fields' => ['branch_id' => ['Cabang', 'options:branches', true], 'odc_id' => ['ODC induk', 'options:odcs', true], 'core_id' => ['Core / jalur', 'options:cores', true], 'code' => ['Kode ODP', 'text', true], 'name' => ['Nama ODP', 'text', true], 'location' => ['Lokasi', 'textarea', false], 'port_count' => ['Jumlah port pelanggan (minimal 8)', 'number', true]]],
        'serials' => ['title' => 'Serial ONT/ONU', 'table' => 'product_serials', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan', 'noc'], 'create' => ['superadmin', 'administrator', 'admin', 'keuangan'], 'columns' => ['serial_number' => 'Serial number', 'product_name' => 'Barang', 'branch_name' => 'Cabang', 'status' => 'Status'], 'fields' => ['branch_id' => ['Cabang', 'options:branches', true], 'product_id' => ['Barang ONT/ONU', 'options:ont_products', true], 'serial_number' => ['Serial number perangkat', 'text', true]]],
        'plans' => ['title' => 'Paket layanan', 'table' => 'plans', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => ['superadmin', 'administrator', 'admin'], 'columns' => ['name' => 'Nama paket', 'service_type' => 'Jenis', 'speed_download' => 'Unduh', 'speed_upload' => 'Unggah', 'monthly_price' => 'Tarif / bulan', 'active' => 'Aktif'], 'fields' => ['name' => ['Nama paket', 'text', true], 'service_type' => ['Jenis layanan', 'select:pppoe,static_ip', true], 'speed_download' => ['Kecepatan unduh', 'text', false], 'speed_upload' => ['Kecepatan unggah', 'text', false], 'monthly_price' => ['Tarif bulanan (Rp)', 'number', true], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'customers' => ['title' => 'Pelanggan tetap', 'table' => 'customers', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'noc', 'keuangan', 'sales', 'pelanggan'], 'create' => ['superadmin', 'administrator', 'admin', 'sales'], 'columns' => ['customer_code' => 'ID pelanggan', 'full_name' => 'Nama', 'phone' => 'WhatsApp', 'plan_name' => 'Paket', 'service_type' => 'Jenis koneksi', 'status' => 'Status'], 'fields' => ['customer_code' => ['ID pelanggan (10 digit)', 'customer_id', true], 'sales_user_id' => ['Nama sales (opsional)', 'options:sales', false], 'branch_id' => ['Cabang', 'options:branches', true], 'created_by_display' => ['Dibuat oleh', 'readonly', false], 'full_name' => ['Nama lengkap (nama depan dan belakang)', 'text', true], 'phone' => ['WhatsApp aktif (format 62xxxxxxxxxx)', 'phone', true], 'email' => ['Email', 'email', false], 'service_type' => ['Jenis koneksi', 'select:pppoe,static_ip', true], 'connection_mode' => ['Aksi MikroTik', 'select:create,sync', true], 'mikrotik_router_id' => ['Router MikroTik', 'options:routers', true], 'mikrotik_username' => ['Username koneksi PPPoE', 'text', false], 'mikrotik_password' => ['Kata sandi PPPoE (hanya dikirim ke MikroTik)', 'password', false], 'static_ip' => ['Alamat IP statis', 'text', false], 'plan_id' => ['Paket layanan', 'options:plans', true], 'due_day' => ['Jatuh tempo (tanggal 1–20)', 'number', true], 'auto_billing' => ['Generate tagihan otomatis', 'select:1,0', true], 'device_serial' => ['Serial number ONT/ONU', 'options:available_serials', true], 'odp_port_id' => ['ODP · port', 'options:available_odp_ports', true], 'installed_at' => ['Tanggal aktif', 'date', false], 'address' => ['Alamat pemasangan', 'textarea', false], 'notes' => ['Catatan', 'textarea', false]]],
        'routers' => ['title' => 'Router MikroTik', 'table' => 'routers', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => ['superadmin', 'administrator', 'admin'], 'columns' => ['name' => 'Nama', 'base_url' => 'Alamat REST API', 'username' => 'Pengguna API', 'verify_ssl' => 'Verifikasi TLS', 'active' => 'Aktif'], 'fields' => ['name' => ['Nama router', 'text', true], 'base_url' => ['URL router (HTTPS)', 'url', true], 'username' => ['Pengguna API', 'text', true], 'password' => ['Kata sandi API', 'password', true], 'verify_ssl' => ['Verifikasi sertifikat TLS', 'select:1,0', true], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'products' => ['title' => 'Stok barang', 'table' => 'products', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'keuangan'], 'create' => ['superadmin', 'administrator', 'admin', 'keuangan'], 'columns' => ['sku' => 'SKU', 'name' => 'Barang', 'stock_qty' => 'Stok', 'minimum_stock' => 'Batas minimum', 'cost_price' => 'Harga modal', 'sale_price' => 'Harga jual'], 'fields' => ['sku' => ['SKU', 'text', true], 'name' => ['Nama barang', 'text', true], 'category' => ['Jenis barang', 'select:ont_onu,router,accessory,other', true], 'unit' => ['Satuan', 'text', true], 'minimum_stock' => ['Batas stok minimum', 'number', false], 'cost_price' => ['Harga modal per satuan', 'number', false], 'sale_price' => ['Harga jual', 'number', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'invoices' => ['title' => 'Tagihan pelanggan', 'table' => 'invoices', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan', 'pelanggan'], 'create' => [], 'columns' => ['invoice_no' => 'No. tagihan', 'customer_name' => 'Pelanggan', 'period' => 'Periode', 'total' => 'Total', 'due_date' => 'Jatuh tempo', 'status' => 'Status']],
        'payments' => ['title' => 'Pembayaran', 'table' => 'payments', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan', 'pelanggan'], 'create' => [], 'columns' => ['external_reference' => 'Referensi', 'customer_name' => 'Pelanggan', 'provider' => 'Gateway', 'amount' => 'Jumlah', 'status' => 'Status', 'paid_at' => 'Waktu bayar']],
        'finance' => ['title' => 'Buku kas', 'table' => 'finance_entries', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan'], 'create' => ['superadmin', 'administrator', 'admin', 'keuangan'], 'columns' => ['transaction_date' => 'Tanggal', 'entry_type' => 'Jenis', 'category' => 'Kategori', 'amount' => 'Jumlah', 'description' => 'Keterangan'], 'fields' => ['transaction_date' => ['Tanggal transaksi', 'date', true], 'category' => ['Kategori biaya', 'text', true], 'amount' => ['Jumlah (Rp)', 'number', true], 'description' => ['Keterangan', 'textarea', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'attendance' => ['title' => 'Absensi tim', 'table' => 'attendances', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'keuangan', 'noc', 'sales'], 'create' => [], 'columns' => ['user_name' => 'Nama', 'work_date' => 'Tanggal', 'check_in' => 'Masuk', 'check_out' => 'Pulang', 'status' => 'Status']],
        'work-orders' => ['title' => 'Tugas teknisi & sales', 'table' => 'work_orders', 'roles' => ['superadmin', 'administrator', 'admin', 'teknisi', 'noc', 'sales'], 'create' => ['superadmin', 'administrator', 'admin', 'noc', 'sales'], 'columns' => ['work_order_no' => 'No. tugas', 'title' => 'Pekerjaan', 'customer_name' => 'Pelanggan', 'technician_name' => 'Teknisi', 'sales_name' => 'Sales', 'billable_amount' => 'Nilai', 'status' => 'Status'], 'fields' => ['title' => ['Judul pekerjaan', 'text', true], 'customer_id' => ['Pelanggan (opsional)', 'options:customers', false], 'technician_id' => ['Teknisi (opsional)', 'options:technicians', false], 'sales_user_id' => ['Sales (opsional)', 'options:sales', false], 'billable_amount' => ['Nilai pekerjaan (Rp)', 'number', false], 'scheduled_at' => ['Jadwal', 'datetime-local', false], 'description' => ['Rincian pekerjaan', 'textarea', false], 'branch_id' => ['Cabang', 'options:branches', false]]],
        'earnings' => ['title' => 'Bonus teknisi & sales fee', 'table' => 'staff_earnings', 'roles' => ['superadmin', 'administrator', 'admin', 'keuangan', 'teknisi', 'sales'], 'create' => [], 'columns' => ['user_name' => 'Penerima', 'earning_type' => 'Jenis fee', 'period' => 'Periode', 'amount' => 'Nilai', 'status' => 'Status', 'work_order_no' => 'Pekerjaan']],
        'earning-rules' => ['title' => 'Aturan bonus & sales fee', 'table' => 'earning_rules', 'roles' => ['superadmin', 'administrator', 'admin'], 'create' => ['superadmin', 'administrator', 'admin'], 'columns' => ['role' => 'Peran penerima', 'calculation_type' => 'Perhitungan', 'value' => 'Nilai', 'active' => 'Aktif'], 'fields' => ['role' => ['Peran penerima', 'select:teknisi,sales', true], 'calculation_type' => ['Metode hitung', 'select:fixed,percent', true], 'value' => ['Nominal rupiah atau persentase', 'number', true], 'branch_id' => ['Cabang (kosong = semua cabang)', 'options:branches', false]]],
        'vouchers' => ['title' => 'Voucher hotspot', 'table' => 'hotspot_vouchers', 'roles' => ['superadmin', 'administrator', 'admin', 'noc'], 'create' => [], 'columns' => ['username' => 'Username', 'password' => 'Kata sandi', 'profile' => 'Profil', 'unit_price' => 'Harga', 'status' => 'Status', 'created_at' => 'Dibuat']],
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
            $routerQuery = db_connect()->table('routers')->select('id, name, branch_id')->where('active', 1);
            if (mynet_user('branch_id')) $routerQuery->where('branch_id', mynet_user('branch_id'));
            $routers = $routerQuery->orderBy('name')->get()->getResultArray();
        }
        $voucherBranches = [];
        if ($slug === 'vouchers' && empty(mynet_user('branch_id'))) {
            $voucherBranches = db_connect()->table('branches')->select('id, name')->where('status', 'active')->orderBy('name')->get()->getResultArray();
        }

        return view('workspace/index', [
            'title' => $module['title'], 'module' => $slug, 'definition' => $module,
            'rows' => $rows, 'menus' => mynet_menus(mynet_user('role')),
            'canCreate' => in_array(mynet_user('role'), $module['create'], true),
            'actions' => $links, 'openInvoices' => $openInvoices, 'routers' => $routers, 'voucherBranches' => $voucherBranches,
        ]);
    }

    public function newForm(string $slug)
    {
        $module = $this->module($slug);
        if ($denied = $this->authorize($module['create'])) return $denied;
        return view('workspace/form', [
            'title' => 'Tambah ' . strtolower($module['title']), 'module' => $slug,
            'definition' => $module, 'fields' => $this->fields($module),
            'generatedCustomerCode' => $slug === 'customers' ? $this->generateCustomerCode() : null,
            'menus' => mynet_menus(mynet_user('role')),
        ]);
    }

    public function generateCustomerId()
    {
        if (! in_array(mynet_user('role'), ['superadmin', 'administrator', 'admin', 'sales'], true)) return $this->response->setStatusCode(403)->setJSON(['error' => 'Tidak memiliki akses.']);
        return $this->response->setJSON(['customer_id' => $this->generateCustomerCode()]);
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
        $branchWa = null;
        if ($slug === 'branches') {
            $provider = (string) ($posted['wa_provider'] ?? '');
            $key = $provider === 'fonnte' ? trim((string) ($posted['wa_token'] ?? '')) : trim((string) ($posted['wa_api_key'] ?? ''));
            if (! in_array($provider, ['ngirimwa', 'fonnte'], true) || $key === '') return redirect()->back()->withInput()->with('error', 'Pilih gateway WhatsApp cabang dan isi kredensial gateway tersebut.');
            $branchWa = [
                'provider' => $provider, 'ngirimwa_api_key' => $provider === 'ngirimwa' ? $key : '', 'fonnte_token' => $provider === 'fonnte' ? $key : '',
                'technician_group_id' => trim((string) ($posted['technician_group_id'] ?? '')),
                'finance_group_id' => trim((string) ($posted['finance_group_id'] ?? '')),
                'admin_group_id' => trim((string) ($posted['admin_group_id'] ?? '')),
            ];
            foreach (['technician_group_id', 'finance_group_id', 'admin_group_id'] as $groupField) {
                if ($branchWa[$groupField] !== '' && ! preg_match('/^[0-9-]{8,40}@g\.us$/', $branchWa[$groupField])) return redirect()->back()->withInput()->with('error', 'ID grup WhatsApp harus berakhiran @g.us.');
            }
            unset($data['wa_provider'], $data['wa_api_key'], $data['wa_token'], $data['technician_group_id'], $data['finance_group_id'], $data['admin_group_id']);
        }
        if (in_array($slug, ['plans', 'customers', 'routers', 'products', 'finance', 'work-orders', 'odcs', 'odps', 'serials'], true)) $data['branch_id'] = $branchId;
        if ($slug === 'fiber-cores') {
            $odc = db_connect()->table('fiber_odcs')->where('id', (int) ($data['odc_id'] ?? 0))->where('active', 1)->get()->getRowArray();
            if (! $odc || ! $this->inBranch($odc['branch_id'])) return redirect()->back()->withInput()->with('error', 'Pilih ODC aktif di cabang Anda.');
            if ((int) ($data['core_number'] ?? 0) < 1) return redirect()->back()->withInput()->with('error', 'Nomor core harus lebih besar dari nol.');
        }
        $odpPortCount = 0;
        if ($slug === 'odps') {
            $odpPortCount = (int) ($data['port_count'] ?? 0);
            unset($data['port_count']);
            if ($odpPortCount < 8 || $odpPortCount > 64) return redirect()->back()->withInput()->with('error', 'ODP harus memiliki minimal 8 dan maksimal 64 port pelanggan.');
            $odcId = (int) ($data['odc_id'] ?? 0);
            $core = db_connect()->table('fiber_cores')->where('id', (int) ($data['core_id'] ?? 0))->where('odc_id', $odcId)->get()->getRowArray();
            $odc = db_connect()->table('fiber_odcs')->where('id', $odcId)->where('branch_id', $branchId)->where('active', 1)->get()->getRowArray();
            if (! $core || ! $odc || (int) db_connect()->table('fiber_odps')->where('odc_id', $odcId)->countAllResults() >= 8) return redirect()->back()->withInput()->with('error', 'ODP wajib terhubung ke core ODC yang sama; satu ODC maksimal memiliki 8 ODP.');
        }
        if ($slug === 'earning-rules') $data['branch_id'] = mynet_user('branch_id') ?: (! empty($posted['branch_id']) ? (int) $posted['branch_id'] : null);
        if ($slug === 'odcs') {
            $select .= ', branches.name AS branch_name';
            $builder->join('branches', 'branches.id = fiber_odcs.branch_id', 'left');
        } elseif ($slug === 'fiber-cores') {
            $select .= ', fiber_odcs.name AS odc_name';
            $builder->join('fiber_odcs', 'fiber_odcs.id = fiber_cores.odc_id', 'left');
        } elseif ($slug === 'odps') {
            $select .= ', fiber_odcs.name AS odc_name, fiber_cores.route_label';
            $builder->join('fiber_odcs', 'fiber_odcs.id = fiber_odps.odc_id', 'left')->join('fiber_cores', 'fiber_cores.id = fiber_odps.core_id', 'left');
        } elseif ($slug === 'customers') {
            unset($data['created_by_display']);
            $customerCode = trim((string) ($posted['customer_code'] ?? ''));
            if (! preg_match('/^[0-9]{10}$/', $customerCode)) return redirect()->back()->withInput()->with('error', 'ID pelanggan harus terdiri dari 10 angka.');
            if (db_connect()->table('customers')->where('customer_code', $customerCode)->countAllResults()) return redirect()->back()->withInput()->with('error', 'ID pelanggan sudah digunakan. Tekan Generate untuk membuat ID baru.');
            $data['customer_code'] = $customerCode;
            $data['status'] = 'active';
            $data['created_by'] = (int) mynet_user('id');
            $data['auto_billing'] = (int) ($data['auto_billing'] ?? 1);
            $data['due_day'] = (int) ($data['due_day'] ?? 0);
            if ($data['due_day'] < 1 || $data['due_day'] > 20) return redirect()->back()->withInput()->with('error', 'Tanggal jatuh tempo harus antara 1 sampai 20.');
            $nameParts = preg_split('/\s+/', trim((string) $data['full_name']));
            if (count($nameParts) < 2) return redirect()->back()->withInput()->with('error', 'Isi nama depan dan nama belakang pelanggan.');
            $phone = preg_replace('/[\s().-]+/', '', (string) $data['phone']);
            if (str_starts_with($phone, '+62')) $phone = substr($phone, 1);
            elseif (str_starts_with($phone, '08')) $phone = '62' . substr($phone, 1);
            elseif (str_starts_with($phone, '8')) $phone = '62' . $phone;
            if (! preg_match('/^62[0-9]{8,13}$/', $phone)) return redirect()->back()->withInput()->with('error', 'Nomor WhatsApp harus valid dan menggunakan kode negara 62.');
            $data['phone'] = $phone;
            $data['connection_mode'] = (string) ($data['connection_mode'] ?? 'create');
            if (! in_array($data['connection_mode'], ['create', 'sync'], true)) return redirect()->back()->withInput()->with('error', 'Pilih aksi MikroTik yang valid.');
            if ($data['service_type'] === 'static_ip') $data['connection_mode'] = 'sync';
            if ($data['service_type'] === 'pppoe' && (trim((string) ($data['mikrotik_username'] ?? '')) === '' || trim((string) ($posted['mikrotik_password'] ?? '')) === '')) return redirect()->back()->withInput()->with('error', 'Username dan kata sandi koneksi PPPoE wajib diisi.');
            $mikrotikPassword = (string) ($posted['mikrotik_password'] ?? '');
            unset($data['mikrotik_password']);
            if ($data['service_type'] === 'static_ip' && ! filter_var($data['static_ip'] ?? '', FILTER_VALIDATE_IP)) return redirect()->back()->withInput()->with('error', 'Alamat IP static harus valid.');
            $salesId = (int) ($data['sales_user_id'] ?? 0);
            if ($salesId) {
                $sales = db_connect()->table('users')->where('id', $salesId)->where('role', 'sales')->where('active', 1)->where('branch_id', $branchId)->countAllResults();
                if (! $sales) return redirect()->back()->withInput()->with('error', 'Sales harus aktif dan berasal dari cabang pelanggan.');
            }
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
            if (! $plan || $plan['service_type'] !== $data['service_type'] || (! empty($plan['branch_id']) && (int) $plan['branch_id'] !== (int) $data['branch_id'])) return redirect()->back()->withInput()->with('error', 'Paket aktif harus sesuai jenis koneksi dan cabang pelanggan.');
            $router = db_connect()->table('routers')->where('id', (int) $data['mikrotik_router_id'])->where('active', 1)->where('branch_id', $data['branch_id'])->countAllResults();
            if (! $router) return redirect()->back()->withInput()->with('error', 'Pilih router aktif dari cabang pelanggan.');
            $serial = db_connect()->table('product_serials ps')->select('ps.id, ps.product_id, ps.serial_number')
                ->join('products p', 'p.id = ps.product_id')->where('ps.id', (int) $data['device_serial'])
                ->where('ps.branch_id', $data['branch_id'])->where('ps.status', 'available')->where('p.category', 'ont_onu')->get()->getRowArray();
            if (! $serial) return redirect()->back()->withInput()->with('error', 'Serial ONT/ONU harus terdaftar dan tersedia pada stok cabang.');
            $port = db_connect()->table('fiber_odp_ports op')->select('op.id, o.odc_id')
                ->join('fiber_odps o', 'o.id = op.odp_id')->where('op.id', (int) $data['odp_port_id'])
                ->where('op.status', 'available')->where('o.branch_id', $data['branch_id'])->get()->getRowArray();
            if (! $port) return redirect()->back()->withInput()->with('error', 'Pilih port ODP yang masih tersedia di cabang pelanggan.');
            if ((int) db_connect()->table('fiber_odps')->where('odc_id', $port['odc_id'])->countAllResults() < 4) return redirect()->back()->withInput()->with('error', 'ODC harus memiliki minimal 4 ODP sebelum pelanggan dapat dipasang.');
            if ($data['service_type'] === 'pppoe') {
                $routerRow = db_connect()->table('routers')->where('id', (int) $data['mikrotik_router_id'])->get()->getRowArray();
                try {
                    (new \App\Libraries\RouterOs())->provisionPppoe($routerRow, (string) $data['mikrotik_username'], $mikrotikPassword, (string) $plan['name'], (string) $data['connection_mode']);
                } catch (\Throwable $e) {
                    return redirect()->back()->withInput()->with('error', 'Sinkronisasi PPPoE ke MikroTik gagal: ' . $e->getMessage());
                }
            } else {
                $routerRow = db_connect()->table('routers')->where('id', (int) $data['mikrotik_router_id'])->get()->getRowArray();
                try {
                    (new \App\Libraries\RouterOs())->syncStaticIpQueue($routerRow, 'MYNET-' . $customerCode, (string) $data['static_ip'], (string) $plan['speed_download'], (string) $plan['speed_upload']);
                } catch (\Throwable $e) {
                    return redirect()->back()->withInput()->with('error', 'Sinkronisasi Simple Queue IP static gagal: ' . $e->getMessage());
                }
            }
            if ((int) db_connect()->table('fiber_odps')->where('odc_id', $port['odc_id'])->countAllResults() < 4) return redirect()->back()->withInput()->with('error', 'ODC harus memiliki minimal 4 ODP sebelum pelanggan dapat dipasang.');
            $data['device_serial'] = $serial['serial_number'];
            $data['mikrotik_router_id'] = (int) $data['mikrotik_router_id'];
            $data['sales_user_id'] = $salesId ?: null;
            $data['odp_port_id'] = (int) $data['odp_port_id'];
            db_connect()->transStart();
            db_connect()->table($module['table'])->insert($data);
            $customerId = (int) db_connect()->insertID();
            db_connect()->table('product_serials')->where('id', $serial['id'])->where('status', 'available')->update(['status' => 'assigned', 'customer_id' => $customerId, 'updated_at' => date('Y-m-d H:i:s')]);
            if (db_connect()->affectedRows() !== 1) {
                db_connect()->transRollback();
                return redirect()->back()->withInput()->with('error', 'Serial ONT/ONU sudah dipakai pelanggan lain.');
            }
            db_connect()->table('products')->where('id', $serial['product_id'])->where('stock_qty >', 0)->set('stock_qty', 'stock_qty - 1', false)->update();
            if (db_connect()->affectedRows() !== 1) {
                db_connect()->transRollback();
                return redirect()->back()->withInput()->with('error', 'Stok barang ONT/ONU kosong; catat stok masuk sebelum memasang perangkat.');
            }
            db_connect()->table('fiber_odp_ports')->where('id', $port['id'])->where('status', 'available')->update(['status' => 'occupied', 'customer_id' => $customerId]);
            if (db_connect()->affectedRows() !== 1) {
                db_connect()->transRollback();
                return redirect()->back()->withInput()->with('error', 'Port ODP sudah dipakai pelanggan lain.');
            }
            db_connect()->transComplete();
            if (! db_connect()->transStatus()) return redirect()->back()->withInput()->with('error', 'Pelanggan gagal disimpan; pastikan serial perangkat dan port ODP belum dipakai.');
            return redirect()->to('/area/customers')->with('success', 'Pelanggan tetap berhasil dibuat dengan ID ' . $customerCode . '.');
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

        if ($slug === 'odps') {
            $db = db_connect();
            $db->transStart();
            $db->table('fiber_odps')->insert($data);
            $odpId = (int) $db->insertID();
            $ports = [];
            for ($portNumber = 1; $portNumber <= $odpPortCount; $portNumber++) $ports[] = ['odp_id' => $odpId, 'port_number' => $portNumber, 'status' => 'available'];
            $db->table('fiber_odp_ports')->insertBatch($ports);
            $db->transComplete();
            if (! $db->transStatus()) return redirect()->back()->withInput()->with('error', 'ODP dan port tidak berhasil disimpan.');
            return redirect()->to('/area/odps')->with('success', 'ODP berhasil dibuat beserta ' . $odpPortCount . ' port pelanggan.');
        }
        if ($slug === 'serials') {
            $productQuery = db_connect()->table('products')->where('id', (int) ($posted['product_id'] ?? 0))->where('category', 'ont_onu')->where('active', 1);
            if ($branchId) $productQuery->groupStart()->where('branch_id', $branchId)->orWhere('branch_id', null)->groupEnd();
            $product = $productQuery->get()->getRowArray();
            if (! $product || ! $this->inBranch($posted['branch_id'] ?? null)) return redirect()->back()->withInput()->with('error', 'Pilih produk ONT/ONU aktif pada cabang Anda.');
            $db = db_connect();
            if ($db->table('product_serials')->where('serial_number', trim((string) $posted['serial_number']))->countAllResults()) return redirect()->back()->withInput()->with('error', 'Serial number sudah terdaftar.');
            $db->table('product_serials')->insert([
                'branch_id' => $branchId, 'product_id' => (int) $product['id'], 'serial_number' => trim((string) $posted['serial_number']),
                'status' => 'available', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return redirect()->to('/area/serials')->with('success', 'Serial ONT/ONU berhasil ditambahkan ke stok tersedia.');
        }
        db_connect()->table($module['table'])->insert($data);
        if ($slug === 'branches' && $branchWa !== null) (new \App\Libraries\IntegrationConfig())->saveBranchWhatsapp((int) db_connect()->insertID(), $branchWa);
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
        } elseif ($slug === 'serials') {
            $select = 'product_serials.id, product_serials.branch_id, product_serials.serial_number, product_serials.status, products.name AS product_name, branches.name AS branch_name';
            $builder->join('products', 'products.id = product_serials.product_id')->join('branches', 'branches.id = product_serials.branch_id', 'left');
        } elseif ($slug === 'products') {
            $select .= ', CASE WHEN ' . $table . '.stock_qty <= ' . $table . '.minimum_stock THEN 1 ELSE 0 END AS low_stock';
        }
        $builder->select($select);

        $user = session('user');
        if ($user['role'] === 'pelanggan') {
            if ($slug === 'customers') $builder->where('customers.id', $user['customer_id'] ?? 0);
            elseif (in_array($slug, ['invoices', 'payments'], true)) $builder->where($table . '.customer_id', $user['customer_id'] ?? 0);
            elseif ($slug === 'work-orders') $builder->where('work_orders.id', 0);
        } elseif (! empty($user['branch_id']) && in_array($slug, ['plans', 'customers', 'routers', 'products', 'serials', 'odcs', 'odps', 'invoices', 'payments', 'finance', 'attendance', 'work-orders', 'earnings', 'earning-rules', 'vouchers', 'users'], true)) {
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
            'plans' => $db->table('plans')->select('id, name, branch_id, service_type')->where('active', 1)->whereIn('service_type', ['pppoe', 'static_ip']),
            'customers' => $db->table('customers')->select('id, full_name AS name')->where('status', 'active'),
            'technicians' => $db->table('users')->select('id, full_name AS name')->where('active', 1)->where('role', 'teknisi'),
            'sales' => $db->table('users')->select('id, full_name AS name')->where('active', 1)->where('role', 'sales'),
            'branches' => $db->table('branches')->select('id, name')->where('status', 'active'),
            'odcs' => $db->table('fiber_odcs')->select('id, name')->where('active', 1),
            'cores' => $db->table('fiber_cores c')->select("c.id, CONCAT(o.name, ' / Core ', c.core_number, ' - ', c.color) AS name", false)->join('fiber_odcs o', 'o.id = c.odc_id')->where('o.active', 1),
            'routers' => $db->table('routers')->select('id, name, branch_id')->where('active', 1),
            'available_serials' => $db->table('product_serials ps')->select('ps.id, ps.branch_id, CONCAT(ps.serial_number, " · ", b.name) AS name', false)->join('products p', 'p.id = ps.product_id')->join('branches b', 'b.id = ps.branch_id')->where('ps.status', 'available')->where('p.category', 'ont_onu'),
            'ont_products' => $db->table('products')->select('id, name, branch_id')->where('active', 1)->where('category', 'ont_onu'),
            'available_odp_ports' => $db->table('fiber_odp_ports op')->select("op.id, o.branch_id, CONCAT(o.name, ' / Port ', op.port_number) AS name", false)->join('fiber_odps o', 'o.id = op.odp_id')->where('op.status', 'available')->where('o.active', 1),
            default => null,
        };
        if (! $query) return [];
        $user = session('user');
        if (! empty($user['branch_id'])) {
            if ($source === 'branches') $query->where('id', $user['branch_id']);
            elseif (in_array($source, ['plans', 'customers', 'technicians', 'sales'], true)) $query->where('branch_id', $user['branch_id']);
            elseif ($source === 'routers') $query->where('branch_id', $user['branch_id']);
            elseif ($source === 'odcs') $query->where('branch_id', $user['branch_id']);
            elseif ($source === 'cores') $query->where('o.branch_id', $user['branch_id']);
            elseif ($source === 'available_serials') $query->where('ps.branch_id', $user['branch_id']);
            elseif ($source === 'ont_products') $query->where('branch_id', $user['branch_id']);
            elseif ($source === 'available_odp_ports') $query->where('o.branch_id', $user['branch_id']);
        }
        if (in_array($source, ['available_serials', 'available_odp_ports', 'ont_products'], true)) return $query->orderBy('name')->get()->getResultArray();
        return $query->orderBy('name')->get()->getResultArray();
    }

    private function generateCustomerCode(): string
    {
        $db = db_connect();
        do {
            $code = (string) random_int(1_000_000_000, 9_999_999_999);
        } while ($db->table('customers')->where('customer_code', $code)->countAllResults() > 0);

        return $code;
    }
}
