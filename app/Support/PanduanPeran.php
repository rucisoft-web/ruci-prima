<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;

/**
 * Isi panduan penggunaan per peran. Setiap langkah boleh punya gambar layar di public/img/panduan/{shot}.webp;
 * bila berkasnya belum ada, halaman menampilkan bingkai placeholder dengan ukuran yang sama.
 */
class PanduanPeran
{
    public const DEFAULT = 'eksekutif';

    /** @return array<string, array{label: string, who: string, intro: string, can: list<string>, steps: list<array{title: string, menu: string, text: string, tips?: list<string>, shot: string, mobile?: bool}>}> */
    public static function all(): array
    {
        return [
            'eksekutif' => [
                'label' => 'Direksi & Komisaris',
                'who' => 'Peran Eksekutif (hanya baca): direksi, dewan komisaris, komite audit, dan pimpinan grup.',
                'intro' => 'Anda tidak perlu mengisi data. Cukup membaca dasbor, menelusuri angka yang menarik perhatian, dan meminta tindak lanjut kepada manajer terkait.',
                'can' => ['Melihat semua dasbor, daftar, dan detail data', 'Berpindah antar perusahaan yang menjadi hak Anda', 'Tidak dapat mengubah atau menghapus data, sehingga aman untuk dipantau kapan saja'],
                'steps' => [
                    ['title' => 'Masuk dan pilih perusahaan', 'menu' => 'Halaman masuk › pemilih perusahaan di kiri atas', 'text' => 'Masuk dengan email dan kata sandi dari administrator. Bila Anda memegang beberapa perusahaan (misalnya induk dan anak usaha), ganti perusahaan lewat pemilih di kiri atas tanpa perlu keluar.', 'shot' => 'eksekutif-1'],
                    ['title' => 'Baca Dasbor Pimpinan', 'menu' => 'Pimpinan › Dasbor Pimpinan', 'text' => 'Mulai dari Indeks Kesiapan Operasional (0–100). Di atas 80 berarti operasional siap; di bawah 65 perlu intervensi. Lima komponennya menunjukkan bagian mana yang menurunkan angka.', 'tips' => ['Kartu KPI berwarna merah atau jingga adalah yang perlu ditanyakan lebih dulu.', 'Grafik 12 bulan membantu membedakan lonjakan sesaat dari tren.'], 'shot' => 'eksekutif-2'],
                    ['title' => 'Putuskan yang menunggu', 'menu' => 'Dasbor Pimpinan › Menunggu keputusan Anda', 'text' => 'Daftar ini diurutkan dari dampak terbesar: izin kedaluwarsa, risiko kritis, dan usulan investasi. Klik salah satu untuk melihat detail lengkapnya dan siapa penanggung jawabnya.', 'shot' => 'eksekutif-3'],
                    ['title' => 'Tinjau usulan CAPEX', 'menu' => 'Risiko & Investasi › Usulan CAPEX', 'text' => 'Setiap usulan memiliki skor prioritas dari dampak keselamatan, pelanggan, pendapatan, dan urgensi. Urutkan menurut skor untuk bahan rapat anggaran.', 'shot' => 'eksekutif-4'],
                    ['title' => 'Pantau risiko dan kepatuhan izin', 'menu' => 'Risiko & Investasi › Risiko aset; Legal & Dokumen › Dokumen', 'text' => 'Peta risiko menunjukkan aset yang paling berbahaya bila gagal beserta mitigasinya. Daftar dokumen memperlihatkan izin yang kedaluwarsa atau segera berakhir.', 'shot' => 'eksekutif-5'],
                ],
            ],
            'manajer' => [
                'label' => 'Manajer / Kepala Bagian Umum',
                'who' => 'Peran Manajer: kepala bagian umum, chief engineer, kepala pool, atau manajer fasilitas.',
                'intro' => 'Manajer mengatur data induk, menyetujui permintaan, dan memastikan pekerjaan berjalan sesuai jadwal. Mulailah setiap hari dari Dasbor Utama.',
                'can' => ['Semua yang dapat dilakukan Staf', 'Menyetujui atau menolak pemesanan ruang, kendaraan, dan permintaan barang', 'Mengelola konfigurasi: lokasi, departemen, kategori, templat, ruang rapat, kendaraan', 'Mengajukan dan menilai usulan CAPEX, melihat jejak audit'],
                'steps' => [
                    ['title' => 'Mulai dari Dasbor Utama', 'menu' => 'Layanan Umum › Dasbor Utama', 'text' => 'Agenda hari ini, persetujuan yang menunggu, stok di bawah minimum, dan izin yang akan habis tampil di satu layar. Angka pada menu kiri adalah jumlah yang perlu ditindaklanjuti.', 'shot' => 'manajer-1'],
                    ['title' => 'Setujui pemesanan ruang dan kendaraan', 'menu' => 'Layanan Umum › Pemesanan Ruang Rapat / Pemesanan Kendaraan', 'text' => 'Pemesanan baru berstatus menunggu. Gunakan tombol Setujui atau Tolak pada baris pemesanan. Sistem mencegah dua pemesanan bentrok di ruang atau kendaraan yang sama.', 'shot' => 'manajer-2'],
                    ['title' => 'Proses permintaan barang', 'menu' => 'ATK & Persediaan › Permintaan Barang', 'text' => 'Setujui permintaan dari unit kerja, lalu Serahkan barang. Stok berkurang otomatis dan tercatat di Mutasi Stok.', 'tips' => ['Barang dengan stok di bawah minimum diberi tanda di Stok Barang; catat pembelian lewat Stok masuk.'], 'shot' => 'manajer-3'],
                    ['title' => 'Ubah laporan kerusakan menjadi work order', 'menu' => 'Inspeksi & Pemeliharaan › Permintaan Pemeliharaan', 'text' => 'Laporan dari staf atau dari pindai QR masuk ke sini. Pilih Buat work order, tetapkan teknisi dan prioritas.', 'shot' => 'manajer-4'],
                    ['title' => 'Atur pemeliharaan preventif', 'menu' => 'Inspeksi & Pemeliharaan › Pemeliharaan Preventif', 'text' => 'Buat rencana berkala untuk aset penting (misalnya genset tiap bulan, lift tiap minggu). Work order terbit sesuai jadwal sehingga perawatan tidak menunggu rusak.', 'shot' => 'manajer-5'],
                    ['title' => 'Kelola risiko dan usulan CAPEX', 'menu' => 'Risiko & Investasi', 'text' => 'Catat risiko dengan kemungkinan, dampak, pemilik, dan mitigasi. Aset yang perlu diganti diajukan sebagai usulan CAPEX dengan skor prioritas, siap dibaca direksi.', 'shot' => 'manajer-6'],
                    ['title' => 'Konfigurasi dan jejak audit', 'menu' => 'Konfigurasi', 'text' => 'Atur lokasi, departemen, kategori aset, templat lokasi, dan ruang rapat. Jejak audit mencatat siapa mengubah apa dan kapan.', 'shot' => 'manajer-7'],
                ],
            ],
            'umum' => [
                'label' => 'Staf Bagian Umum',
                'who' => 'Peran Staf di bagian umum / general affairs, sekretariat, atau pengelola kendaraan.',
                'intro' => 'Staf bagian umum mencatat pemesanan, persediaan, dan dokumen sehari-hari. Persetujuan dilakukan manajer, sehingga staf cukup memastikan datanya lengkap.',
                'can' => ['Memesan ruang rapat dan kendaraan', 'Mencatat stok masuk, keluar, dan permintaan barang', 'Mencatat dokumen, izin, dan masa berlakunya', 'Tidak dapat mengubah konfigurasi atau menyetujui'],
                'steps' => [
                    ['title' => 'Lihat jadwal dan pesan ruang rapat', 'menu' => 'Layanan Umum › Jadwal Ruang Rapat', 'text' => 'Jadwal mingguan memperlihatkan semua ruang sekaligus. Klik Pesan ruang, isi waktu, jumlah peserta, dan keperluan. Bentrok jadwal ditolak otomatis.', 'shot' => 'umum-1'],
                    ['title' => 'Pesan dan catat pemakaian kendaraan', 'menu' => 'Layanan Umum › Pemesanan Kendaraan', 'text' => 'Setelah disetujui, tekan Berangkat saat kendaraan keluar (isi odometer) dan Kembali saat pulang. Jarak tempuh dihitung otomatis.', 'shot' => 'umum-2'],
                    ['title' => 'Catat BBM, servis, dan pajak kendaraan', 'menu' => 'Layanan Umum › Kendaraan Dinas › buka kendaraan', 'text' => 'Isi log BBM dan servis di halaman kendaraan. Jatuh tempo pajak, STNK, KIR, dan asuransi diingatkan sebelum habis.', 'shot' => 'umum-3'],
                    ['title' => 'Kelola ATK dan persediaan', 'menu' => 'ATK & Persediaan › Stok Barang', 'text' => 'Gunakan Stok masuk saat barang datang, Stok keluar untuk pemakaian, dan Stok opname saat hitung fisik. Riwayat mutasi tersimpan per barang.', 'shot' => 'umum-4'],
                    ['title' => 'Daftarkan izin dan dokumen', 'menu' => 'Legal & Dokumen › Dokumen', 'text' => 'Unggah izin, sertifikat, kontrak, atau polis beserta tanggal berakhir dan penanggung jawab. Status berubah otomatis menjadi segera berakhir atau kedaluwarsa.', 'shot' => 'umum-5'],
                ],
            ],
            'teknisi' => [
                'label' => 'Staf Teknik / Teknisi',
                'who' => 'Peran Staf di bagian teknik, engineering, atau pemeliharaan.',
                'intro' => 'Teknisi menerima work order, mengerjakannya, dan mencatat hasilnya. Data yang rapi dari lapangan inilah yang membuat dasbor pimpinan akurat.',
                'can' => ['Melihat dan mengerjakan work order', 'Mencatat inspeksi, laporan kerusakan, dan survei aset', 'Memperbarui data aset dan mencetak label QR'],
                'steps' => [
                    ['title' => 'Lihat work order Anda', 'menu' => 'Inspeksi & Pemeliharaan › Work Order', 'text' => 'Daftar work order dapat difilter menurut status, prioritas, atau teknisi. Kerjakan yang berprioritas tinggi lebih dulu.', 'shot' => 'teknisi-1'],
                    ['title' => 'Kerjakan dan selesaikan work order', 'menu' => 'Work Order › buka salah satu', 'text' => 'Buka work order lalu Ubah. Isi waktu mulai dan selesai, akar masalah, tindakan yang dilakukan, jam kerja, downtime, biaya, dan foto sesudah. Riwayat ini menjadi dasar skor kesehatan aset dan laporan biaya.', 'shot' => 'teknisi-2'],
                    ['title' => 'Catat inspeksi rutin', 'menu' => 'Inspeksi & Pemeliharaan › Inspeksi', 'text' => 'Pilih aset, isi hasil, skor kondisi (0–100), pembacaan meter, temuan, dan rekomendasi. Centang Perbarui kondisi fisik aset agar skor aset ikut terbarui. Temuan yang perlu perbaikan dilaporkan lewat Permintaan Pemeliharaan.', 'shot' => 'teknisi-3'],
                    ['title' => 'Survei dan verifikasi aset', 'menu' => 'Identifikasi Awal › Verifikasi Aset', 'text' => 'Saat sensus, telusuri aset per lokasi dan tandai ada, tidak ditemukan, atau tambahan. Progres verifikasi per lokasi terlihat di atas.', 'shot' => 'teknisi-4'],
                    ['title' => 'Register aset dan label QR', 'menu' => 'Intelijen Aset › Aset', 'text' => 'Setiap aset memiliki skor kesehatan, kekritisan, dan risiko. Gunakan Cetak label QR lalu tempelkan di aset.', 'shot' => 'teknisi-5'],
                    ['title' => 'Laporan kerusakan lewat QR', 'menu' => 'Pindai label QR dengan kamera ponsel', 'text' => 'Siapa pun (termasuk tamu hotel atau pengemudi) dapat memindai label untuk melihat aset dan melaporkan kerusakan tanpa masuk aplikasi. Laporan masuk ke Permintaan Pemeliharaan.', 'shot' => 'teknisi-6', 'mobile' => true],
                ],
            ],
            'tamu' => [
                'label' => 'Tamu (demo)',
                'who' => 'Calon pengguna yang ingin mencoba Ruci PRIMA dengan data contoh.',
                'intro' => 'Mode tamu membuka tiga perusahaan contoh dalam mode hanya baca. Profil perusahaan mengacu pada laporan tahunan; angka operasionalnya ilustrasi.',
                'can' => ['Melihat semua menu seperti direksi', 'Berpindah perusahaan contoh dengan satu klik', 'Tidak dapat mengubah data atau profil'],
                'steps' => [
                    ['title' => 'Masuk sebagai Tamu', 'menu' => 'Halaman masuk › Masuk sebagai Tamu', 'text' => 'Pilih perusahaan contoh (bawaan Hotel Sahid Jaya), lalu tekan Masuk sebagai Tamu. Tidak perlu kata sandi.', 'shot' => 'tamu-1'],
                    ['title' => 'Coba perusahaan lain', 'menu' => 'Pita biru di atas layar', 'text' => 'Klik Hotel Sahid, Blue Bird, atau Diamond Food untuk melihat bagaimana Ruci PRIMA dipakai di perhotelan, transportasi, dan pangan.', 'shot' => 'tamu-2'],
                    ['title' => 'Jelajahi fitur dan panduan', 'menu' => 'Panduan & Informasi', 'text' => 'Baca Kenapa Perlu Ruci PRIMA dan Fitur Utama, lalu buka menu lain untuk melihat datanya secara langsung.', 'shot' => 'tamu-3'],
                ],
            ],
        ];
    }

    /** Panduan yang paling sesuai untuk pengguna yang sedang membuka halaman. */
    public static function forUser(?User $user, ?Company $company): string
    {
        if ($user?->isGuest()) {
            return 'tamu';
        }

        $membership = $user?->activeMembership($company);

        return match ($membership?->role) {
            Role::Manajer => 'manajer',
            Role::Staf => preg_match('/teknis|teknik|engineer|mekanik|maintenance/i', (string) $membership->position_title) ? 'teknisi' : 'umum',
            default => self::DEFAULT,
        };
    }

    public static function shotUrl(string $shot): ?string
    {
        $path = "img/panduan/{$shot}.webp";

        return is_file(public_path($path)) ? asset($path).'?v='.filemtime(public_path($path)) : null;
    }
}
