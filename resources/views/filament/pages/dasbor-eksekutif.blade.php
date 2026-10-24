@php
    use App\Support\Money;
    $k = $d['kpi'];
    $trend = $d['maintenance_trend'];
@endphp
<x-filament-panels::page>
<div class="rac-dash">
    <div class="rac-header">
        <div>
            <div class="rac-eyebrow">{{ $company?->name }}</div>
            <h1>Intelijen Aset</h1>
            <p>Intelijen aset eksekutif · klik kartu, grafik, atau baris untuk melihat datanya · per {{ $d['today']->translatedFormat('d F Y') }}</p>
        </div>
        <a class="rac-index" href="{{ $links['health'] }}" title="Buka aset yang perlu perhatian kesehatan">
            <span>Asset Care Index</span><strong>{{ number_format($k['asset_care_index'], 1, ',', '.') }}</strong><small>/ 100</small>
        </a>
    </div>

    <div class="rac-kpis">
        <a class="rac-kpi" href="{{ $links['assets'] }}"><span>Total aset</span><strong>{{ number_format($k['total_assets'], 0, ',', '.') }}</strong><small>Lihat register →</small></a>
        <a class="rac-kpi" href="{{ $links['assets'] }}"><span>Nilai aset</span><strong>{{ Money::short($k['asset_value']) }}</strong><small>Nilai perolehan →</small></a>
        <a class="rac-kpi good" href="{{ $links['health'] }}"><span>Kesehatan aset</span><strong>{{ number_format($k['asset_health'], 1, ',', '.') }}%</strong><small>Lihat masalah kesehatan →</small></a>
        <a class="rac-kpi danger" href="{{ $links['critical'] }}"><span>Aset kritis</span><strong>{{ $k['critical_assets'] }}</strong><small>Telusuri →</small></a>
        <a class="rac-kpi warning" href="{{ $links['open_wo'] }}"><span>Work order terbuka</span><strong>{{ $k['open_workorders'] }}</strong><small>Antrean kerja →</small></a>
        <a class="rac-kpi warning" href="{{ $links['overdue_pm'] }}"><span>PM terlambat</span><strong>{{ $k['overdue_pm'] }}</strong><small>Rencana terlambat →</small></a>
        <a class="rac-kpi" href="{{ $links['ytd_cost'] }}"><span>Biaya pemeliharaan YTD</span><strong>{{ Money::short($k['maintenance_cost']) }}</strong><small>Rincian biaya →</small></a>
        <a class="rac-kpi" href="{{ $links['capex'] }}"><span>Kebutuhan CAPEX</span><strong>{{ Money::short($k['capex_required']) }}</strong><small>Tinjau usulan →</small></a>
    </div>

    <div class="rac-grid">
        <section class="rac-panel wide">
            <h3>Kesehatan aset per departemen <small>klik departemen</small></h3>
            <div class="rac-bars">
                @forelse ($d['health_by_department'] as $row)
                    @php($url = $links['department']($row['id']))
                    <a class="rac-bar-row" @if($url) href="{{ $url }}" @endif title="{{ $row['total'] }} aset">
                        <span class="label">{{ $row['label'] }}</span>
                        <span class="track"><span class="fill {{ $row['value'] < 60 ? 'bad' : ($row['value'] < 75 ? 'mid' : '') }}" style="width: {{ $row['value'] }}%"></span></span>
                        <b>{{ number_format($row['value'], 1, ',', '.') }}%</b>
                    </a>
                @empty
                    <p class="rac-muted">Belum ada aset.</p>
                @endforelse
            </div>
        </section>

        <section class="rac-panel">
            <h3>Distribusi kondisi aset <small>klik kondisi</small></h3>
            <div class="rac-conditions">
                @forelse ($d['condition_distribution'] as $row)
                    <a class="rac-condition" href="{{ $links['condition']($row['key']) }}">
                        <span class="dot {{ $row['key'] }}"></span><span>{{ $row['label'] }}</span><b>{{ $row['value'] }}</b><small>{{ $row['percent'] }}%</small>
                    </a>
                @empty
                    <p class="rac-muted">Belum ada aset.</p>
                @endforelse
            </div>
        </section>

        <section class="rac-panel wide">
            <h3>Tren pemeliharaan 12 bulan <small>klik batang atau bulan</small></h3>
            <div class="rac-trend">
                @foreach ($trend['months'] as $m)
                    <div class="rac-trend-month">
                        <div class="bars">
                            @foreach (['preventive' => 'Preventif', 'corrective' => 'Korektif', 'emergency' => 'Darurat'] as $type => $label)
                                <a class="{{ $type }}" href="{{ $links['trend']($m, $type) }}" style="height: {{ $m[$type] / $trend['max'] * 100 }}%" title="{{ $m['full_label'] }}: {{ $label }} {{ $m[$type] }}"></a>
                            @endforeach
                        </div>
                        <a class="month" href="{{ $links['trend']($m) }}">{{ $m['label'] }}</a>
                    </div>
                @endforeach
            </div>
            <div class="rac-legend"><span class="preventive">Preventif</span><span class="corrective">Korektif</span><span class="emergency">Darurat</span></div>
        </section>

        <a class="rac-panel" href="{{ $links['pm'] }}">
            <h3>Kepatuhan pemeliharaan preventif <small>klik untuk rincian</small></h3>
            <div class="rac-gauge" style="--pct: {{ $k['pm_compliance'] }}%">
                <div class="value"><strong>{{ number_format($k['pm_compliance'], 1, ',', '.') }}%</strong><span>Patuh</span></div>
            </div>
        </a>

        <section class="rac-panel">
            <h3>Matriks risiko aset <small>probabilitas × konsekuensi</small></h3>
            <div class="rac-matrix">
                @foreach ($d['risk_matrix'] as $cell)
                    <a class="cell {{ $cell['level'] }} {{ $cell['count'] ? 'has' : '' }}" href="{{ $links['risk']($cell['p'], $cell['c']) }}" title="P{{ $cell['p'] }} × K{{ $cell['c'] }}: {{ $cell['count'] }} risiko">
                        <b>{{ $cell['count'] ?: '' }}</b><small>{{ $cell['p'] }}×{{ $cell['c'] }}</small>
                    </a>
                @endforeach
            </div>
            <div class="rac-axis"><span>↑ Probabilitas</span><span>Konsekuensi →</span></div>
        </section>

        <section class="rac-panel wide">
            <h3>Biaya pemeliharaan per kategori aset (YTD) <small>klik kategori</small></h3>
            <div class="rac-bars cost">
                @forelse ($d['cost_by_category'] as $row)
                    <a class="rac-bar-row" href="{{ $links['cost_category']($row['id']) }}">
                        <span class="label">{{ $row['label'] }}</span>
                        <span class="track"><span class="fill" style="width: {{ $row['percent'] }}%"></span></span>
                        <b>{{ Money::short($row['value']) }}</b>
                    </a>
                @empty
                    <p class="rac-muted">Belum ada work order selesai tahun ini.</p>
                @endforelse
            </div>
        </section>

        <section class="rac-panel attention">
            <h3>Perlu perhatian manajemen <small>klik untuk membuka bukti aset</small></h3>
            <div class="rac-table-wrap">
            <table>
                <thead><tr><th>Aset</th><th>Lokasi</th><th>Kesehatan</th><th>Risiko</th><th>Prioritas</th><th></th></tr></thead>
                <tbody>
                    @forelse ($d['attention'] as $a)
                        <tr onclick="window.location='{{ $links['asset']($a) }}'">
                            <td><a href="{{ $links['asset']($a) }}">{{ $a->name }}</a><div class="rac-muted">{{ $a->code }}</div></td>
                            <td>{{ $a->location?->complete_name }}</td>
                            <td>{{ number_format($a->health_score, 1, ',', '.') }}%</td>
                            <td>{{ number_format($a->risk_score, 1, ',', '.') }}</td>
                            <td><span class="rac-badge {{ $a->priority->value }}">{{ $a->priority->getLabel() }}</span></td>
                            <td class="drill">Buka →</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="rac-muted">Belum ada aset.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </section>
    </div>
</div>
</x-filament-panels::page>
