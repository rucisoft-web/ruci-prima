{{-- Di bawah formulir masuk: coba sebagai tamu (bila data contoh terpasang) dan tautan ke penjelasan fitur. --}}
@php
    $guest = \App\Models\User::guestAccount();
    $default = \App\Http\Controllers\GuestLoginController::DEFAULT_COMPANY;
    $companies = collect($guest?->getTenants(filament()->getPanel('app')))->sortBy(fn ($company) => [$company->slug !== $default, $company->name])->values();
    $labels = ['sahid' => ['Hotel Sahid Jaya', 'Perhotelan & MICE'], 'bluebird' => ['Blue Bird', 'Transportasi'], 'diamond' => ['Diamond Food', 'Pangan & rantai dingin']];
@endphp
<div class="rac-login-extra">
    @if ($companies->isNotEmpty())
        <div class="rac-login-divider"><span>atau coba tanpa akun</span></div>
        <form class="rac-guest-form" method="GET" action="{{ route('guest.enter') }}">
            <fieldset>
                <legend>Pilih perusahaan contoh</legend>
                <div class="rac-guest-options">
                    @foreach ($companies as $company)
                        <label>
                            <input type="radio" name="perusahaan" value="{{ $company->slug }}" @checked($company->slug === $default)>
                            <span><b>{{ $labels[$company->slug][0] ?? $company->name }}</b><small>{{ $labels[$company->slug][1] ?? $company->industry }}</small></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <button type="submit" class="rac-guest-button">Masuk sebagai Tamu</button>
            <p class="rac-guest-note">Mode baca-saja dengan data ilustrasi. Perusahaan dapat diganti kapan saja dari pita di atas layar.</p>
        </form>
    @endif
    <a class="rac-login-learn" href="{{ route('features') }}">Pelajari fitur utama Ruci PRIMA <span aria-hidden="true">→</span></a>
    <p class="rac-login-trust">Terenkripsi · Akses berbasis peran · Jejak audit</p>
    <p class="rac-login-trust">Produk <a href="https://rucisoft.id" style="color:inherit;text-decoration:underline">Rucisoft</a> · <a href="https://rucisoft.id" style="color:inherit">rucisoft.id</a></p>
</div>
