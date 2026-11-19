# mynet — Billing ISP

## Fitur wajib aplikasi

1. **Dashboard umum** untuk ringkasan operasional dan informasi penting aplikasi.
2. **Pelanggan dan tagihan:** pengelolaan pelanggan PPPoE dan IP statis, layanan, serta tagihan.
3. **Persediaan:** pengelolaan barang masuk, barang keluar, dan laporan persediaan.
4. **Keuangan:** pendapatan, pengeluaran, kategori, arus kas, dan laporan harian, mingguan, bulanan, per 3 bulan, tahunan, serta seluruh periode.
5. **Paket internet:** pengelolaan paket dan layanan internet.
6. **Cabang:** pengelolaan data dan pengaturan cabang.
7. **Teknisi dan tiket:** pengelolaan teknisi dan tiket, laporan tiket per teknisi, serta laporan absensi per teknisi dengan rentang mingguan, bulanan, per 3 bulan, tahunan, dan seluruh periode.
8. **Absensi karyawan:** mendukung teknisi, admin, finance, sales, NOC, manager, helper, dan peran lain; mencatat jam masuk dan pulang, koordinat lokasi atau cabang, foto, serta informasi absensi lainnya.
9. **Pengaturan aplikasi:** nama aplikasi permanen **mynet**, SEO, meta tag, deskripsi, Google Search, media sosial, dan nomor telepon.
10. **Pengguna dan peran:** pengelolaan administrator, admin, finance, sales, teknisi, helper, dan manager.
11. **Akses berbasis cabang:** manager dapat ditetapkan ke cabang atau tidak. Jika terikat ke cabang, seluruh fitur hanya menampilkan dan mengelola data cabang tersebut.
12. **Integrasi:** pengelolaan router MikroTik, payment gateway, WhatsApp gateway, dan SMTP email.
13. **Profil pengguna dan verifikasi:** pengguna dapat mengubah kata sandi, email, nomor WhatsApp, nama, dan foto profil. Perubahan email atau nomor WhatsApp harus diverifikasi melalui OTP ke alamat atau nomor lama dan baru. Pada login pertama, pengguna wajib mengganti kata sandi dan memverifikasi email.
14. **Pengaturan perusahaan:** nama perusahaan, pemilik, nomor telepon pemilik, penanggung jawab, NOC, dan CS, email, alamat, serta media sosial.
15. **Versi dan pemeliharaan aplikasi:** menampilkan versi, menyediakan pengelolaan migrasi atau pembaruan database, serta alur pull request GitHub atau pembaruan versi. Lisensi MIT harus dicantumkan pada deskripsi lisensi.
16. **Log aplikasi:** log khusus yang hanya dapat diakses administrator.

`mynet` sedang dimigrasikan dari CodeIgniter ke **Laravel 13** dengan **FilamentPHP v3** sebagai panel admin. Nama aplikasi ditetapkan sebagai `mynet` dan tidak dapat diubah dari pengaturan aplikasi. Panel administrator tersedia di `/admin`; halaman `/` mengarah ke panel tersebut.

## Cakupan fitur

Fitur legacy yang menjadi sasaran migrasi meliputi:

- Pelanggan, cabang/area, paket PPPoE dan IP statis, serta sinkronisasi akun dan isolir MikroTik.
- Router MikroTik, voucher hotspot, pemantauan router, ODC/ODP, port pelanggan, dan serial ONT/ONU.
- Tagihan, pembayaran manual dan gateway Tripay/Midtrans/Xendit, invoice PDF, dan buku kas.
- Stok barang dan mutasi, absensi, tugas teknisi, tiket maintenance, bonus teknisi, dan sales fee.
- WhatsApp NgirimWA/Fonnte, template pesan, antrean terjadwal, dan pengaturan cabang.
- Pengaturan SEO, kontak, media sosial, serta API eksternal.

**Status migrasi:** fondasi Laravel, panel Filament, paket integrasi, skema database, dan model Eloquent sudah disiapkan. Service bisnis, resource CRUD, widget dashboard, dan otomasi scheduler masih dalam proses porting; daftar fitur di atas adalah cakupan migrasi, bukan klaim bahwa seluruh layar sudah selesai.

## Teknologi

- Laravel 13
- FilamentPHP 3.3
- PHP 8.3 atau lebih baru
- MySQL/MariaDB dengan tabel InnoDB
- `evilfreelancer/routeros-api-php` untuk RouterOS API
- `barryvdh/laravel-dompdf` untuk dokumen PDF

## Instalasi pengembang

1. Siapkan PHP dan ekstensi Laravel yang dibutuhkan, Composer, serta MySQL/MariaDB.
2. Pasang dependency:

   ```sh
   composer install
   ```

3. Salin `.env.example` menjadi `.env`, lalu atur `APP_URL`, `APP_KEY`, dan kredensial database. Jangan masukkan kredensial produksi ke Git.
4. Gunakan **database kosong khusus Laravel**. Jangan arahkan `php artisan migrate` ke database CodeIgniter lama karena database tersebut sudah memiliki tabel billing dengan skema berbeda. Pertahankan database lama sebagai sumber data/referensi sampai prosedur pemindahan data disiapkan.
5. Buat application key dan jalankan migrasi pada database Laravel yang kosong:

   ```sh
   php artisan key:generate
   php artisan migrate
   ```

6. Buat akun administrator Filament:

   ```sh
   php artisan make:filament-user
   ```

7. Jalankan aplikasi untuk pengembangan:

   ```sh
   php artisan serve
   ```

   Buka `http://127.0.0.1:8000/admin`.

## Penjadwalan

Setelah command billing dan isolir otomatis selesai dipasang, jalankan Laravel Scheduler setiap menit melalui cron:

```cron
* * * * * cd /path/to/mynet && php artisan schedule:run >> /dev/null 2>&1
```

## Kode legacy

Sumber CodeIgniter dipertahankan di `_legacy_code/` sebagai referensi selama migrasi. Folder `.git` tetap berada di root repository. File `.env`, `vendor`, dan `node_modules` legacy tidak perlu dipublikasikan.

## Nama dan pengaturan aplikasi

Nama merek dikunci sebagai `mynet`. Halaman pengaturan hanya akan mengelola meta SEO, nomor telepon, email, dan tautan media sosial, tanpa field untuk mengganti nama aplikasi.
