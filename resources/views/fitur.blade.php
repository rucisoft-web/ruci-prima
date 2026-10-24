@php($guestUrl = \App\Models\User::guestAccount() ? route('guest.enter') : null)
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fitur Utama · Ruci PRIMA</title>
    <meta name="description" content="Ruci PRIMA: pengelolaan ruang, inventaris, mobilitas, dan administrasi untuk komisaris, direksi, dan kepala bagian umum.">
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/prima.css') }}?v={{ @filemtime(public_path('css/prima.css')) }}">
</head>
<body class="rp-page">
    <header class="rp-top">
        <a class="rp-brand" href="{{ route('features') }}"><img src="{{ asset('favicon.svg') }}" alt=""> Ruci <b>PRIMA</b></a>
        <nav class="rp-nav" aria-label="Bagian halaman">
            <a href="#fitur">Fitur</a>
            <a href="#peran">Untuk siapa</a>
            <a href="#sektor">BUMN &amp; swasta</a>
            <a href="#keamanan">Keamanan</a>
        </nav>
        <div class="rp-top-actions">
            @if ($guestUrl)
                <a class="rp-btn small" href="{{ $guestUrl }}">Coba sebagai Tamu</a>
            @endif
            <a class="rp-btn small primary" href="{{ url('/app/login') }}">Masuk</a>
        </div>
    </header>
    <main class="rp-main">
        @include('partials.fitur-konten', ['guestUrl' => $guestUrl])
    </main>
    <footer class="rp-foot">&copy; {{ date('Y') }} Rucisoft · Ruci PRIMA · Pengelolaan Ruang, Inventaris, Mobilitas &amp; Administrasi</footer>
</body>
</html>
