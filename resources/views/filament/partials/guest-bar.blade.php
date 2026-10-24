{{-- Pita mode tamu: perusahaan yang sedang dilihat dan pindah perusahaan dengan satu klik. --}}
@php
    $tenant = filament()->getTenant();
    $default = \App\Http\Controllers\GuestLoginController::DEFAULT_COMPANY;
    $companies = auth()->user()->getTenants(filament()->getCurrentPanel())->sortBy(fn ($company) => [$company->slug !== $default, $company->name]);
    $short = fn ($name) => trim(str_replace(['PT ', ' Tbk', ' International', ' Jaya', ' Indonesia'], ['', '', '', '', ''], $name));
@endphp
<div class="rac-guest-bar" role="region" aria-label="Mode tamu">
    <span class="tag">Mode tamu · hanya baca</span>
    <span class="now">Sedang melihat <b>{{ $tenant?->name }}</b></span>
    <nav class="switch" aria-label="Coba perusahaan lain">
        <span>Coba perusahaan lain:</span>
        @foreach ($companies as $company)
            <a href="{{ \App\Filament\App\Pages\DasborPimpinan::getUrl(tenant: $company) }}" @class(['active' => $tenant?->is($company)])>{{ $short($company->name) }}</a>
        @endforeach
    </nav>
    <a class="link" href="{{ \App\Filament\App\Pages\FiturUtama::getUrl() }}">Fitur utama</a>
</div>
