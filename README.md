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

- Profil pelanggan memuat cabang, paket, layanan PPPoE atau hotspot, harga paket, nomor WhatsApp, alamat, username MikroTik, dan status.
- Paket layanan menyimpan tarif bulanan, jenis koneksi, kecepatan, dan profil hotspot.
- Generate tagihan bulanan membuat satu tagihan per pelanggan aktif untuk periode terpilih. Tagihan ganda untuk pelanggan dan periode yang sama dilewati.
- Pembayaran tagihan menerima input kasir/transfer manual atau tautan checkout payment gateway. Pembayaran sebagian didukung.
- Webhook gateway memeriksa signature/token, mencocokkan jumlah pembayaran, menolak referensi yang tidak dikenal, memperbarui status tagihan, dan mengabaikan event berulang.
- Pengingat tagihan WhatsApp dapat dikirim dari daftar tagihan. Status antrean dan kegagalan dicatat di log pesan.
- Invoice dapat dicetak langsung pada format thermal 80 mm atau A4, dan tersedia sebagai PDF A4.
- Administrator dapat mengatur nama aplikasi, logo, favicon, tautan sosial, gambar preview Open Graph, canonical URL, robots, verifikasi Google Search Console, dan Google Tag/Tag Manager dari menu Pengaturan aplikasi.
- API key eksternal memiliki scope dan batas cabang. Secret ditampilkan sekali saat dibuat, disimpan sebagai hash, dan dapat dicabut.

### MikroTik dan voucher hotspot

- Detail router disimpan per cabang. Kata sandi API router dienkripsi memakai application encryption key CodeIgniter.
- Generate 1–100 voucher dengan username dan kata sandi acak.
- Voucher dapat disimpan untuk ekspor/pemakaian manual atau langsung dibuat sebagai user hotspot di RouterOS v7 melalui REST API.
- Status `sync_failed` dicatat bila perangkat menolak atau tidak dapat dijangkau; kegagalan tidak menghapus voucher lokal.
- Sinkronisasi langsung hanya mencakup user Hotspot (`/ip/hotspot/user`). Pengelolaan PPPoE atau isolasi otomatis saat tagihan terlambat belum termasuk.

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

- **MikroTik RouterOS v7 REST API** untuk membuat akun hotspot. Router dan kredensialnya dikonfigurasi per cabang di aplikasi.
- **NgirimWA** melalui endpoint kirim pesan dan header API key.
- **Fonnte** (kadang ditulis Fonte) melalui endpoint kirim pesan dan token perangkat.
- **Tripay** untuk membuat transaksi pembayaran tertutup dan menerima callback.
- **Midtrans Snap** untuk membuat checkout dan menerima notifikasi transaksi.
- **Xendit Invoice API** untuk membuat invoice dan menerima webhook.
- Kredensial integrasi dapat diatur dari halaman Integrasi API dan disimpan terenkripsi menggunakan `encryption.key`. `.env` tetap dapat dipakai sebagai konfigurasi cadangan saat belum ada konfigurasi database.
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

## Instalasi lokal

1. Pasang dependensi Composer:

   ```bash
   composer install
   ```

2. Salin `.env.example` menjadi `.env`, lalu sesuaikan `app.baseURL`, database, encryption key, dan password seeder.

   ```bash
   cp .env.example .env
   ```

   Alternatifnya, gunakan `.env.example` sebagai referensi konfigurasi. `.env` sengaja diabaikan Git.

3. Buat database MySQL/MariaDB `sql_mynet_ekiguistian_com` dengan charset `utf8mb4`. Isi kredensial pada `.env`.

4. Buat encryption key acak minimal 32 byte untuk kata sandi router. Contoh menghasilkan 64 karakter hex:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   Salin hasilnya ke `encryption.key` di `.env`. Jangan mengganti key setelah kredensial router terenkripsi, kecuali kredensial tersebut dienkripsi ulang.

5. Atur `MYNET_SEED_ADMIN_PASSWORD` di `.env` ke password unik minimal 12 karakter.

6. Buat skema dan akun awal:

   ```bash
   php spark migrate
   php spark db:seed MynetSeeder
   ```

7. Jalankan server lokal:

   ```bash
   php spark serve --host 127.0.0.1 --port 8080
   ```

8. Buka `http://localhost:8080/` lalu masuk dengan username `administrator@mynet.local` dan password yang diatur pada langkah 5. Pengguna awal akan diminta mengganti password sebelum membuka dashboard.

Document root web server harus diarahkan ke folder `public/`, bukan akar repo. Untuk produksi, gunakan `CI_ENVIRONMENT = production`, HTTPS, dan jangan aktifkan debug toolbar.

## Deploy ke aaPanel (`mynet.ekiguistian.com`)

Panduan ini menggunakan database MySQL/MariaDB yang sudah dibuat, `sql_mynet_ekiguistian_com`. Ganti nilai user database dan password dengan kredensial yang sudah disimpan pada `.env` server. Jangan menyalin password, encryption key, atau token aktif ke README atau Git.

### 1. Arahkan domain ke server

1. Pada pengelola DNS domain `ekiguistian.com`, buat record `A` untuk host `mynet` yang menunjuk ke alamat IPv4 publik VPS aaPanel.
2. Jika server memakai IPv6, tambahkan record `AAAA` yang benar. Hapus record `AAAA` yang tidak digunakan agar pengunjung tidak diarahkan ke server yang salah.
3. Pastikan firewall VPS dan firewall penyedia hosting mengizinkan koneksi TCP pada port `80` dan `443`.
4. Tunggu hingga DNS mengarah ke alamat server sebelum meminta sertifikat SSL.

### 2. Buat website PHP di aaPanel

1. Buka **Website → PHP Project** (atau **Website → Add Site**, bergantung pada versi aaPanel), lalu tambahkan domain `mynet.ekiguistian.com`.
2. Gunakan direktori proyek `/www/wwwroot/mynet.ekiguistian.com` dan pilih PHP `8.2` atau lebih baru.
3. Pada pengaturan versi PHP di aaPanel, pastikan ekstensi `intl`, `mbstring`, `mysqli`/`mysqlnd`, `curl`, `openssl`, `json`, `fileinfo`, dan `dom` aktif. Gunakan versi PHP CLI yang sama dengan versi PHP website.
4. Database tidak perlu dibuat ulang. Pastikan user database yang sudah dibuat memiliki izin koneksi dan perubahan skema pada `sql_mynet_ekiguistian_com`.

### 3. Unggah kode aplikasi

1. Unggah atau clone isi repositori ke `/www/wwwroot/mynet.ekiguistian.com`. Pastikan `composer.json`, `spark`, `app/`, `public/`, dan `writable/` berada langsung di direktori proyek tersebut, bukan di subfolder tambahan.
2. Atur document root website ke `/www/wwwroot/mynet.ekiguistian.com/public`. Jika versi aaPanel menampilkan pilihan **Running Directory** relatif terhadap direktori situs, pilih `public`. Web server hanya boleh mengekspos folder `public/`; folder `app/`, `writable/`, `vendor/`, dan `.env` harus tetap di luar document root.
3. Buka terminal aaPanel, lalu jalankan Composer dari direktori proyek:

   ```bash
   cd /www/wwwroot/mynet.ekiguistian.com
   composer install --no-dev --optimize-autoloader --no-interaction
   ```

   Jika aaPanel menyediakan menu Composer, menu itu juga dapat dipakai. Pastikan Composer menggunakan PHP CLI `8.2` atau lebih baru dan ekstensi yang diperlukan sudah aktif.

### 4. Siapkan `.env` produksi

1. Salin `.env.example` menjadi `.env` di direktori proyek, atau buat `.env` baru di sana. Nama file harus persis `.env`, bukan `env` atau `.env.example`.
2. Atur nilai produksi berikut. Jangan memasukkan nilai rahasia ke dokumentasi atau Git:

   ```dotenv
   CI_ENVIRONMENT = production
   app.baseURL = 'https://mynet.ekiguistian.com/'

   database.default.hostname = localhost
   database.default.database = sql_mynet_ekiguistian_com
   database.default.username = USER_DATABASE_AAPANEL
   database.default.password = PASSWORD_DATABASE_AAPANEL
   database.default.DBDriver = MySQLi
   database.default.port = 3306

   encryption.key = HASIL_RANDOM_64_KARAKTER_HEX
   MYNET_SEED_ADMIN_PASSWORD = PASSWORD_ADMIN_UNIK_MINIMAL_12_KARAKTER
   ```

   Jika database berada di host lain, ganti `localhost` dan port sesuai konfigurasi hosting. Password administrator seed dipakai untuk akun awal `administrator@mynet.local`; akun akan diminta mengganti password saat login pertama. Buat encryption key baru sebelum kredensial router disimpan, lalu simpan dengan aman dan jangan menggantinya setelah digunakan.

   Buat key melalui terminal:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

3. Pastikan `.env` tidak berada di `public/` dan tidak ikut di-commit. Gunakan `.env.example` hanya sebagai template tanpa password atau token aktif.

### 5. Jalankan migrasi database

Jalankan dari direktori proyek menggunakan PHP CLI versi website:

```bash
cd /www/wwwroot/mynet.ekiguistian.com
php spark migrate
php spark db:seed MynetSeeder
```

Perintah seeder hanya dijalankan pada pemasangan pertama untuk membuat data awal dan administrator. Untuk deploy berikutnya, buat backup database terlebih dahulu, lalu jalankan migrasi yang baru tanpa menjalankan seeder lagi.

### 6. Atur hak akses file

Pastikan `writable/` dan `public/uploads/` dapat ditulis oleh user PHP yang dipakai website di aaPanel. Gunakan pemilik user website dan permission direktori yang sesuai, misalnya `775`; jangan membuka permission `777` untuk seluruh proyek. Folder `app/` dan file `.env` tidak perlu diberi akses tulis oleh web server.

### 7. Atur URL rewrite

- Untuk Apache, pastikan `public/.htaccess` ikut terunggah dan `mod_rewrite` aktif.
- Untuk Nginx, tambahkan aturan berikut ke konfigurasi website di dalam blok `server {}` agar rute CodeIgniter diteruskan ke `index.php`:

  ```nginx
  location / {
      try_files $uri $uri/ /index.php?$query_string;
  }
  ```

  Simpan konfigurasi dan reload Nginx dari aaPanel. Jangan mengarahkan domain ke akar proyek.

### 8. Aktifkan HTTPS

1. Buka website di aaPanel, pilih **SSL**, lalu terbitkan sertifikat Let's Encrypt untuk `mynet.ekiguistian.com`.
2. Setelah sertifikat aktif, aktifkan pengalihan HTTP ke HTTPS.
3. Jika DNS memakai proxy seperti Cloudflare dan validasi sertifikat gagal, ubah record sementara menjadi **DNS only**, terbitkan sertifikat, lalu aktifkan kembali proxy setelah koneksi origin HTTPS berfungsi.

### 9. Aktifkan antrean WhatsApp (jika digunakan)

Atur `WA_PROVIDER` dan kredensial penyedia di `.env` atau melalui **Pengaturan → Integrasi API**. Di **Cron → Add Task** aaPanel, pilih tugas shell yang berjalan setiap menit dan gunakan perintah berikut. Sesuaikan path PHP CLI jika nama versi yang terpasang berbeda:

```bash
flock -n /tmp/mynet-wa-queue.lock sh -c 'cd /www/wwwroot/mynet.ekiguistian.com && /www/server/php/82/bin/php spark whatsapp:process-queue >> writable/logs/whatsapp-queue.log 2>&1'
```

### 10. Verifikasi aplikasi

1. Buka `https://mynet.ekiguistian.com/` dan pastikan halaman login tampil.
2. Masuk dengan `administrator@mynet.local` dan password awal dari `MYNET_SEED_ADMIN_PASSWORD`, lalu ganti password ketika diminta.
3. Pastikan dashboard terbuka, data awal tampil, dan tidak ada error di log aplikasi `writable/logs/` maupun log PHP/web server aaPanel.
4. Jika memakai payment gateway, daftarkan callback hanya untuk provider yang diaktifkan:

   ```text
   https://mynet.ekiguistian.com/payments/webhook/tripay
   https://mynet.ekiguistian.com/payments/webhook/midtrans
   https://mynet.ekiguistian.com/payments/webhook/xendit
   ```

### Pemecahan masalah singkat

- **404 saat membuka halaman selain login:** pastikan document root berakhir dengan `/public` dan aturan rewrite Nginx/Apache aktif.
- **Error koneksi database:** periksa hostname, nama database, username, password, port, dan izin user database pada `.env` server.
- **Error 500 atau upload gagal:** periksa ekstensi PHP, versi PHP CLI, pemilik `writable/` dan `public/uploads/`, serta log aplikasi/PHP.
- **Sertifikat tidak terbit:** pastikan DNS `mynet.ekiguistian.com` sudah menunjuk ke VPS dan port `80` dapat diakses dari internet.

Referensi: [Dokumentasi Website aaPanel](https://www.aapanel.com/docs/SubaaPanel/Website.html), [PHP Project aaPanel](https://www.aapanel.com/docs/Function/php.html), [persyaratan server CodeIgniter 4](https://codeigniter.com/user_guide/intro/requirements.html), dan [deployment CodeIgniter 4](https://www.codeigniter.com/user_guide/installation/deployment.html).

## Konfigurasi WhatsApp

Atur satu penyedia berikut dalam `.env`:

```dotenv
WA_PROVIDER = ngirimwa
NGIRIMWA_API_KEY = isi_api_key
```

atau:

```dotenv
WA_PROVIDER = fonnte
FONNTE_TOKEN = isi_token_perangkat
```

Konfigurasi juga dapat dimasukkan melalui **Pengaturan → Integrasi API**. Rahasia yang sudah disimpan tidak ditampilkan lagi dan nilai pada input rahasia yang dikosongkan akan dipertahankan. Nomor pelanggan disimpan sesuai input; integrasi Fonnte menambahkan kode negara Indonesia `62` untuk nomor biasa. Pengingat tagihan tersedia dari daftar tagihan.

ID grup WhatsApp untuk teknisi, keuangan, dan admin diatur pada bagian **Grup penerima WhatsApp**. ID dapat ditetapkan per cabang dengan ID default sebagai fallback. Kedua penyedia menerima ID grup berakhiran `@g.us`; NgirimWA menyediakan daftar grup lewat endpoint `GET /groups`, sementara Fonnte perlu memperbarui daftar grup setelah perangkat bergabung ke grup. Jangan berulang kali memanggil pembaruan daftar Fonnte. Jika ID tidak diatur, sistem mengirim ke nomor staf aktif pada cabang. Penerimaan pesan oleh API penyedia belum membuktikan bahwa pesan telah dibaca.

Pesan terjadwal diproses oleh command CLI. Pasang cron setiap menit di server Linux:

```cron
* * * * * flock -n /tmp/mynet-wa-queue.lock sh -c 'cd /path/mynet && php spark whatsapp:process-queue >> writable/logs/whatsapp-queue.log 2>&1'
```

Worker mengambil maksimal 50 pesan yang sudah waktunya per eksekusi, mencatat percobaan, dan memulihkan antrean yang berstatus `sending` lebih dari satu jam. `flock` mencegah jadwal cron meluncurkan worker yang tumpang tindih.

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

Gunakan RouterOS v7, aktifkan layanan HTTPS `www-ssl`, pasang sertifikat yang dipercaya server aplikasi, dan buat user khusus REST dengan izin minimum yang diperlukan untuk menulis user Hotspot. Batasi `available-from`/firewall router ke alamat server aplikasi atau VPN. Gunakan HTTPS dan biarkan verifikasi sertifikat aktif untuk penggunaan produksi.

Tambahkan router melalui menu **Router MikroTik** dengan endpoint seperti `https://alamat-router:443`, username API, password, dan cabang. Pilih router pada form generate voucher agar akun dibuat di router tersebut. Tanpa router dipilih, aplikasi hanya membuat voucher lokal.

## Struktur penting

- `app/Controllers/` — login, dashboard, area kerja, billing, stok, absensi, voucher, webhook, dan notifikasi.
- `app/Libraries/` — adapter payment gateway, WhatsApp, dan RouterOS.
- `app/Database/Migrations/` — skema modul billing dan operasional.
- `app/Database/Seeds/MynetSeeder.php` — cabang awal, paket contoh, aturan bonus awal, dan administrator utama.
- `app/Views/` — halaman responsif Bahasa Indonesia.
- `public/assets/` — stylesheet dan skrip antarmuka.
- `.env.example` — daftar konfigurasi rahasia dan koneksi lokal; tidak berisi kredensial aktif.

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
