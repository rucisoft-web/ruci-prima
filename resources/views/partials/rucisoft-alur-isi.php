<?php

// Isi blok halaman awal seragam (partials/rucisoft-alur.blade.php) khusus produk ini.
return [
    'slug' => 'ruci-prima',
    'nama' => 'Ruci PRIMA',
    'aksen' => '#0b3a5c',
    'aksen_2' => '#c9963e',
    'coba_url' => '',
    'masalah_judul' => 'Pimpinan sulit menjawab pertanyaan sederhana tentang aset dan fasilitas.',
    'masalah_lead' => 'Berapa aset yang kritis? Izin mana yang akan habis? Ruang dan kendaraan mana yang paling terpakai? Jawabannya tersebar di banyak tempat.',
    'masalah' => [
        ['Data tersebar', 'Aset di Excel, izin di lemari arsip, pemesanan ruang di grup percakapan.'],
        ['Biaya reaktif', 'Perbaikan menunggu rusak. Biaya darurat, lembur, dan gangguan layanan lebih besar daripada pemeliharaan terjadwal.'],
        ['Investasi tanpa pembanding', 'Usulan CAPEX dibahas tanpa ukuran yang sama, sehingga anggaran tidak selalu jatuh ke kebutuhan paling penting.'],
        ['Kepatuhan terlewat', 'Izin dan sertifikat baru diketahui habis saat inspeksi, audit, atau klaim asuransi.'],
    ],
    'penyebab_judul' => 'Layanan umum dikelola per kegiatan, bukan sebagai satu sistem.',
    'penyebab_lead' => 'Setiap bagian punya catatan sendiri, dan tidak ada ukuran bersama untuk kondisi aset dan risiko.',
    'penyebab' => [
        ['Tidak ada register tunggal', 'Aset, ruang, kendaraan, dan izin dicatat di file yang berbeda.'],
        ['Pemeliharaan tidak terjadwal', 'Tidak ada rencana preventif dan riwayat work order per aset.'],
        ['Tidak ada skor prioritas', 'Usulan investasi tidak dinilai dengan kriteria yang sama.'],
        ['Tidak ada pengingat', 'Masa berlaku izin, pajak, dan sertifikat tidak dipantau sistem.'],
    ],
    'solusi_judul' => 'Ruci PRIMA menjadi satu kendali untuk aset, fasilitas, dan layanan umum.',
    'solusi_lead' => 'Pimpinan melihat kesiapan operasional dalam satu layar; bagian umum bekerja dengan alur yang tertib dan terdokumentasi.',
    'solusi' => [
        ['Dasbor Pimpinan & register aset', 'Indeks Kesiapan Operasional, skor kesehatan aset, dan data yang bisa ditelusuri.', 'Data tersebar'],
        ['Pemeliharaan & work order', 'Preventif terjadwal, biaya, downtime, dan akar masalah tercatat.', 'Biaya reaktif'],
        ['Risiko & CAPEX', 'Peta risiko dan skor prioritas investasi yang seragam.', 'Investasi tanpa pembanding'],
        ['Dokumen & perizinan', 'Pengingat sebelum izin, SLF, SILO, polis, dan pajak habis masa berlakunya.', 'Kepatuhan terlewat'],
    ],
];
