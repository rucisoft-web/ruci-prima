{{-- Panel merek di sisi kiri halaman login (hanya untuk tamu). --}}
<aside class="rac-auth-hero" aria-hidden="true">
    <div class="rac-auth-brand">
        <img src="{{ asset('favicon.svg') }}" alt="">
        <span>Ruci PRIMA</span>
    </div>

    <div class="rac-auth-copy">
        <p class="rac-auth-eyebrow">{{ $eyebrow ?? 'Pengelolaan Ruang, Inventaris, Mobilitas & Administrasi' }}</p>
        <h2>Layanan umum tertata,<br>operasional tenang.</h2>
        <ul>
            <li>Ruang rapat dan kendaraan dinas</li>
            <li>Aset, ATK, dan persediaan</li>
            <li>Dokumen, perizinan, dan jatuh tempo</li>
        </ul>
    </div>

    <p class="rac-auth-foot">&copy; {{ date('Y') }} Rucisoft</p>
    <div class="rac-auth-stripes"></div>
</aside>
