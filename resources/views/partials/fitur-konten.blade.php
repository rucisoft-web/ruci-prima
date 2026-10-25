{{--
    Penjelasan fitur utama Ruci PRIMA untuk komisaris, direksi, dan kepala bagian umum (BUMN maupun swasta).
    Dipakai di halaman publik /fitur dan di sub menu Pimpinan › Fitur Utama.
    $guestUrl: tautan masuk sebagai tamu (null bila data contoh tidak terpasang).
    $dashboardUrl: di dalam panel, tombol utama membuka Dasbor Pimpinan.
--}}
@php
    $icon = fn (string $path) => new \Illuminate\Support\HtmlString('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$path.'</svg>');
    $features = [
        ['M4 19V5m0 14h16M8 15l3-4 3 2 5-6', 'Dasbor Pimpinan', 'Indeks Kesiapan Operasional 0–100, KPI lintas modul, daftar keputusan yang menunggu, catatan otomatis, dan grafik tren 12 bulan. Setiap angka dapat ditelusuri sampai ke datanya.'],
        ['M4 7h16M4 12h16M4 17h10', 'Register & Kesehatan Aset', 'Setiap aset punya skor kesehatan, kekritisan, skor risiko, umur, dan sisa umur ekonomis. Aset yang perlu perhatian muncul lebih dulu.'],
        ['M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2z', 'Label QR & Sensus Aset', 'Inventarisasi lapangan dengan templat per ruang, verifikasi aset (ada, tidak ditemukan, tambahan), dan label QR untuk lapor kerusakan dari ponsel.'],
        ['M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-.5-.5-2.5z', 'Pemeliharaan & Work Order', 'Pemeliharaan preventif terjadwal, permintaan perbaikan, dan work order dengan biaya, jam kerja, downtime, serta akar masalah.'],
        ['M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z', 'Risiko & CAPEX', 'Peta risiko 5×5 dengan pemilik dan mitigasi. Usulan investasi dinilai dengan skor prioritas yang seragam: keselamatan, pelanggan, pendapatan, urgensi.'],
        ['M4 5h16v14H4zM4 9h16M9 5v14', 'Ruang Rapat', 'Pemesanan ruang tanpa bentrok, persetujuan, jadwal mingguan per ruang, dan utilisasi untuk menilai kebutuhan ruang kantor.'],
        ['M5 16h14l-1.5-6h-11zM7 16v3M17 16v3M8 10l1-4h6l1 4', 'Kendaraan Dinas', 'Pemesanan dan persetujuan kendaraan, catatan berangkat-kembali, log BBM, servis, pengingat pajak, STNK, KIR, dan asuransi, serta biaya per km.'],
        ['M4 8l8-4 8 4v8l-8 4-8-4zM4 8l8 4 8-4M12 12v8', 'ATK & Persediaan', 'Stok minimum, stok masuk-keluar-opname, permintaan barang per unit kerja, dan nilai persediaan yang selalu terkini.'],
        ['M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6', 'Dokumen & Perizinan', 'Izin usaha, SLF, SILO, sertifikat, kontrak vendor, polis asuransi, dan pajak dengan pengingat sebelum masa berlaku habis.'],
        ['M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6', 'Multi-perusahaan & Hak Akses', 'Satu instalasi untuk induk dan anak usaha dengan data terpisah. Peran Manajer, Staf, dan Eksekutif (hanya baca), plus jejak audit dan MFA.'],
    ];
@endphp
<div class="rp">
    <section class="rp-hero">
        <p class="rp-eyebrow">Untuk Komisaris, Direksi, dan Kepala Bagian Umum</p>
        <h1>Satu kendali untuk aset, fasilitas, dan layanan umum perusahaan.</h1>
        <p class="rp-lead">Ruci PRIMA (Pengelolaan Ruang, Inventaris, Mobilitas &amp; Administrasi) menyatukan aset, pemeliharaan, ruang rapat, kendaraan dinas, persediaan, serta izin dan dokumen. Pimpinan melihat kesiapan operasional dalam satu layar; bagian umum bekerja dengan alur yang tertib dan terdokumentasi.</p>
        <div class="rp-actions">
            @if ($dashboardUrl ?? null)
                <a class="rp-btn primary" href="{{ $dashboardUrl }}">Buka Dasbor Pimpinan</a>
            @else
                @if ($guestUrl)
                    <a class="rp-btn primary" href="{{ $guestUrl }}">Coba sebagai Tamu</a>
                @endif
                <a class="rp-btn" href="{{ url('/app/login') }}">Masuk</a>
            @endif
        </div>
        <ul class="rp-trust">
            <li>BUMN, anak usaha, dan perusahaan swasta</li>
            <li>Banyak perusahaan dalam satu instalasi</li>
            <li>Dapat dipasang di server sendiri</li>
        </ul>
    </section>

    @if ($dashboardUrl ?? null)
        <section class="rp-section" id="tantangan">
            <h2>Tantangan yang sering dihadapi pimpinan</h2>
            <div class="rp-grid four">
                <article><b>Data tersebar</b><p>Aset di Excel, izin di lemari arsip, pemesanan ruang di grup percakapan. Sulit menjawab pertanyaan sederhana dengan cepat.</p></article>
                <article><b>Biaya reaktif</b><p>Perbaikan menunggu rusak. Biaya darurat, lembur, dan gangguan layanan lebih besar daripada pemeliharaan terjadwal.</p></article>
                <article><b>Investasi tanpa pembanding</b><p>Usulan CAPEX dibahas tanpa ukuran yang sama, sehingga anggaran tidak selalu jatuh ke kebutuhan paling penting.</p></article>
                <article><b>Kepatuhan terlewat</b><p>Izin dan sertifikat baru diketahui habis saat inspeksi, audit, atau klaim asuransi.</p></article>
            </div>
        </section>
    @else
        @include('partials.rucisoft-alur')
    @endif

    <section class="rp-section" id="fitur">
        <h2>Fitur utama</h2>
        <div class="rp-features">
            @foreach ($features as [$path, $title, $text])
                <article>
                    <span class="rp-icon">{{ $icon('<path d="'.$path.'"/>') }}</span>
                    <div><b>{{ $title }}</b><p>{{ $text }}</p></div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rp-section" id="peran">
        <h2>Nilai bagi setiap pemangku kepentingan</h2>
        <div class="rp-grid three">
            <article class="rp-role">
                <h3>Dewan Komisaris &amp; Komite Audit</h3>
                <ul>
                    <li>Pengawasan berbasis data: profil risiko aset, kepatuhan izin, dan tren biaya setiap periode rapat.</li>
                    <li>Jejak audit siapa mengubah apa dan kapan, sebagai bukti pengendalian internal.</li>
                    <li>Akses hanya baca, sehingga dapat memantau tanpa mengubah data.</li>
                </ul>
            </article>
            <article class="rp-role">
                <h3>Direksi</h3>
                <ul>
                    <li>Indeks Kesiapan Operasional dan daftar keputusan yang menunggu, diurutkan dari dampak terbesar.</li>
                    <li>Prioritas CAPEX yang dapat dipertanggungjawabkan, didukung bukti kondisi aset.</li>
                    <li>Perbandingan antar perusahaan atau unit dalam satu akun.</li>
                </ul>
            </article>
            <article class="rp-role">
                <h3>Kepala Bagian Umum</h3>
                <ul>
                    <li>Satu tempat untuk ruang rapat, kendaraan dinas, ATK, dan perizinan.</li>
                    <li>Persetujuan berjenjang, pengingat jatuh tempo, dan stok minimum otomatis.</li>
                    <li>Laporan siap pakai untuk rapat pimpinan, tanpa menyusun ulang dari banyak berkas.</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="rp-section" id="sektor">
        <h2>Sesuai untuk BUMN maupun swasta</h2>
        <div class="rp-grid two">
            <article class="rp-sector">
                <h3>BUMN &amp; anak usaha</h3>
                <ul>
                    <li>Mendukung prinsip tata kelola yang baik: transparansi, akuntabilitas, dan pertanggungjawaban atas pemakaian aset dan fasilitas.</li>
                    <li>Register aset dan sensus berkala yang tertelusur, membantu kesiapan pemeriksaan oleh SPI maupun auditor eksternal.</li>
                    <li>Pengendalian pemakaian kendaraan dinas, ruang, dan ATK dengan persetujuan yang tercatat.</li>
                    <li>Struktur induk dan anak usaha dalam satu instalasi, dengan data tiap entitas tetap terpisah.</li>
                </ul>
            </article>
            <article class="rp-sector">
                <h3>Perusahaan swasta</h3>
                <ul>
                    <li>Menekan biaya operasional dengan memindahkan pekerjaan reaktif menjadi pemeliharaan terjadwal.</li>
                    <li>Investasi diarahkan ke aset yang paling berpengaruh pada pelanggan dan pendapatan.</li>
                    <li>Cepat diterapkan di banyak cabang, pool, pabrik, atau properti.</li>
                    <li>Biaya infrastruktur ringan: berjalan di hosting cPanel, tanpa server khusus.</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="rp-section" id="penerapan">
        <h2>Penerapan bertahap</h2>
        <div class="rp-steps">
            <article><span>0–30 hari</span><b>Fondasi</b><p>Struktur lokasi dan unit kerja, register izin &amp; dokumen, kendaraan, ruang rapat, dan stok ATK. Dasbor sudah bisa dibaca pimpinan.</p></article>
            <article><span>31–60 hari</span><b>Sensus aset</b><p>Survei lapangan dengan templat dan label QR. Aset kritis diberi rencana pemeliharaan preventif dan register risiko.</p></article>
            <article><span>61–90 hari</span><b>Keputusan berbasis data</b><p>Usulan CAPEX dinilai dengan skor prioritas, rapat direksi memakai Dasbor Pimpinan, dan target kesiapan ditetapkan per unit.</p></article>
        </div>
    </section>

    <section class="rp-section" id="keamanan">
        <h2>Keamanan dan tata kelola data</h2>
        <div class="rp-grid four">
            <article><b>Data terpisah</b><p>Setiap perusahaan memiliki data sendiri; pengguna hanya melihat perusahaan yang menjadi haknya.</p></article>
            <article><b>Akses berbasis peran</b><p>Manajer mengelola, Staf mengerjakan, Eksekutif membaca. Halaman dan tombol mengikuti peran.</p></article>
            <article><b>Jejak audit &amp; MFA</b><p>Perubahan penting tercatat, dan akun dapat dilindungi verifikasi dua langkah.</p></article>
            <article><b>Kepemilikan data</b><p>Dipasang di server atau hosting perusahaan sendiri; data tidak dititipkan ke pihak lain.</p></article>
        </div>
    </section>

    @if ($guestUrl)
        <section class="rp-cta">
            <div>
                <h2>Lihat sendiri dengan data contoh</h2>
                <p>Tiga perusahaan contoh siap dibuka dalam mode hanya baca. Profilnya mengacu pada laporan tahunan; angka operasionalnya ilustrasi.</p>
            </div>
            <div class="rp-cta-links">
                <a class="rp-btn primary" href="{{ $guestUrl }}?perusahaan=sahid">Hotel Sahid Jaya</a>
                <a class="rp-btn" href="{{ $guestUrl }}?perusahaan=bluebird">Blue Bird</a>
                <a class="rp-btn" href="{{ $guestUrl }}?perusahaan=diamond">Diamond Food</a>
            </div>
        </section>
    @endif
</div>
