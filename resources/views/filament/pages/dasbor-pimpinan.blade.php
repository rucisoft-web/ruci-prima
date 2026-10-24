@php
    use App\Filament\App\Widgets\PimpinanChart;
    use App\Support\Money;
    $k = $d['kpi'];
    $r = $d['readiness'];
    $tone = fn (float $v) => $v >= 80 ? 'good' : ($v >= 65 ? 'warning' : 'danger');
    $num = fn ($v, $dec = 1) => number_format((float) $v, $dec, ',', '.');
    $kindLink = ['capex' => $links['capex'], 'risk' => $links['risks'], 'document' => $links['documents']];
@endphp
<x-filament-panels::page>
<div class="rac-dash rac-lead">
    <div class="rac-header">
        <div>
            <div class="rac-eyebrow">{{ $company?->name }}</div>
            <h1>Dasbor Pimpinan</h1>
            <p>Ringkasan untuk direksi dan komisaris · data per {{ $d['today']->translatedFormat('d F Y') }} · <a href="{{ $links['why'] }}">Mengapa angka ini penting →</a></p>
        </div>
    </div>

    <section class="rac-lead-hero">
        <div class="rac-lead-score {{ $tone($r['score']) }}" style="--pct: {{ $r['score'] }}%">
            <div class="ring"><div><strong>{{ $num($r['score']) }}</strong><span>dari 100</span></div></div>
            <div>
                <h2>Indeks Kesiapan Operasional</h2>
                <p>Gabungan kesehatan aset, kepatuhan izin, kesiapan armada, ketersediaan persediaan, dan kepatuhan pemeliharaan preventif. Di atas 80 berarti operasional siap; di bawah 65 perlu intervensi pimpinan.</p>
            </div>
        </div>
        <div class="rac-lead-parts">
            @foreach ($r['parts'] as $label => $value)
                <div class="part">
                    <span>{{ $label }}</span>
                    <div class="track"><div class="fill {{ $tone($value) }}" style="width: {{ max(2, min(100, $value)) }}%"></div></div>
                    <b>{{ $num($value) }}</b>
                </div>
            @endforeach
        </div>
    </section>

    <div class="rac-kpis rac-lead-kpis">
        <a class="rac-kpi" href="{{ $links['assets'] }}"><span>Nilai aset dikelola</span><strong>{{ Money::short($k['asset_value']) }}</strong><small>{{ $num($k['total_assets'], 0) }} aset tercatat</small></a>
        <a class="rac-kpi {{ $k['opex_change'] > 5 ? 'warning' : 'good' }}" href="{{ $links['work_orders'] }}"><span>Biaya operasional tahun berjalan</span><strong>{{ Money::short($k['opex_ytd']) }}</strong><small>3 bln terakhir {{ $k['opex_change'] >= 0 ? '▲' : '▼' }} {{ $num(abs($k['opex_change'])) }}% vs 3 bln sebelumnya</small></a>
        <a class="rac-kpi {{ $k['reactive_now'] <= $k['reactive_before'] ? 'good' : 'warning' }}" href="{{ $links['work_orders'] }}"><span>Pekerjaan reaktif</span><strong>{{ $num($k['reactive_now']) }}%</strong><small>sebelumnya {{ $num($k['reactive_before']) }}% · makin rendah makin baik</small></a>
        <a class="rac-kpi" href="{{ $links['capex'] }}"><span>CAPEX menunggu keputusan</span><strong>{{ Money::short($k['capex_pending']) }}</strong><small>{{ $k['capex_pending_count'] }} usulan berprioritas</small></a>
        <a class="rac-kpi {{ $k['critical_risks'] ? 'danger' : 'good' }}" href="{{ $links['risks'] }}"><span>Risiko tinggi & kritis terbuka</span><strong>{{ $k['high_risks'] }}</strong><small>{{ $k['critical_risks'] }} di antaranya kritis</small></a>
        <a class="rac-kpi {{ $k['documents_expired'] ? 'danger' : ($k['documents_expiring'] ? 'warning' : 'good') }}" href="{{ $links['documents'] }}"><span>Izin & dokumen bermasalah</span><strong>{{ $k['documents_expired'] + $k['documents_expiring'] }}</strong><small>{{ $k['documents_expired'] }} kedaluwarsa · {{ $k['documents_expiring'] }} segera berakhir</small></a>
        <a class="rac-kpi" href="{{ $links['vehicles'] }}"><span>Kesiapan armada operasional</span><strong>{{ $k['fleet_ready'] }} / {{ $k['fleet_total'] }}</strong><small>kendaraan siap jalan hari ini</small></a>
        <a class="rac-kpi" href="{{ $links['rooms'] }}"><span>Utilisasi ruang rapat</span><strong>{{ $num($k['room_utilization']) }}%</strong><small>rata-rata 30 hari kerja</small></a>
    </div>

    <div class="rac-lead-split">
        <section class="rac-panel">
            <h3>Menunggu keputusan Anda <small>urut dari dampak terbesar</small></h3>
            <div class="rac-list">
                @forelse ($d['decisions'] as $item)
                    <a class="rac-decision" href="{{ $kindLink[$item['kind']] }}">
                        <span class="rac-chip {{ $item['tone'] }}">{{ $item['label'] }}</span>
                        <div><b>{{ $item['title'] }}</b><div class="rac-muted">{{ $item['detail'] }}</div></div>
                        <strong>{{ $item['amount'] ? Money::short($item['amount']) : '' }}</strong>
                    </a>
                @empty
                    <p class="rac-muted">Tidak ada keputusan yang tertunda.</p>
                @endforelse
            </div>
        </section>
        <section class="rac-panel">
            <h3>Catatan untuk pimpinan <small>dibuat otomatis dari data</small></h3>
            <ul class="rac-insights">
                @foreach ($d['insights'] as $note)
                    <li class="{{ $note['tone'] }}">{{ $note['text'] }}</li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="rac-lead-charts">
        <div class="span-2">@livewire(PimpinanChart::class, ['chart' => 'costs'], key('costs'))</div>
        <div>@livewire(PimpinanChart::class, ['chart' => 'reactive'], key('reactive'))</div>
        <div>@livewire(PimpinanChart::class, ['chart' => 'condition_value'], key('condition_value'))</div>
        <div>@livewire(PimpinanChart::class, ['chart' => 'capex'], key('capex'))</div>
        <div>@livewire(PimpinanChart::class, ['chart' => 'past_life'], key('past_life'))</div>
        <div>@livewire(PimpinanChart::class, ['chart' => 'documents'], key('documents'))</div>
        <section class="rac-panel">
            <h3>Peta risiko aset <small>probabilitas × konsekuensi, klik sel</small></h3>
            <div class="rac-matrix">
                @foreach ($d['risk_matrix'] as $cell)
                    <a class="cell {{ $cell['level'] }} {{ $cell['count'] ? 'has' : '' }}" href="{{ $links['risk']($cell['p'], $cell['c']) }}" title="P{{ $cell['p'] }} × K{{ $cell['c'] }}: {{ $cell['count'] }} risiko">
                        <b>{{ $cell['count'] ?: '' }}</b><small>{{ $cell['p'] }}×{{ $cell['c'] }}</small>
                    </a>
                @endforeach
            </div>
            <div class="rac-axis"><span>↑ Probabilitas</span><span>Konsekuensi →</span></div>
        </section>
        <div>@livewire(PimpinanChart::class, ['chart' => 'rooms'], key('rooms'))</div>
        <div class="span-full">@livewire(PimpinanChart::class, ['chart' => 'fleet_cost'], key('fleet_cost'))</div>
    </div>

    <p class="rac-muted rac-lead-foot">Rincian teknis aset ada di <a href="{{ $links['asset_dashboard'] }}">Dasbor Aset</a>. Setiap kartu dan daftar dapat diklik untuk membuka data di baliknya.</p>
</div>
</x-filament-panels::page>
