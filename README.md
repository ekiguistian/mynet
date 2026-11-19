# Mynet Billing & Network Manager

Aplikasi operasional ISP untuk mengelola pelanggan internet, tagihan berkala, pembayaran, voucher hotspot, stok, kas, absensi, pekerjaan teknisi, dan sales fee. Aplikasi dibangun dengan CodeIgniter 4 dan tampilan responsif untuk administrator, admin cabang, teknisi, keuangan, NOC, sales, dan pelanggan.

Tidak ada halaman depan publik. Akses ke `/` langsung menampilkan halaman login.

## Fitur

### Akun, peran, dan cabang

- Login berbasis username dan kata sandi dengan session regeneration, password hash, CSRF untuk form, dan pemaksaan penggantian kata sandi awal.
- Peran yang disediakan: `superadmin`, `administrator`, `admin`, `teknisi`, `keuangan`, `noc`, `sales`, dan `pelanggan`.
- Menu dan operasi dibatasi per peran. Setiap pengguna dapat ditempatkan pada satu cabang; akun administrator utama dapat melihat semua cabang.
- Data pelanggan, paket, router, stok, tagihan, transaksi, absensi, pekerjaan, bonus, dan voucher difilter menurut cabang.
- Akun pelanggan ditautkan ke profil pelanggan sehingga halaman pelanggan hanya menampilkan tagihan dan pembayaran miliknya.
- Perangkat desktop dan layar ponsel memakai layout responsif yang sama, dengan menu sesuai peran.

### Pelanggan dan tagihan

- Profil pelanggan tetap memuat cabang, paket PPPoE atau IP static, nomor WhatsApp, jatuh tempo, pilihan router, username, SN perangkat, dan port ODP. Voucher hotspot dikelola pada menu terpisah tanpa profil pelanggan tetap.
- ID pelanggan dibuat otomatis sebagai 10 angka dan divalidasi unik. Nama depan dan belakang, nomor WhatsApp berawalan 62, SN ONT/ONU, dan port ODP wajib diisi.
- Generate tagihan manual membuat satu tagihan per pelanggan aktif untuk periode terpilih dan menghormati tanggal jatuh tempo pelanggan. Perintah harian `php spark billing:generate-recurring` membuat tagihan otomatis untuk pelanggan yang tanggalnya jatuh hari ini dan mengaktifkan auto billing. Tagihan periode ganda dilewati.
- Pembayaran tagihan menerima input kasir/transfer manual atau tautan checkout payment gateway. Pembayaran sebagian didukung.
- Webhook gateway memeriksa signature/token, mencocokkan jumlah pembayaran, menolak referensi yang tidak dikenal, memperbarui status tagihan, dan mengabaikan event berulang.
- Pengingat tagihan WhatsApp dapat dikirim dari daftar tagihan. Status antrean dan kegagalan dicatat di log pesan.
- Invoice dapat dicetak langsung pada format thermal 80 mm atau A4, dan tersedia sebagai PDF A4.
- Administrator dapat mengatur nama aplikasi, logo, favicon, tautan sosial, gambar preview Open Graph, canonical URL, robots, verifikasi Google Search Console, dan Google Tag/Tag Manager dari menu Pengaturan aplikasi.
- API key eksternal memiliki scope dan batas cabang. Secret ditampilkan sekali saat dibuat, disimpan sebagai hash, dan dapat dicabut.

### MikroTik dan voucher hotspot

- Detail router disimpan per cabang: nama, IP Public/hostname VPN, port HTTPS/REST API, username, dan password terenkripsi. Halaman router memperbarui status koneksi, uptime, CPU, dan penggunaan memori setiap 60 detik.
- Generate 1–100 voucher dengan username dan kata sandi acak.
- Voucher dapat disimpan untuk ekspor/pemakaian manual atau langsung dibuat sebagai user hotspot di RouterOS v7 melalui REST API.
- Status `sync_failed` dicatat bila perangkat menolak atau tidak dapat dijangkau; kegagalan tidak menghapus voucher lokal.
- Profil hotspot dan harganya dibaca langsung dari router. Harga diambil hanya jika komentar profil berupa angka bulat.
- Akun PPPoE dapat dibuat atau disinkronkan pada menu pelanggan melalui RouterOS REST API. Kata sandi PPPoE hanya dikirim ke router dan tidak disimpan oleh aplikasi.
- IP static disinkronkan sebagai Simple Queue RouterOS dengan target `/32` untuk IPv4 atau `/128` untuk IPv6. Nama queue memakai ID pelanggan (`MYNET-<ID>`), dan batas upload/download mengikuti kecepatan paket.
- Pengelolaan SN ONT/ONU dan ODC, jalur/core warna, ODP, serta port pelanggan tersedia per cabang. Satu ODC menampung 4–8 ODP; setiap ODP dibuat dengan minimal 8 port dan port ditempati saat pelanggan didaftarkan.

### Persediaan dan keuangan

- Barang menyimpan SKU, satuan, jumlah stok, batas minimum, harga modal, dan harga jual.
- Stok masuk/keluar dicatat sebagai pergerakan audit. Jumlah stok diperbarui dalam transaksi database dan stok keluar tidak boleh melebihi saldo.
- Pembelian stok masuk otomatis menjadi pengeluaran pada buku kas.
- Pembayaran tagihan manual maupun terkonfirmasi melalui webhook menjadi pemasukan buku kas.
- Pembayaran bonus dan sales fee menjadi pengeluaran kas.
- Ringkasan pemasukan, pengeluaran, dan selisih bulanan difilter per cabang dan disembunyikan dari peran yang tidak mengelola keuangan.

### Absensi, tugas, bonus, dan sales fee

- Setiap pengguna staf mencatat jam masuk dan pulang. Satu baris absensi dibuat per pengguna per hari.
- Tugas kerja dapat dihubungkan ke pelanggan, teknisi, sales, jadwal, dan nilai pekerjaan.
- Teknisi dapat memperbarui tugas yang ditugaskan kepadanya. Penyelesaian tugas menghitung bonus teknisi dan sales fee yang belum dibayar.
- Aturan kompensasi dapat ditetapkan per cabang atau sebagai aturan umum, dengan nominal tetap atau persentase.
- Nilai awal dari seeder: bonus teknisi Rp25.000 per pekerjaan dan sales fee 5% dari nilai pekerjaan. Ubah melalui menu **Aturan bonus**.
- Tim keuangan membayar bonus/fee dari daftar **Bonus & fee**; pembayaran masuk ke buku kas dan tidak bisa dihitung ulang untuk pekerjaan yang sama.

### Integrasi eksternal

- **MikroTik RouterOS v7 REST API** untuk profil voucher Hotspot serta pembuatan/sinkronisasi secret PPPoE. Router dan kredensialnya dikonfigurasi per cabang di aplikasi.
- **NgirimWA** melalui endpoint kirim pesan dan header API key.
- **Fonnte** (kadang ditulis Fonte) melalui endpoint kirim pesan dan token perangkat.
- **Tripay** untuk membuat transaksi pembayaran tertutup dan menerima callback.
- **Midtrans Snap** untuk membuat checkout dan menerima notifikasi transaksi.
- **Xendit Invoice API** untuk membuat invoice dan menerima webhook.
- Kredensial integrasi dapat diatur dari halaman Integrasi API dan disimpan terenkripsi menggunakan `encryption.key`. `.env` tetap dapat dipakai sebagai konfigurasi cadangan saat belum ada konfigurasi database.
- Gateway WhatsApp dapat diatur saat cabang dibuat. Pesan ke pelanggan dan staf memakai kredensial serta ID grup milik cabang tersebut; antrean mendukung jadwal kirim.
- Template pesan mendukung antrean terjadwal, penerima pelanggan, atau grup peran teknisi/keuangan/admin. Grup dapat dikirim sebagai ID grup Fonnte atau NgirimWA; tanpa ID grup, sistem mengirim satu pesan ke tiap nomor staf aktif pada cabang.
- Tiket maintenance mencakup antrean, prioritas, penjadwalan, penugasan teknisi, riwayat pembaruan, notifikasi WhatsApp, dan pembaruan status.

## Peran dan akses singkat

| Peran | Akses utama |
|---|---|
| `superadmin` | Semua cabang, pengguna, konfigurasi, dan seluruh modul |
| `administrator` | Semua modul operasional dan cabang |
| `admin` | Pelanggan, paket, tagihan, pembayaran manual, stok, router, absensi, tugas, dan pengelolaan staf cabang |
| `teknisi` | Pelanggan, absensi, tugas yang ditugaskan, stok, dan voucher |
| `keuangan` | Tagihan, pembayaran, kas, stok, dan pembayaran bonus/fee |
| `noc` | Pelanggan, paket, router, voucher, dan tugas jaringan |
| `sales` | Pelanggan, tugas, dan sales fee |
| `pelanggan` | Profil sendiri, tagihan sendiri, dan checkout pembayaran |

## Persyaratan

- PHP 8.2 atau lebih baru dengan ekstensi `intl`, `mbstring`, `mysqli`, `curl`, `openssl`, `json`, dan `fileinfo`.
- Ekstensi PHP `dom` diperlukan oleh Dompdf untuk PDF invoice.
- Composer 2.
- MySQL/MariaDB.
- RouterOS v7 dengan REST API aktif untuk sinkronisasi voucher.
- URL HTTPS publik untuk callback payment gateway pada lingkungan produksi.

## Deploy produksi di aaPanel (Nginx)

Panduan ini adalah template. Ganti nilai dalam tanda `<...>` dengan nilai milik lingkungan Anda. Jangan menyalin kredensial dari `.env` ke README, tiket, atau Git. Konfigurasi development lokal bersifat opsional dan dijelaskan setelah panduan server.

Siapkan terlebih dahulu domain, VPS aaPanel, database MySQL/MariaDB, serta kredensial database yang dibuat khusus untuk aplikasi.

### 1. Arahkan domain ke server

1. Pada pengelola DNS domain Anda, buat record `A` untuk subdomain yang dipilih (misalnya `billing`) dan arahkan ke alamat IPv4 publik VPS aaPanel.
2. Jika server memakai IPv6, tambahkan record `AAAA` yang benar. Hapus record `AAAA` yang tidak digunakan agar pengunjung tidak diarahkan ke server yang salah.
3. Pastikan firewall VPS dan firewall penyedia hosting mengizinkan koneksi TCP pada port `80` dan `443`.
4. Tunggu hingga DNS mengarah ke alamat server sebelum meminta sertifikat SSL.

### 2. Buat website PHP di aaPanel

1. Pastikan Nginx sudah terpasang di aaPanel. Buka **Website → PHP Project → Add Site**, pilih PHP Project, lalu masukkan domain aplikasi Anda (`<domain-anda>`).
2. Tentukan direktori proyek (contoh `/www/wwwroot/<nama-aplikasi>`), pilih PHP `8.2` atau lebih baru, dan gunakan Nginx sebagai web server.
3. Pada pengaturan versi PHP di aaPanel, pastikan ekstensi `intl`, `mbstring`, `mysqli`/`mysqlnd`, `curl`, `openssl`, `json`, `fileinfo`, dan `dom` aktif. Gunakan versi PHP CLI yang sama dengan versi PHP website.
4. Buat database dan user khusus aplikasi. Berikan izin yang dibutuhkan aplikasi pada database tersebut.

### 3. Unggah kode aplikasi

1. Unggah atau clone isi repositori ke direktori proyek yang dipilih. Pastikan `composer.json`, `spark`, `app/`, `public/`, dan `writable/` berada langsung di direktori tersebut, bukan di subfolder tambahan.
2. Pada **Site Directory / Running Directory**, atur direktori berjalan ke `public`. Pastikan root Nginx menunjuk ke `<direktori-proyek>/public`. Hanya folder `public/` yang boleh diakses web; `app/`, `writable/`, `vendor/`, dan `.env` harus berada di luar document root.
3. Buka terminal aaPanel, lalu jalankan Composer dari direktori proyek:

   ```bash
   cd <direktori-proyek>
   composer install --no-dev --optimize-autoloader --no-interaction
   ```

   Jika aaPanel menyediakan menu Composer, menu itu juga dapat dipakai. Pastikan Composer menggunakan PHP CLI `8.2` atau lebih baru dan ekstensi yang diperlukan sudah aktif.

### 4. Siapkan `.env` produksi

1. Salin `.env.example` menjadi `.env` di direktori proyek, atau buat `.env` baru di sana. Nama file harus persis `.env`, bukan `env` atau `.env.example`.
2. Atur nilai produksi berikut. Jangan memasukkan nilai rahasia ke dokumentasi atau Git:

   ```dotenv
   CI_ENVIRONMENT = production
   app.baseURL = 'https://<domain-anda>/'

   database.default.hostname = <host-database>
   database.default.database = <nama-database>
   database.default.username = <user-database>
   database.default.password = <password-database>
   database.default.DBDriver = MySQLi
   database.default.port = 3306

   encryption.key = <kunci-acak-64-karakter-hex>
   ```

   Isi placeholder dari informasi yang diberikan panel hosting. Buat encryption key baru sebelum menyimpan kredensial router, lalu simpan dengan aman dan jangan menggantinya setelah digunakan.

   Buat key melalui terminal:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

3. Pastikan `.env` tidak berada di `public/` dan tidak ikut di-commit. Gunakan `.env.example` hanya sebagai template tanpa password atau token aktif.

### 5. Jalankan migrasi database

Lakukan langkah berikut dari terminal aaPanel, menggunakan PHP CLI yang sama dengan versi website:

```bash
cd <direktori-proyek>
php spark migrate:status
```

1. Pastikan `.env` sudah menunjuk ke database aplikasi yang benar dan kredensialnya valid. Perintah `migrate:status` menampilkan migrasi yang belum dijalankan; jika koneksi gagal, periksa konfigurasi database dan izin user sebelum melanjutkan.
2. Sebelum migrasi pada database yang sudah berisi data, buat backup melalui aaPanel atau tool database.
3. Jalankan migrasi untuk membuat atau memperbarui skema:

   ```bash
   php spark migrate
   ```

4. Pada pemasangan pertama saja, buat data awal dan administrator:

   ```bash
   php spark db:seed MynetSeeder
   ```

   Migrasi membuat akun administrator utama dengan username `mynet` dan peran `superadmin`, menyimpan password sebagai hash, serta mewajibkan penggantian password setelah login pertama. Seeder tidak memerlukan variabel password administrator di `.env`.

5. Verifikasi status migrasi:

   ```bash
   php spark migrate:status
   ```

Semua migrasi aplikasi seharusnya menampilkan waktu pada kolom **Migrated On**. Seeder hanya dijalankan saat instalasi pertama; untuk deploy pembaruan, backup database, jalankan `php spark migrate`, lalu periksa kembali statusnya. Jangan menjalankan seeder berulang kali.

Administrator utama juga dapat membuka menu **Versi aplikasi** untuk melihat versi aplikasi dan migrasi tertunda, lalu menjalankan migrasi dari halaman tersebut. Buat backup database sebelum menekan tombol migrasi; tindakan itu mengubah skema database.

Contoh urutan perintah untuk instalasi pertama:

```bash
cd <direktori-proyek>
php spark migrate
php spark db:seed MynetSeeder
php spark migrate:status
```

### 6. Atur hak akses file

Pastikan `writable/` dan `public/uploads/` dapat ditulis oleh user PHP yang dipakai website di aaPanel. Gunakan pemilik user website dan permission direktori yang sesuai, misalnya `775`; jangan membuka permission `777` untuk seluruh proyek. Folder `app/` dan file `.env` tidak perlu diberi akses tulis oleh web server.

### 7. Atur rewrite Nginx

Buka konfigurasi atau **URL Rewrite** website yang dibuat di aaPanel. Tambahkan aturan berikut ke dalam blok `server {}` yang sudah ada. Jika sudah ada `location /`, ganti isinya agar tidak membuat blok duplikat:

```nginx
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

Simpan konfigurasi. Gunakan tombol uji konfigurasi Nginx di aaPanel bila tersedia; jika memakai terminal server, jalankan `nginx -t` sebelum reload Nginx. Jangan arahkan root Nginx ke akar repository.

### 8. Aktifkan HTTPS

1. Buka website di aaPanel, pilih **SSL**, lalu terbitkan sertifikat Let's Encrypt untuk domain aplikasi Anda.
2. Setelah sertifikat aktif, aktifkan pengalihan HTTP ke HTTPS.
3. Jika DNS memakai proxy seperti Cloudflare dan validasi sertifikat gagal, ubah record sementara menjadi **DNS only**, terbitkan sertifikat, lalu aktifkan kembali proxy setelah koneksi origin HTTPS berfungsi.

### 9. Aktifkan antrean WhatsApp (jika digunakan)

Atur `WA_PROVIDER` dan kredensial penyedia di `.env` atau melalui **Pengaturan → Integrasi API**. Di **Cron → Add Task** aaPanel, pilih tugas shell yang berjalan setiap menit dan gunakan perintah berikut. Ganti placeholder direktori dan path PHP CLI sesuai server:

```bash
flock -n /tmp/mynet-wa-queue.lock sh -c 'cd <direktori-proyek> && <path-php-cli> spark whatsapp:process-queue >> writable/logs/whatsapp-queue.log 2>&1'
```

### 10. Verifikasi aplikasi

1. Buka `https://<domain-anda>/` dan pastikan halaman login tampil.
2. Gunakan akun administrator awal berikut:

   - Username: `mynet`
   - Password: `<password awal yang dibagikan secara privat>`

   Password aktif tidak dicantumkan di repository publik. Setelah login, aplikasi mewajibkan penggantian password sebelum dashboard dapat dibuka.
3. Pastikan dashboard terbuka, data awal tampil, dan tidak ada error di log aplikasi `writable/logs/` maupun log PHP/web server aaPanel.
4. Jika memakai payment gateway, daftarkan callback hanya untuk provider yang diaktifkan:

   ```text
   https://<domain-anda>/payments/webhook/tripay
   https://<domain-anda>/payments/webhook/midtrans
   https://<domain-anda>/payments/webhook/xendit
   ```

### Pemecahan masalah singkat

- **404 saat membuka halaman selain login:** pastikan Running Directory atau root Nginx menunjuk ke `/public` dan rewrite Nginx aktif.
- **Error koneksi database:** periksa hostname, nama database, username, password, port, dan izin user database pada `.env` server.
- **Error 500 atau upload gagal:** periksa ekstensi PHP, versi PHP CLI, pemilik `writable/` dan `public/uploads/`, serta log aplikasi/PHP.
- **Sertifikat tidak terbit:** pastikan DNS domain aplikasi sudah menunjuk ke VPS dan port `80` dapat diakses dari internet.

Referensi: [Dokumentasi Website aaPanel](https://www.aapanel.com/docs/SubaaPanel/Website.html), [PHP Project aaPanel](https://www.aapanel.com/docs/Function/php.html), [persyaratan server CodeIgniter 4](https://codeigniter.com/user_guide/intro/requirements.html), [deployment CodeIgniter 4](https://www.codeigniter.com/user_guide/installation/deployment.html), dan [rewrite URL Nginx CodeIgniter](https://www.codeigniter.com/user_guide/general/urls.html).

## Opsional: konfigurasi development lokal

Konfigurasi ini opsional dan hanya diperlukan bila Anda ingin menjalankan salinan development di komputer. Deploy produksi aaPanel di atas tidak memerlukan pengujian lokal.

1. Pasang dependensi Composer:

   ```bash
   composer install
   ```

2. Salin `.env.example` menjadi `.env`, lalu sesuaikan `app.baseURL`, database, encryption key, dan password seeder.

   ```bash
   cp .env.example .env
   ```

   Alternatifnya, gunakan `.env.example` sebagai referensi konfigurasi. `.env` sengaja diabaikan Git.

3. Buat database MySQL/MariaDB dengan charset `utf8mb4`. Isi `.env` menggunakan kredensial database lingkungan lokal Anda.

4. Buat encryption key acak minimal 32 byte untuk kata sandi router. Contoh menghasilkan 64 karakter hex:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   Salin hasilnya ke `encryption.key` di `.env`. Jangan mengganti key setelah kredensial router terenkripsi, kecuali kredensial tersebut dienkripsi ulang.

5. Buat skema dan akun awal:

   ```bash
   php spark migrate
   php spark db:seed MynetSeeder
   ```

7. Jalankan server lokal:

   ```bash
   php spark serve --host 127.0.0.1 --port 8080
   ```

7. Buka `http://localhost:8080/` lalu masuk dengan username `mynet` dan password awal yang ditetapkan untuk instalasi. Pengguna awal akan diminta mengganti password sebelum membuka dashboard.

Document root web server harus diarahkan ke folder `public/`, bukan akar repo. Untuk produksi, gunakan `CI_ENVIRONMENT = production`, HTTPS, dan jangan aktifkan debug toolbar.

## Konfigurasi WhatsApp

Nilai `.env` berikut menjadi konfigurasi global cadangan. Integrasi WhatsApp khusus cabang diatur ketika membuat cabang dan dapat diperbarui pada menu **Integrasi API → WhatsApp per cabang**:

```dotenv
WA_PROVIDER = ngirimwa
NGIRIMWA_API_KEY = isi_api_key
```

atau:

```dotenv
WA_PROVIDER = fonnte
FONNTE_TOKEN = isi_token_perangkat
```

Konfigurasi global juga dapat dimasukkan melalui **Pengaturan → Integrasi API**. Rahasia yang sudah disimpan tidak ditampilkan lagi dan nilai pada input rahasia yang dikosongkan akan dipertahankan. Nomor pelanggan disimpan sesuai input; integrasi Fonnte menambahkan kode negara Indonesia `62` untuk nomor biasa. Pengingat tagihan tersedia dari daftar tagihan.

ID grup WhatsApp untuk teknisi, keuangan, dan admin diatur pada bagian **Grup penerima WhatsApp**. ID dapat ditetapkan per cabang dengan ID default sebagai fallback. Kedua penyedia menerima ID grup berakhiran `@g.us`; NgirimWA menyediakan daftar grup lewat endpoint `GET /groups`, sementara Fonnte perlu memperbarui daftar grup setelah perangkat bergabung ke grup. Jangan berulang kali memanggil pembaruan daftar Fonnte. Jika ID tidak diatur, sistem mengirim ke nomor staf aktif pada cabang. Penerimaan pesan oleh API penyedia belum membuktikan bahwa pesan telah dibaca.

Pesan terjadwal diproses oleh command CLI. Pasang cron setiap menit di server Linux:

```cron
* * * * * flock -n /tmp/mynet-wa-queue.lock sh -c 'cd /path/mynet && php spark whatsapp:process-queue >> writable/logs/whatsapp-queue.log 2>&1'
* * * * * flock -n /tmp/mynet-billing.lock sh -c 'cd /path/mynet && php spark billing:generate-recurring >> writable/logs/billing-recurring.log 2>&1'
```

Worker WhatsApp mengambil maksimal 50 pesan yang sudah waktunya per eksekusi, mencatat percobaan, dan memulihkan antrean yang berstatus `sending` lebih dari satu jam. Worker billing membuat tagihan pada tanggal jatuh tempo pelanggan; tagihan periode yang sudah ada dilewati. `flock` mencegah jadwal cron meluncurkan worker yang tumpang tindih.

## Konfigurasi payment gateway

Pilih tepat satu gateway aktif pada `.env`:

```dotenv
PAYMENT_PROVIDER = tripay
TRIPAY_MODE = sandbox
TRIPAY_API_KEY = ...
TRIPAY_PRIVATE_KEY = ...
TRIPAY_MERCHANT_CODE = ...
TRIPAY_METHOD = BRIVA
```

Pilihan lainnya:

```dotenv
PAYMENT_PROVIDER = midtrans
MIDTRANS_MODE = sandbox
MIDTRANS_SERVER_KEY = ...
```

```dotenv
PAYMENT_PROVIDER = xendit
XENDIT_SECRET_KEY = ...
XENDIT_WEBHOOK_TOKEN = ...
```

Daftarkan callback server pada dashboard penyedia dengan URL:

```text
https://domain-anda/payments/webhook/tripay
https://domain-anda/payments/webhook/midtrans
https://domain-anda/payments/webhook/xendit
```

Uji terlebih dahulu pada sandbox masing-masing gateway. Pastikan callback dapat diakses publik melalui HTTPS sebelum mengaktifkan transaksi produksi.

Kredensial juga dapat dimasukkan dari **Pengaturan → Integrasi API**. Halaman tersebut menguji kelengkapan konfigurasi, sedangkan transaksi dan callback tetap perlu diverifikasi melalui sandbox gateway.

## Konfigurasi RouterOS

Gunakan RouterOS v7, aktifkan layanan HTTPS `www-ssl`, pasang sertifikat yang dipercaya server aplikasi, dan buat user khusus REST dengan izin minimum untuk membaca resource sistem serta mengelola user Hotspot, PPP secrets, dan Simple Queue sesuai fitur yang digunakan. Saat menambah router, masukkan IP Public atau hostname VPN dan **port layanan HTTPS/REST** (umumnya port `www-ssl`, sering `443`; bukan port API biner `8728`). Batasi `available-from`/firewall router ke alamat server aplikasi atau VPN. Gunakan HTTPS dan biarkan verifikasi sertifikat aktif untuk penggunaan produksi.

Tambahkan router melalui menu **Router MikroTik** dengan endpoint seperti `https://alamat-router:443`, username API, password, dan cabang. Pilih router pada form generate voucher agar akun dibuat di router tersebut. Tanpa router dipilih, aplikasi hanya membuat voucher lokal.

## Struktur penting

Peta direktori utama repositori:

```text
mynet/
├── app/
│   ├── Commands/       # Perintah CLI, termasuk pemrosesan antrean WhatsApp
│   ├── Config/         # Konfigurasi CodeIgniter dan routing aplikasi
│   ├── Controllers/    # Login, dashboard, billing, integrasi, tiket, dan modul kerja
│   ├── Database/
│   │   ├── Migrations/ # Perubahan skema database
│   │   └── Seeds/      # Data awal aplikasi dan administrator
│   ├── Filters/        # Filter autentikasi dan API key
│   ├── Helpers/        # Fungsi bantuan aplikasi
│   ├── Libraries/      # Integrasi MikroTik, WhatsApp, payment, dan API key
│   └── Views/          # Halaman login, dashboard, pengaturan, invoice, dan tiket
├── public/             # Document root web: front controller dan aset publik
│   ├── assets/         # CSS dan JavaScript
│   └── uploads/        # File publik yang diunggah aplikasi
├── writable/           # Cache, log, sesi, dan file kerja; perlu izin tulis runtime
├── tests/              # Pengujian aplikasi (jika ditambahkan)
├── vendor/             # Dependensi Composer hasil instalasi, tidak disimpan di Git
├── .env                # Konfigurasi lokal/server; dibuat dari template dan tidak di-commit
├── .env.example        # Template konfigurasi tanpa kredensial aktif
├── env                # Template environment bawaan CodeIgniter
├── composer.json       # Dependensi dan metadata Composer
├── composer.lock       # Versi dependensi yang dikunci untuk instalasi konsisten
├── VERSION             # Versi aplikasi yang ditampilkan pada menu Versi aplikasi
├── spark               # CLI CodeIgniter untuk migrasi, seeder, dan perintah aplikasi
└── README.md           # Dokumentasi fitur, instalasi, dan struktur aplikasi
```

Folder `vendor/` dibuat dengan Composer di lingkungan tujuan. Folder `writable/` berisi data runtime dan tidak boleh dipakai sebagai document root. Arahkan Nginx hanya ke `public/`. File `.env` berisi konfigurasi khusus lingkungan dan tidak boleh dimasukkan ke Git; `.env.example` dan `env` adalah template, bukan konfigurasi aktif.

## API eksternal

Buat API key dari **Pengaturan → API eksternal**, pilih scope yang dibutuhkan, lalu kirim token sebagai `Authorization: Bearer <token>`. Token di-hash di database, terikat ke cabang pembuat, dan dapat dicabut.

| Method | Endpoint | Scope |
|---|---|---|
| `GET` | `/api/v1/ping` | Token aktif |
| `GET` | `/api/v1/customers` | `customers_read` |
| `GET` | `/api/v1/invoices` | `invoices_read` |
| `GET` | `/api/v1/tickets` | `tickets_read` |
| `POST` | `/api/v1/tickets` | `tickets_write` |

Contoh membuat tiket (kunci dengan scope `tickets_write`):

```bash
  curl -X POST https://domain-anda/api/v1/tickets \
  -H 'Authorization: Bearer mynet_live_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"branch_id":1,"title":"Gangguan koneksi","description":"Pelanggan tidak mendapat akses","priority":"high"}'
```

## Cetak invoice dan tiket maintenance

Menu **Tagihan** menyediakan tampilan cetak A4, cetak thermal 80 mm, dan unduhan PDF A4. Pelanggan hanya dapat membuka invoice miliknya; staf dibatasi pada cabang yang diizinkan.

Tiket maintenance tersedia untuk pelanggan, admin, NOC, dan teknisi. Pelanggan membuat laporan dari akunnya; staf dapat membuat tiket untuk cabang, administrator/NOC menugaskan teknisi, lalu teknisi mengubah status dan menambahkan catatan penyelesaian. API eksternal dengan scope `tickets_write` dapat membuat tiket dan memicu notifikasi antrean.

## Referensi API

- [CodeIgniter 4](https://codeigniter.com/user_guide/)
- [MikroTik RouterOS REST API](https://manual.mikrotik.com/docs/developer-guides/rest-api/)
- [NgirimWA API](https://panduan.ngirimwa.com/api/kirim-pesan)
- [Fonnte: kirim pesan API](https://docs.fonnte.com/api-send-message/)
- [Fonnte: melihat ID grup WhatsApp](https://docs.fonnte.com/api-get-whatsapp-group-list/)
- [Dompdf](https://github.com/dompdf/dompdf)
- [Tripay Developer Guide](https://tripay.co.id/developer)
- [Midtrans Snap integration](https://docs.midtrans.com/docs/snap-snap-integration-guide) dan [notifikasi transaksi](https://docs.midtrans.com/docs/https-notification-webhooks)
- [Xendit API reference](https://developers.xendit.co/api-reference/)

- [NgirimWA: kirim pesan dan ID grup](https://panduan.ngirimwa.com/api/grup-whatsapp)
- [Fonnte: memperbarui daftar grup](https://docs.fonnte.com/api-update-whatsapp-group-list/)
