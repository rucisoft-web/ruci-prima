@php
    use App\Support\Money;
    $f = $facts;
    $num = fn ($v) => number_format((float) $v, 0, ',', '.');
    $examples = [
        'hotel' => [
            'title' => 'Contoh di perhotelan',
            'items' => [
                ['Kamar yang siap jual', 'Setiap AC, water heater, dan kunci kamar punya riwayat dan skor kesehatan. Kerusakan dari laporan tamu lewat QR langsung menjadi work order, sehingga kamar tidak lama berstatus out of order.'],
                ['Acara MICE tanpa kejutan', 'Chiller, AHU ballroom, eskalator, dan genset dipantau risikonya sebelum acara besar. Pimpinan tahu lebih dulu bila perlu menyiapkan unit cadangan.'],
                ['Renovasi berbasis data', 'Usulan renovasi kamar dan fasilitas diberi skor prioritas yang sama, sehingga anggaran diarahkan ke perbaikan yang paling berpengaruh pada tamu dan pendapatan.'],
            ],
        ],
        'transport' => [
            'title' => 'Contoh di transportasi',
            'items' => [
                ['Pool yang siap operasi', 'SPKLU, car lift, kompresor, dan genset di setiap pool tercatat dan dipelihara terjadwal. Antrean pengisian dan bengkel yang macet terlihat sebelum mengganggu jam operasi armada.'],
                ['Transisi kendaraan listrik terukur', 'Biaya energi, servis, dan umur batch armada tersaji berdampingan, menjadi dasar keputusan peremajaan dan penambahan unit listrik.'],
                ['Izin dan kelaikan tidak terlewat', 'Izin angkutan, SILO, sertifikat laik operasi, KIR, dan polis asuransi diingatkan jauh hari sebelum habis.'],
            ],
        ],
        'food' => [
            'title' => 'Contoh di manufaktur & rantai dingin',
            'items' => [
                ['Rantai dingin terjaga', 'Kompresor amonia, cold room, dan genset cabang dipantau kesehatannya. Gangguan suhu yang bisa merusak stok bernilai besar tercatat sebagai risiko dengan rencana mitigasi.'],
                ['Lini produksi lebih andal', 'Rasio pekerjaan reaktif per lini menunjukkan mesin yang paling sering berhenti, menjadi dasar overhaul atau penggantian sebelum musim puncak.'],
                ['Audit pangan siap kapan saja', 'Sertifikat halal, izin edar, FSSC, dan izin bejana tekan tersimpan dengan masa berlaku dan penanggung jawab yang jelas.'],
            ],
        ],
        'general' => [
            'title' => 'Contoh di perusahaan Anda',
            'items' => [
                ['Fasilitas yang siap pakai', 'Aset gedung, kendaraan, dan ruang kerja tercatat, dipelihara terjadwal, dan rusaknya dilaporkan lewat QR.'],
                ['Anggaran yang tepat sasaran', 'Setiap usulan investasi diberi skor prioritas yang sama sehingga mudah dibandingkan.'],
                ['Kepatuhan yang terjaga', 'Izin, sertifikat, kontrak, dan polis diingatkan sebelum habis.'],
            ],
        ],
    ][$industry];
@endphp
<x-filament-panels::page>
<div class="rac-why">
    <section class="rac-why-hero">
        <div class="rac-eyebrow">Untuk direksi, komisaris, dan pimpinan {{ $company?->name }}</div>
        <h1>Keputusan aset dan layanan umum yang diambil dari data, bukan perkiraan.</h1>
        <p>Ruci PRIMA (Pengelolaan Ruang, Inventaris, Mobilitas & Administrasi) menyatukan aset, pemeliharaan, armada, ruang rapat, persediaan, serta izin dan dokumen dalam satu sistem. Pimpinan melihat satu angka kesiapan operasional, tahu apa yang perlu diputuskan, dan bisa menelusuri setiap angka sampai ke bukti di lapangan.</p>
        <a class="rac-why-cta" href="{{ $dashboard }}">Buka Dasbor Pimpinan →</a>
    </section>

    <section class="rac-why-block">
        <h2>Yang sudah dikelola di perusahaan ini</h2>
        <div class="rac-why-facts">
            <div><strong>{{ Money::short($f['asset_value']) }}</strong><span>nilai {{ $num($f['assets']) }} aset tercatat dan dipantau kesehatannya</span></div>
            <div><strong>{{ $num($f['documents']) }}</strong><span>izin, sertifikat, kontrak, dan polis dengan pengingat masa berlaku</span></div>
            <div><strong>{{ $num($f['vehicles']) }}</strong><span>kendaraan operasional dengan biaya per km dan jatuh tempo pajak/KIR</span></div>
            <div><strong>{{ $num($f['rooms']) }} &middot; {{ $num($f['supplies']) }}</strong><span>ruang rapat dan jenis barang persediaan yang terpantau</span></div>
            <div><strong>{{ Money::short($f['capex']) }}</strong><span>usulan investasi (CAPEX) yang menunggu keputusan, lengkap dengan skor prioritas</span></div>
        </div>
    </section>

    <section class="rac-why-block">
        <h2>Lima pertanyaan pimpinan yang terjawab dalam satu menit</h2>
        <div class="rac-why-grid five">
            <article><b>1. Apakah operasional kita siap?</b><p>Indeks Kesiapan Operasional menggabungkan kesehatan aset, kepatuhan izin, kesiapan armada, persediaan, dan disiplin pemeliharaan menjadi satu angka 0–100.</p></article>
            <article><b>2. Di mana uang perawatan habis?</b><p>Biaya pemeliharaan, BBM, servis kendaraan, dan persediaan terlihat per bulan dan per kategori, sehingga lonjakan cepat ditelusuri penyebabnya.</p></article>
            <article><b>3. Investasi mana yang harus didahulukan?</b><p>Setiap usulan CAPEX dinilai dengan rumus yang sama: dampak keselamatan, pelanggan, pendapatan, dan urgensi. Rapat anggaran membahas prioritas, bukan siapa yang paling keras bersuara.</p></article>
            <article><b>4. Risiko apa yang bisa menghentikan bisnis?</b><p>Peta risiko 5×5 menunjukkan aset yang paling berbahaya bila gagal, siapa pemiliknya, dan sejauh mana mitigasinya.</p></article>
            <article><b>5. Apakah ada izin yang terlewat?</b><p>Izin dan sertifikat yang kedaluwarsa atau segera berakhir tampil di dasbor, lengkap dengan penanggung jawabnya.</p></article>
        </div>
    </section>

    <section class="rac-why-block">
        <h2>Manfaat bagi setiap peran pimpinan</h2>
        <div class="rac-why-roles">
            <article><h3>Direktur Utama & Direksi</h3><ul><li>Satu layar untuk kesiapan operasional seluruh unit.</li><li>Daftar keputusan yang menunggu, diurutkan dari dampak terbesar.</li><li>Bisa membandingkan beberapa perusahaan atau unit dalam satu akun.</li></ul></article>
            <article><h3>Dewan Komisaris & Komite Audit</h3><ul><li>Jejak audit: siapa mengubah apa dan kapan, untuk setiap data penting.</li><li>Bukti pengendalian internal atas aset tetap, persediaan, dan kepatuhan perizinan.</li><li>Profil risiko aset yang dapat dipantau setiap periode rapat.</li></ul></article>
            <article><h3>Direktur Keuangan</h3><ul><li>Nilai aset, biaya pemeliharaan, dan nilai persediaan yang terekonsiliasi dengan lapangan.</li><li>Rencana CAPEX multi-tahun dari aset yang melewati umur ekonomis.</li><li>Biaya per km kendaraan dan pemakaian persediaan per unit kerja.</li></ul></article>
            <article><h3>Direktur Operasional & Kepala Unit</h3><ul><li>Pemeliharaan preventif terjadwal; kerusakan dilaporkan staf lewat QR.</li><li>Pemesanan ruang dan kendaraan tanpa bentrok, dengan persetujuan berjenjang.</li><li>Stok ATK dan kebutuhan kantor tidak pernah kosong mendadak.</li></ul></article>
        </div>
    </section>

    <section class="rac-why-block">
        <h2>{{ $examples['title'] }}</h2>
        <div class="rac-why-grid three">
            @foreach ($examples['items'] as [$title, $text])
                <article><b>{{ $title }}</b><p>{{ $text }}</p></article>
            @endforeach
        </div>
    </section>

    <section class="rac-why-block">
        <h2>Nilai bisnis yang dapat diukur</h2>
        <table class="rac-why-table">
            <thead><tr><th>Area</th><th>Sebelum</th><th>Dengan Ruci PRIMA</th><th>Indikator di dasbor</th></tr></thead>
            <tbody>
                <tr><td>Biaya perawatan</td><td>Perbaikan menunggu rusak; biaya darurat dan lembur tinggi.</td><td>Pemeliharaan terjadwal; kerusakan berulang terlihat polanya.</td><td>Porsi pekerjaan reaktif, biaya per kategori</td></tr>
                <tr><td>Investasi (CAPEX)</td><td>Usulan dibahas tanpa pembanding yang sama.</td><td>Skor prioritas seragam dan bukti kondisi aset.</td><td>CAPEX per kategori, aset melewati umur ekonomis</td></tr>
                <tr><td>Kepatuhan</td><td>Izin habis baru diketahui saat inspeksi atau klaim.</td><td>Pengingat otomatis dan penanggung jawab jelas.</td><td>Status izin & dokumen</td></tr>
                <tr><td>Produktivitas fasilitas</td><td>Ruang dan kendaraan bentrok atau menganggur.</td><td>Jadwal, persetujuan, dan utilisasi tercatat.</td><td>Utilisasi ruang, biaya per km</td></tr>
                <tr><td>Tata kelola</td><td>Data tersebar di Excel dan grup percakapan.</td><td>Satu sumber data dengan hak akses per peran dan jejak audit.</td><td>Riwayat perubahan setiap catatan</td></tr>
            </tbody>
        </table>
        @if ($f['reactive_cost'] > 0)
            <p class="rac-why-note">Ilustrasi: biaya pekerjaan korektif dan darurat 12 bulan terakhir di perusahaan ini {{ Money::short($f['reactive_cost']) }}. Bila 20% di antaranya dapat dicegah melalui pemeliharaan terjadwal, potensi penghematannya sekitar {{ Money::short($f['reactive_cost'] * 0.2) }} per tahun. Angka 20% adalah asumsi untuk diskusi, bukan janji hasil.</p>
        @endif
    </section>

    <section class="rac-why-block">
        <h2>Implementasi bertahap, terasa sejak bulan pertama</h2>
        <div class="rac-why-steps">
            <article><span>0–30 hari</span><b>Fondasi</b><p>Struktur lokasi dan departemen, register izin & dokumen, kendaraan, ruang rapat, dan stok ATK. Dasbor sudah bisa dibaca pimpinan.</p></article>
            <article><span>31–60 hari</span><b>Sensus aset</b><p>Survei lapangan dengan templat dan label QR. Aset kritis diberi rencana pemeliharaan preventif dan register risiko.</p></article>
            <article><span>61–90 hari</span><b>Keputusan berbasis data</b><p>Usulan CAPEX dinilai dengan skor prioritas, rapat direksi memakai Dasbor Pimpinan, dan target indeks kesiapan ditetapkan per unit.</p></article>
        </div>
    </section>

    <section class="rac-why-block">
        <h2>Keamanan dan tata kelola</h2>
        <ul class="rac-why-list">
            <li>Data setiap perusahaan terpisah; satu akun pimpinan dapat membuka beberapa perusahaan sesuai haknya.</li>
            <li>Tiga peran: Manajer (mengelola), Staf (mengerjakan), dan Eksekutif (hanya membaca), sehingga pimpinan melihat data tanpa risiko mengubahnya.</li>
            <li>Jejak audit untuk perubahan penting dan verifikasi dua langkah (MFA) untuk akun.</li>
            <li>Berjalan di server perusahaan sendiri (termasuk hosting cPanel), sehingga kepemilikan data tetap di tangan perusahaan.</li>
        </ul>
    </section>
</div>
</x-filament-panels::page>
