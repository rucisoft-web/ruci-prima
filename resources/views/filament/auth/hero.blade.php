{{-- Panel merek di sisi kiri halaman login (hanya untuk tamu). Ringan: tanpa gambar, hanya CSS. --}}
<aside class="rac-auth-hero" aria-hidden="true">
    <div class="rac-auth-brand">
        <img class="rac-logo-light" src="{{ asset('brand/ruci-prima-logo.svg') }}" alt="Ruci PRIMA">
        <img class="rac-logo-dark" src="{{ asset('brand/ruci-prima-logo-dark.svg') }}" alt="Ruci PRIMA">
    </div>

    <div class="rac-auth-copy">
        <p class="rac-auth-eyebrow">{{ $eyebrow ?? 'Pengelolaan Ruang, Inventaris, Mobilitas & Administrasi' }}</p>
        <h2>Fasilitas tertata,<br>keputusan lebih jernih.</h2>
        <p class="rac-auth-lead">Satu sistem untuk aset, ruang rapat, kendaraan, persediaan, dan perizinan, dengan dasbor yang siap dibaca pimpinan.</p>
        <ol>
            <li><span>01</span>Ruang & jadwal</li>
            <li><span>02</span>Inventaris & aset</li>
            <li><span>03</span>Mobilitas & armada</li>
            <li><span>04</span>Administrasi & izin</li>
        </ol>
    </div>

    <p class="rac-auth-foot">&copy; {{ date('Y') }} Rucisoft &middot; Ruci PRIMA</p>
    <div class="rac-auth-lines"></div>
</aside>
