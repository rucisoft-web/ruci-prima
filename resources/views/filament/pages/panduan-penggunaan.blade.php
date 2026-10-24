@php
    use App\Support\PanduanPeran;
@endphp
<x-filament-panels::page>
<div class="rp rp-guide">
    <section class="rp-hero">
        <p class="rp-eyebrow">Panduan penggunaan</p>
        <h1>{{ $guide['label'] }}</h1>
        <p class="rp-lead">{{ $guide['intro'] }}</p>
    </section>

    <nav class="rp-tabs" aria-label="Pilih peran">
        @foreach ($guides as $key => $item)
            <a href="{{ static::getUrl(['peran' => $key]) }}" @class(['active' => $key === $active]) @if ($key === $active) aria-current="page" @endif>
                {{ $item['label'] }}@if ($key === $mine)<small>peran Anda</small>@endif
            </a>
        @endforeach
    </nav>

    <section class="rp-guide-who">
        <div><b>Untuk siapa</b><p>{{ $guide['who'] }}</p></div>
        <div><b>Yang dapat dilakukan</b><ul>@foreach ($guide['can'] as $can)<li>{{ $can }}</li>@endforeach</ul></div>
    </section>

    <ol class="rp-guide-steps">
        @foreach ($guide['steps'] as $i => $step)
            @php($src = PanduanPeran::shotUrl($step['shot']))
            <li id="langkah-{{ $i + 1 }}">
                <div class="rp-guide-text">
                    <span class="rp-guide-num">Langkah {{ $i + 1 }}</span>
                    <h3>{{ $step['title'] }}</h3>
                    <p class="rp-guide-menu">{{ $step['menu'] }}</p>
                    <p>{{ $step['text'] }}</p>
                    @if (! empty($step['tips']))
                        <ul class="rp-guide-tips">@foreach ($step['tips'] as $tip)<li>{{ $tip }}</li>@endforeach</ul>
                    @endif
                </div>
                <figure @class(['rp-shot', 'mobile' => $step['mobile'] ?? false])>
                    <div class="rp-shot-frame">
                        <span class="rp-shot-placeholder" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 8h18M8 21h8"/></svg>
                            Gambar layar: {{ $step['title'] }}
                        </span>
                        @if ($src)
                            <a href="{{ $src }}" target="_blank" rel="noopener" title="Buka gambar ukuran penuh">
                                <img src="{{ $src }}" alt="Gambar layar: {{ $step['title'] }}" loading="lazy" decoding="async" onerror="this.closest('a').remove()">
                            </a>
                        @endif
                    </div>
                    <figcaption>{{ $src ? 'Klik gambar untuk memperbesar' : 'Gambar layar belum tersedia' }}</figcaption>
                </figure>
            </li>
        @endforeach
    </ol>
</div>
</x-filament-panels::page>
