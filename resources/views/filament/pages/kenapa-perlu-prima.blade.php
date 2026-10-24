@php
    $signs = [
        'Daftar aset tersebar di beberapa file Excel dan tidak cocok dengan kondisi di lapangan.',
        'Perbaikan baru dilakukan setelah alat rusak, dan biaya darurat terus naik.',
        'Ruang rapat atau kendaraan dinas sering bentrok, atau justru jarang terpakai.',
        'ATK dan barang habis mendadak, sementara barang lain menumpuk di gudang.',
        'Izin, sertifikat, atau polis baru diketahui habis saat inspeksi atau klaim.',
        'Usulan investasi dibahas tanpa ukuran prioritas yang sama.',
        'Laporan untuk direksi disusun manual dari banyak berkas setiap bulan.',
        'Sulit membuktikan siapa menyetujui atau mengubah data saat audit.',
    ];
    $areas = [
        ['Aset & inventaris', 'Aset tidak tercatat lengkap; nilai buku tidak cocok dengan barang di lapangan; aset hilang atau menganggur tidak ketahuan.', 'Register aset dengan lokasi, penanggung jawab, umur, dan nilai. Sensus lapangan dengan templat per ruang dan label QR.', 'Setiap aset dapat ditemukan, diverifikasi, dan dipertanggungjawabkan.'],
        ['Pemeliharaan', 'Perawatan menunggu rusak. Riwayat perbaikan hanya di kepala teknisi; kerusakan berulang tidak terlihat polanya.', 'Pemeliharaan preventif terjadwal, laporan kerusakan lewat QR, dan work order dengan biaya, downtime, serta akar masalah.', 'Porsi pekerjaan reaktif turun, umur aset lebih panjang, biaya lebih terkendali.'],
        ['Ruang rapat', 'Pemesanan lewat grup percakapan; ruang bentrok atau dipesan tetapi tidak dipakai.', 'Jadwal mingguan semua ruang, pemesanan tanpa bentrok, persetujuan, dan utilisasi per ruang.', 'Rapat berjalan tepat waktu dan kebutuhan ruang kantor dinilai dengan data.'],
        ['Kendaraan dinas', 'Pemakaian kendaraan tidak tercatat; BBM dan servis sulit dikendalikan; pajak dan KIR terlambat.', 'Pemesanan dan persetujuan, catatan berangkat-kembali dengan odometer, log BBM dan servis, pengingat pajak, STNK, KIR, asuransi.', 'Biaya per km terlihat, kendaraan selalu laik jalan, dan denda terhindar.'],
        ['ATK & persediaan', 'Stok tidak terpantau; permintaan unit kerja tidak tercatat; selisih saat hitung fisik.', 'Stok minimum, permintaan barang per unit kerja dengan persetujuan, stok masuk-keluar, dan stok opname.', 'Barang tidak habis mendadak, pemakaian per unit jelas, nilai persediaan selalu terkini.'],
        ['Izin & dokumen', 'Dokumen tersimpan di lemari atau surel pribadi; masa berlaku tidak dipantau.', 'Register izin, sertifikat, kontrak, dan polis dengan masa berlaku, penanggung jawab, dan pengingat.', 'Kepatuhan terjaga dan risiko sanksi atau klaim ditolak berkurang.'],
        ['Risiko & investasi', 'Risiko aset tidak terpetakan; anggaran jatuh ke usulan yang paling keras disuarakan.', 'Peta risiko 5×5 dengan pemilik dan mitigasi; usulan CAPEX dengan skor prioritas yang seragam.', 'Anggaran diarahkan ke aset yang paling berpengaruh pada keselamatan, pelanggan, dan pendapatan.'],
        ['Pelaporan pimpinan', 'Direksi menunggu laporan bulanan yang disusun manual dan sudah usang saat dibaca.', 'Dasbor Pimpinan dengan Indeks Kesiapan Operasional, KPI, keputusan yang menunggu, dan grafik 12 bulan.', 'Keputusan diambil lebih cepat dari data yang sama untuk semua pihak.'],
        ['Tata kelola & audit', 'Sulit membuktikan pengendalian internal; data dapat diubah tanpa jejak.', 'Hak akses per peran (Manajer, Staf, Eksekutif), jejak audit setiap perubahan, verifikasi dua langkah.', 'Siap diperiksa SPI, auditor eksternal, maupun komite audit.'],
    ];
@endphp
<x-filament-panels::page>
<div class="rp">
    <section class="rp-hero">
        <p class="rp-eyebrow">Kenapa perlu Ruci PRIMA</p>
        <h1>Fasilitas dan layanan umum menentukan kelancaran bisnis, tetapi jarang dikelola dengan data.</h1>
        <p class="rp-lead">Aset, ruang, kendaraan, persediaan, dan izin adalah fondasi operasional. Saat semuanya tercatat terpisah, biaya bocor tanpa terlihat, kepatuhan terlewat, dan pimpinan mengambil keputusan dari perkiraan. Ruci PRIMA menyatukan semuanya dalam satu sistem yang dipakai bagian umum setiap hari dan dibaca pimpinan kapan saja.</p>
        <div class="rp-actions">
            <a class="rp-btn primary" href="{{ $guideUrl }}">Baca panduan penggunaan</a>
            <a class="rp-btn" href="{{ $featuresUrl }}">Lihat fitur utama</a>
        </div>
    </section>

    <section class="rp-section">
        <h2>Tanda perusahaan Anda memerlukannya</h2>
        <p class="rp-sub">Bila dua atau lebih pernyataan berikut terasa akrab, ada biaya dan risiko yang belum terlihat.</p>
        <ul class="rp-signs">
            @foreach ($signs as $sign)
                <li>{{ $sign }}</li>
            @endforeach
        </ul>
    </section>

    <section class="rp-section">
        <h2>Masalah dan solusinya</h2>
        <div class="rp-ps">
            @foreach ($areas as [$area, $problem, $solution, $result])
                <article>
                    <h3>{{ $area }}</h3>
                    <dl>
                        <div class="problem"><dt>Masalah</dt><dd>{{ $problem }}</dd></div>
                        <div class="solution"><dt>Solusi Ruci PRIMA</dt><dd>{{ $solution }}</dd></div>
                        <div class="result"><dt>Hasil</dt><dd>{{ $result }}</dd></div>
                    </dl>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rp-section">
        <h2>Cara kerjanya</h2>
        <ol class="rp-flow">
            <li><span>1</span><b>Catat</b><p>Aset, ruang, kendaraan, barang, dan izin masuk ke satu register, lengkap dengan lokasi dan penanggung jawab.</p></li>
            <li><span>2</span><b>Jadwalkan</b><p>Pemeliharaan preventif, pemesanan, dan masa berlaku diatur sekali; sistem yang mengingatkan.</p></li>
            <li><span>3</span><b>Kerjakan</b><p>Staf menjalankan work order, pemesanan, dan permintaan barang dengan persetujuan berjenjang.</p></li>
            <li><span>4</span><b>Putuskan</b><p>Pimpinan membaca hasilnya di dasbor dan menentukan prioritas dari data yang sama.</p></li>
        </ol>
    </section>

    <section class="rp-section">
        <h2>Biaya jika tidak berubah</h2>
        <div class="rp-grid three">
            <article><b>Biaya darurat</b><p>Perbaikan tak terencana umumnya lebih mahal daripada perawatan terjadwal karena lembur, pengiriman cepat, dan gangguan layanan.</p></article>
            <article><b>Sanksi dan klaim</b><p>Izin atau sertifikat yang habis dapat berujung pada penghentian operasi, denda, atau klaim asuransi yang ditolak.</p></article>
            <article><b>Keputusan tertunda</b><p>Tanpa data yang dipercaya semua pihak, rapat anggaran berulang dan investasi penting tertunda.</p></article>
        </div>
    </section>

    <section class="rp-cta">
        <div>
            <h2>Mulai dari peran Anda</h2>
            <p>Panduan penggunaan menjelaskan langkah demi langkah untuk direksi, manajer, staf bagian umum, dan teknisi, lengkap dengan gambar layar.</p>
        </div>
        <div class="rp-cta-links">
            <a class="rp-btn primary" href="{{ $guideUrl }}">Buka panduan</a>
        </div>
    </section>
</div>
</x-filament-panels::page>
