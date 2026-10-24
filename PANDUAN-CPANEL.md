# Panduan Instalasi Ruci PRIMA di cPanel

Panduan ini untuk hosting cPanel biasa (shared hosting atau VPS dengan cPanel). Ada dua jalur: **dengan Terminal/SSH** (lebih mudah) dan **tanpa Terminal** (memakai File Manager dan Cron Jobs sekali jalan).

Contoh di panduan memakai akun cPanel `userku`, folder program `/home/userku/ruci-prima`, dan alamat `https://prima.domainanda.com`. Sesuaikan dengan milik Anda.

## 1. Prasyarat hosting

| Komponen | Ketentuan | Tempat di cPanel |
|---|---|---|
| PHP | **8.3 atau lebih baru** | *MultiPHP Manager* (pilih domain › PHP 8.3) atau *Select PHP Version* (CloudLinux) |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `openssl`, `bcmath`, `ctype`, `tokenizer`, `xml`, `dom`, `curl` | *Select PHP Version › Extensions* (terutama **intl** dan **fileinfo**) |
| Database | **MySQL 8** atau MariaDB 10.6+ | *MySQL Databases* |
| SSL | Sangat disarankan (kamera HP untuk foto laporan QR butuh HTTPS) | *SSL/TLS Status* › Run AutoSSL |
| Cron | **Tidak wajib.** Hanya dipakai sekali jalan bila tidak ada Terminal | *Cron Jobs* |

Tidak perlu Node.js, Redis, atau ekstensi GD/Imagick: kode QR dibuat sebagai SVG dengan PHP murni.

## 2. Buat database

1. Buka **MySQL Databases**.
2. *Create New Database*: misalnya `prima` (cPanel menjadikannya `userku_prima`).
3. *Add New User*: misalnya `prima` (menjadi `userku_prima`), kata sandi kuat. Catat.
4. *Add User To Database*: pilih user dan database tadi › centang **ALL PRIVILEGES** › Make Changes.

## 3. Taruh program di server

Program diletakkan **di luar `public_html`** (misalnya `/home/userku/ruci-prima`), supaya `.env` dan kode tidak bisa dibuka dari browser.

### Jalur A: dengan Terminal (cPanel › Terminal, atau SSH)

```bash
cd ~
git clone https://github.com/rucisoft-web/ruci-prima.git
cd ruci-prima
composer install --no-dev --optimize-autoloader
```

Bila repo privat, `git clone` meminta username GitHub dan *personal access token*. Bila perintah `composer` tidak ada: `curl -sS https://getcomposer.org/installer | php` lalu `php composer.phar install --no-dev --optimize-autoloader`.

Bila `php -v` di Terminal bukan 8.3, panggil PHP 8.3 langsung, misalnya `/opt/cpanel/ea-php83/root/usr/bin/php` (EasyApache) atau `/opt/alt/php83/usr/bin/php` (CloudLinux).

### Jalur B: tanpa Terminal (File Manager)

1. Pakai paket **`ruci-prima-cpanel.zip`** (sudah berisi folder `vendor` versi produksi, tidak perlu Composer).
2. *File Manager* › buka `/home/userku` › **Upload** zip tersebut.
3. Klik kanan zip › **Extract** ke `/home/userku`. Hasilnya folder `/home/userku/ruci-prima`.
4. Hapus file zip setelah diekstrak.

## 4. Arahkan domain ke folder `public`

### Pilihan 1 (disarankan): subdomain atau addon domain

1. *Domains* › **Create A New Domain**, misalnya `prima.domainanda.com`.
2. Matikan centang *Share document root*, lalu isi **Document Root**: `ruci-prima/public`.
3. Simpan, lalu aktifkan SSL di *SSL/TLS Status*.

### Pilihan 2: domain utama yang document root-nya terkunci di `public_html`

1. Salin **seluruh isi** `ruci-prima/public` ke `public_html`, termasuk file tersembunyi `.htaccess` (File Manager › *Settings* › centang *Show Hidden Files*).
2. Edit `public_html/index.php`, ganti tiga baris yang berisi `__DIR__.'/../` menjadi:

```php
if (file_exists($maintenance = __DIR__.'/../ruci-prima/storage/framework/maintenance.php')) {
require __DIR__.'/../ruci-prima/vendor/autoload.php';
$app = require_once __DIR__.'/../ruci-prima/bootstrap/app.php';
```

3. Setiap kali memperbarui program, salin ulang isi `ruci-prima/public` ke `public_html` (kecuali `index.php` yang sudah diedit).

> Jangan mengekstrak seluruh program langsung ke `public_html`. Bila terlanjur, `.htaccess` di folder program menolak akses ke `.env`, `vendor`, dan `storage`, tetapi tetap pindahkan secepatnya.

## 5. Isi file `.env`

Di folder `ruci-prima`, salin `.env.example` menjadi `.env` (File Manager › klik kanan › *Copy*, lalu *Rename*), kemudian *Edit*:

```dotenv
APP_NAME="Ruci PRIMA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://prima.domainanda.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=userku_prima
DB_USERNAME=userku_prima
DB_PASSWORD="kata-sandi-database"

SESSION_SECURE_COOKIE=true

# MFA (Google/Microsoft Authenticator). true = wajib bagi semua akun.
ASSET_MFA_REQUIRED=false

# Admin platform pertama (dibuat saat migrate --seed). Kosongkan lagi setelah instalasi.
ASSET_ADMIN_EMAIL=admin@domainanda.com
ASSET_ADMIN_PASSWORD="SandiSementara#2026"

# Data contoh Hotel Sahid, Blue Bird, dan Diamond Food. false untuk pemakaian sungguhan.
ASSET_DEMO=false
```

**Penting:** `APP_URL` harus alamat yang bisa dibuka dari HP staf. Alamat ini tercetak di setiap label QR; bila salah, label harus dicetak ulang.

`APP_KEY=` dibiarkan kosong; diisi oleh `key:generate` di langkah 6. Kata sandi yang mengandung `#` atau spasi harus diberi tanda kutip.

## 6. Kunci aplikasi, tabel, dan admin

### Dengan Terminal

```bash
cd ~/ruci-prima
php artisan key:generate --force
php artisan migrate --seed --force
php artisan optimize
php artisan prima:doctor
```

Admin platform juga bisa dibuat kapan saja: `php artisan prima:create-admin admin@domainanda.com "Admin Platform" --password='SandiKuat#2026'`.

### Tanpa Terminal (Cron Jobs sekali jalan)

Tambahkan perintah berikut satu per satu di **Cron Jobs** dengan jadwal *Once Per Minute*. Tunggu 1–2 menit, periksa hasilnya, lalu **hapus** cron itu sebelum menambah yang berikutnya.

1. `cd /home/userku/ruci-prima && php artisan key:generate --force > storage/logs/setup-1.txt 2>&1`
2. `cd /home/userku/ruci-prima && php artisan migrate --seed --force > storage/logs/setup-2.txt 2>&1`
3. `cd /home/userku/ruci-prima && php artisan optimize > storage/logs/setup-3.txt 2>&1`
4. `cd /home/userku/ruci-prima && php artisan prima:doctor > storage/logs/setup-4.txt 2>&1`

Hasil tiap langkah ada di `ruci-prima/storage/logs/setup-*.txt`. Setelah berhasil, kosongkan `ASSET_ADMIN_PASSWORD` di `.env` dan jalankan `php artisan optimize` lagi.

Aplikasi ini **tidak membutuhkan cron permanen**: skor, PM terlambat, dan dasbor dihitung saat data disimpan atau dibuka.

## 7. Mulai memakai

1. Buka `https://prima.domainanda.com/platform`, masuk sebagai admin platform.
2. **Perusahaan / Properti** › tambah perusahaan (hotel, rumah sakit, pabrik, kampus, gedung kantor). Departemen dan kategori aset umum dibuat otomatis dan bisa diubah.
3. **Akun pengguna** › buat akun dan beri akses perusahaan dengan peran:
   - **Manajer**: semua fitur termasuk konfigurasi, CAPEX, persetujuan pemesanan ruang/kendaraan dan permintaan barang, serta menghapus data.
   - **Staf**: mencatat aset, inspeksi, permintaan, work order, PM, risiko; memesan ruang dan kendaraan, membuat dan menyerahkan permintaan barang, mencatat BBM, servis, dan dokumen; CAPEX hanya baca.
   - **Eksekutif**: hanya baca (dasbor, register, laporan).
4. Pengguna masuk di `https://prima.domainanda.com/app`. Urutan kerja yang disarankan:
   1. *Konfigurasi* › Lokasi (berjenjang), Departemen, Kategori Aset, Templat Ruang/Lokasi.
   2. *Identifikasi Awal* › Survei Aset Awal (penugasan tim per lokasi) › **Bangkitkan dari templat** / **Salin dari lokasi** / **Duplikasi aset**.
   3. Tim lapangan memakai **Verifikasi Aset** untuk menandai aset ada, tidak ditemukan, atau aset tambahan.
   4. Lengkapi nilai, umur, skor kesehatan, dan kekritisan di **Register Aset**.
   5. **Cetak label QR** (pilih aset › *Cetak label QR*) lalu tempel di aset. Siapa pun yang memindai bisa melapor kerusakan tanpa login.
   6. Kelola **Permintaan Pemeliharaan → Work Order**, **Pemeliharaan Preventif** (*Terbitkan WO* memajukan jatuh tempo otomatis), **Register Risiko**, dan **Usulan CAPEX**.
5. Modul **bagian umum**:
   1. *Konfigurasi* › **Ruang Rapat**: daftar ruang beserta kapasitas dan fasilitas. Karyawan memesan di *Pemesanan Ruang Rapat*; jadwal yang bentrok ditolak otomatis, manajer menyetujui. *Jadwal Ruang Rapat* menampilkan seminggu penuh per ruang.
   2. *Kendaraan Dinas*: isi nomor polisi, odometer, dan tanggal pajak, STNK, KIR, asuransi, servis. Pemesanan disetujui dengan menetapkan kendaraan dan pengemudi, lalu dicatat *Berangkat* dan *Kembali* (odometer). Di halaman kendaraan ada tab pengisian BBM, riwayat servis, dan dokumen.
   3. *Stok Barang*: daftar ATK, tinta, bahan kebersihan, dan pantry dengan stok minimum. Pakai *Stok masuk*, *Stok keluar*, atau *Stok opname*; semua tercatat di *Mutasi Stok*. Karyawan mengajukan *Permintaan Barang*, lalu bagian umum menekan **Serahkan barang** dan stok berkurang otomatis.
   4. *Dokumen & Perizinan*: izin usaha, SLF, SILO, kontrak vendor, polis asuransi, PBB. Isi tanggal berakhir dan berapa hari sebelumnya ingin diingatkan; tab *Perlu tindakan* dan *Dasbor Utama* menampilkan yang segera berakhir atau sudah lewat.

### Situs demo / promosi

Isi `ASSET_DEMO=true` (opsional `ASSET_DEMO_PASSWORD`) lalu jalankan `php artisan prima:demo` dan `php artisan optimize`. Tiga perusahaan contoh dibuat. Profil umumnya (nama, alamat, lini usaha, lokasi seperti pool, pabrik, dan cabang) mengacu pada laporan tahunan 2025 masing-masing; seluruh angka operasional, nama orang, dan nomor dokumen adalah **ilustrasi untuk demo**.

| Perusahaan | Alamat | Isi khas |
|---|---|---|
| PT Hotel Sahid Jaya International Tbk | `/app/sahid` | Kamar Nakula/Sadewa, Puri Agung Convention Center, ruang Candi, chiller, lift, CAPEX renovasi kamar dan rooftop |
| PT Blue Bird Tbk | `/app/bluebird` | Enam pool (Halim, Ciputat, Perigi, Warung Buncit, Jimbaran, Semarang), SPKLU, bengkel, batch armada listrik, command center |
| PT Diamond Food Indonesia Tbk | `/app/diamond` | Pabrik MM2100 dan Cimahi, lini UHT dan es krim, kompresor amonia, cabang rantai dingin, proyek gudang Irian 3 |

Setiap perusahaan berisi survei aset yang sedang berjalan, 12 bulan riwayat work order beserta biaya, inspeksi, permintaan dari pindai QR, rencana PM (sebagian terlambat), register risiko, usulan CAPEX, ruang rapat dengan pemesanan 6 minggu, kendaraan dinas dengan log BBM 12 bulan dan riwayat servis, persediaan dengan mutasi 6 bulan (sebagian menipis), permintaan barang, serta izin dan dokumen (sebagian segera berakhir atau kedaluwarsa).

Akun per perusahaan memakai domain `sahid.demo`, `bluebird.demo`, atau `diamond.demo`:

| Peran | Email (contoh untuk Hotel Sahid) |
|---|---|
| Eksekutif (Direktur) | direktur@sahid.demo |
| Manajer | manajer@sahid.demo |
| Staf (Teknisi) | teknisi@sahid.demo |
| Staf (Supervisor) | supervisor@sahid.demo |
| Staf (General Affairs) | umum@sahid.demo |
| Eksekutif lintas perusahaan (komisaris/pimpinan grup) | pimpinan@prima.demo |

Akun `pimpinan@prima.demo` dapat berpindah di antara ketiga perusahaan lewat pemilih perusahaan di kiri atas; cocok untuk memperagakan **Dasbor Pimpinan** dan halaman **Mengapa Ruci PRIMA**. Admin platform (`ASSET_ADMIN_EMAIL`) juga otomatis menjadi manajer di ketiganya.

**Mode tamu.** Selama data contoh terpasang, halaman masuk menampilkan tombol **Masuk sebagai Tamu** dengan pilihan perusahaan (bawaan: PT Hotel Sahid Jaya). Tamu masuk tanpa kata sandi (akun `tamu@prima.demo`, peran Eksekutif, hanya baca, profil tidak dapat diubah) dan dapat berpindah perusahaan lewat pita di atas layar. Alamat langsung: `/tamu`, `/tamu?perusahaan=bluebird`, `/tamu?perusahaan=diamond`. Akun tamu ikut terhapus oleh `php artisan prima:remove-demo`, sehingga tombolnya hilang di instalasi produksi.

**Halaman Fitur Utama.** Penjelasan fitur untuk komisaris, direksi, dan kepala bagian umum (BUMN maupun swasta) tersedia publik di `/fitur` (ditautkan dari halaman masuk) dan di dalam aplikasi pada menu **Panduan & Informasi**, bersama halaman **Kenapa Perlu Ruci PRIMA** dan **Panduan Penggunaan** per peran (Direksi & Komisaris, Manajer, Staf Bagian Umum, Teknisi, Tamu) yang dilengkapi gambar layar.

Kata sandi semua akun: nilai `ASSET_DEMO_PASSWORD` (bawaan `Demo#2026aset`). Hapus seluruh perusahaan contoh kapan saja dengan `php artisan prima:remove-demo`. Instalasi yang masih berisi data contoh lama (GPCH) cukup menjalankan `php artisan prima:remove-demo` lalu `php artisan prima:demo`.

## 8. Memperbarui program

Dengan Terminal:

```bash
cd ~/ruci-prima
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan up
```

Tanpa Terminal: upload zip versi baru, ekstrak menimpa folder `ruci-prima` (zip tidak berisi `.env`, dan berkas di `storage` tidak terhapus), lalu jalankan `php artisan migrate --force` dan `php artisan optimize` lewat cron sekali jalan.

Selalu **backup database dan folder `storage/app/private`** (foto inspeksi, permintaan, dan work order) sebelum memperbarui.

## 9. Masalah umum

| Gejala | Solusi |
|---|---|
| Error 500 tanpa keterangan | Lihat `storage/logs/laravel-*.log`. Sementara boleh `APP_DEBUG=true`, lalu kembalikan ke `false`. |
| `Your PHP version … does not satisfy` | Ubah PHP domain ke 8.3, dan pastikan cron/Terminal juga memakai PHP 8.3. |
| `Specified key was too long` | Database bukan utf8mb4 atau MySQL terlalu lama. Pakai MySQL 8 / MariaDB 10.6+. |
| QR dipindai tetapi halaman tidak terbuka | `APP_URL` masih `localhost` atau alamat internal. Perbaiki, `php artisan optimize`, lalu cetak ulang label. |
| Tombol foto di halaman QR tidak membuka kamera | Situs belum HTTPS. Aktifkan SSL. |
| Laporan QR ditolak "Too Many Attempts" | Batas `ASSET_QR_REPORTS_PER_MINUTE` per IP (bawaan 6). Naikkan bila banyak staf melapor dari satu Wi-Fi. |
| Halaman tampil tanpa gaya | Document root belum ke `ruci-prima/public`, atau isi `public` belum tersalin lengkap. |
| Semua halaman selain beranda 404 | `.htaccess` dari `public` belum ikut tersalin. |
| `419 Page Expired` saat login | `APP_URL` harus sama persis dengan alamat yang dibuka. Bila belum SSL, hapus `SESSION_SECURE_COOKIE=true`. |
| Unggah foto gagal | Naikkan `upload_max_filesize`/`post_max_size` di *MultiPHP INI Editor*; aktifkan ekstensi **fileinfo**. |
| Pengguna kehilangan ponsel MFA | Admin platform › Akun pengguna › *Reset MFA*. |
| Perubahan `.env` tidak berlaku | `php artisan optimize:clear` lalu `php artisan optimize`. |
