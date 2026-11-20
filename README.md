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

## Fitur wajib dan status implementasi

Semua persyaratan utama berikut sudah memiliki alur aplikasi di panel Filament. Integrasi eksternal memerlukan kredensial milik perusahaan sebelum dapat dipakai di produksi.

1. [x] **Dashboard umum** — widget ringkasan pelanggan, tagihan, pembayaran, tiket, stok minimum, dan pekerjaan selesai sesuai hak akses.
2. [x] **Pelanggan dan tagihan** — CRUD pelanggan PPPoE/IP statis, paket/router, tagihan bulanan idempoten, pembayaran manual, dan checkout gateway.
3. [x] **Barang dan inventaris** — CRUD barang, pencatatan masuk/keluar yang memperbarui stok, penolakan stok negatif, dan rekap pergerakan.
4. [x] **Keuangan** — kategori, pendapatan/pengeluaran, arus kas, serta filter laporan harian, mingguan, bulanan, triwulanan, tahunan, dan seluruh periode.
5. [x] **Paket internet** — kelola paket, harga, kecepatan, profil layanan, dan status paket.
6. [x] **Cabang** — kelola cabang, koordinat, dan radius absensi.
7. [x] **Teknisi dan tiket** — kelola teknisi, tiket dan work order; laporan per teknisi mencakup tiket, pekerjaan, dan absensi menurut periode.
8. [x] **Absensi karyawan** — check-in/pulang dengan waktu, GPS dalam radius cabang, foto, IP, catatan, serta pengelolaan absensi sesuai peran.
9. [x] **Pengaturan aplikasi** — merek `mynet` dikunci; tersedia judul/deskripsi/kata kunci SEO, verifikasi Google Search Console, media sosial, dan telepon layanan.
10. [x] **Manajemen pengguna dan peran** — administrator, admin, finance, sales, teknisi, helper, manager, dan NOC dengan izin fitur.
11. [x] **Cakupan cabang** — manager tanpa cabang dapat melihat data lintas cabang; manager terikat cabang dibatasi ke data operasional cabangnya. Paket internet dan kategori keuangan tanpa cabang adalah data master bersama.
12. [x] **Integrasi** — sinkronisasi akun PPPoE dan tes RouterOS, checkout/webhook Midtrans/Xendit/TriPay, WhatsApp OTP, dan SMTP email. Simpan kredensial melalui halaman Integrasi.
13. [x] **Profil pengguna** — ubah nama, kata sandi, email, nomor WhatsApp, dan foto; perubahan kontak diverifikasi OTP ke kontak lama serta baru. Login pertama memaksa perubahan kata sandi dan verifikasi email.
14. [x] **Pengaturan perusahaan** — profil nama perusahaan, pemilik, penanggung jawab, NOC, CS, email, alamat, dan media sosial.
15. [x] **Versi, database, dan lisensi** — tampilkan/simpan versi semver, jalankan migrasi, sediakan tautan ke repository untuk alur pull request, dan tampilkan lisensi MIT. Pemasangan kode baru dilakukan melalui alur repository/deployment, bukan perintah `git pull` otomatis dari panel.
16. [x] **Log aplikasi** — log perubahan dan login tersimpan; hanya Administrator global yang dapat membuka halaman log.

**Hasil:** implementasi mencakup resource CRUD, pembatasan akses per peran/cabang, laporan, command penagihan terjadwal, halaman pengaturan, serta alur integrasi dan OTP. Pembuatan akun administrator awal memakai command `php artisan mynet:make-administrator`. Sebelum dipakai, isi `.env`, atur integrasi, koordinat cabang, dan jalankan scheduler.

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
   php artisan storage:link
   ```

6. Buat akun Administrator pertama:

   ```sh
   php artisan mynet:make-administrator
   ```

   Command ini meminta nama, email, dan kata sandi kuat, lalu mengharuskan perubahan kata sandi serta verifikasi email pada login pertama.

7. Jalankan aplikasi untuk pengembangan:

   ```sh
   php artisan serve
   ```

   Buka `http://127.0.0.1:8000/admin`.

## Penjadwalan

Penagihan membuat tagihan bulanan pada tanggal 1 pukul 00:10. Jalankan Laravel Scheduler setiap menit melalui cron:

```cron
* * * * * cd /path/to/mynet && php artisan schedule:run >> /dev/null 2>&1
```

## Kode legacy

Sumber CodeIgniter dipertahankan di `_legacy_code/` sebagai referensi selama migrasi. Folder `.git` tetap berada di root repository. File `.env`, `vendor`, dan `node_modules` legacy tidak perlu dipublikasikan.

## Nama dan pengaturan aplikasi

Nama merek dikunci sebagai `mynet`. Halaman pengaturan hanya akan mengelola meta SEO, nomor telepon, email, dan tautan media sosial, tanpa field untuk mengganti nama aplikasi.
