<?php

namespace App\Filament\App\Widgets;

use App\Services\LeadershipDashboard;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * Satu grafik Dasbor Pimpinan; jenis grafik dipilih lewat properti $chart.
 * Warna kategori mengikuti urutan tetap (biru, oranye, hijau toska, kuning); warna status hanya untuk kondisi/kepatuhan.
 */
class PimpinanChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    public string $chart = 'costs';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected const SERIES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100'];

    protected const STATUS = ['good' => '#0ca30c', 'warning' => '#fab219', 'serious' => '#ec835a', 'critical' => '#d03b3b'];

    protected const META = [
        'costs' => ['Biaya operasional 12 bulan', 'Rp juta per bulan: pemeliharaan aset, BBM, servis kendaraan, dan persediaan', 'bar'],
        'reactive' => ['Porsi pekerjaan reaktif', 'Korektif + darurat ÷ seluruh work order. Makin rendah makin terkendali', 'line'],
        'condition_value' => ['Nilai aset per kondisi', 'Rp miliar nilai perolehan; modal yang tertanam di aset berkondisi buruk', 'bar'],
        'capex' => ['Usulan CAPEX belum diputuskan', 'Rp miliar per kategori (draf + diajukan)', 'bar'],
        'past_life' => ['Aset melewati umur ekonomis', 'Rp miliar per kategori; dasar rencana peremajaan', 'bar'],
        'rooms' => ['Utilisasi ruang rapat', 'Persentase jam kerja terpakai, 30 hari terakhir', 'bar'],
        'fleet_cost' => ['Biaya kendaraan per km', 'BBM/energi + servis 12 bulan ÷ jarak tempuh', 'bar'],
        'documents' => ['Status izin & dokumen', 'Jumlah dokumen aktif menurut masa berlaku', 'doughnut'],
    ];

    public function getHeading(): string
    {
        return self::META[$this->chart][0];
    }

    public function getDescription(): string
    {
        return self::META[$this->chart][1];
    }

    protected function getType(): string
    {
        return self::META[$this->chart][2];
    }

    protected function getData(): array
    {
        $data = app(LeadershipDashboard::class)->data()['charts'][$this->chart];
        $datasets = collect($data['datasets'])->values()->map(fn (array $set, int $i) => $set + $this->style($i, $data))->all();

        // Label sumbu kategori dipendekkan agar tidak terpotong pada kartu yang sempit.
        $labels = $this->isHorizontal() ? array_map(fn ($l) => (string) str($l)->limit($this->chart === 'fleet_cost' ? 32 : 18), $data['labels']) : $data['labels'];

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    protected function isHorizontal(): bool
    {
        return in_array($this->chart, ['capex', 'past_life', 'rooms', 'fleet_cost'], true);
    }

    protected function style(int $index, array $data): array
    {
        $color = self::SERIES[$index % 4];

        return match ($this->chart) {
            'costs' => ['backgroundColor' => $color, 'borderColor' => '#ffffff', 'borderWidth' => 1, 'borderRadius' => 4, 'maxBarThickness' => 28],
            'reactive' => ['borderColor' => $color, 'backgroundColor' => $color.'22', 'fill' => true, 'tension' => 0.3, 'borderWidth' => 2, 'pointRadius' => 4, 'pointHoverRadius' => 6],
            'condition_value' => [
                'backgroundColor' => collect($data['keys'])->map(fn ($k) => match ($k) {
                    'excellent', 'good' => self::STATUS['good'], 'fair' => self::STATUS['warning'], 'poor' => self::STATUS['serious'], default => self::STATUS['critical'],
                })->all(),
                'borderRadius' => 4, 'maxBarThickness' => 48,
            ],
            'documents' => ['backgroundColor' => [self::STATUS['good'], self::STATUS['warning'], self::STATUS['critical']], 'borderColor' => '#ffffff', 'borderWidth' => 2],
            default => ['backgroundColor' => self::SERIES[0], 'borderRadius' => 4, 'maxBarThickness' => 26],
        };
    }

    protected function getOptions(): RawJs|array
    {
        $unit = "'".addslashes(app(LeadershipDashboard::class)->data()['charts'][$this->chart]['unit'])."'";
        $horizontal = $this->isHorizontal() ? 'true' : 'false';
        $skip = in_array($this->chart, ['costs', 'reactive'], true) ? 'true' : 'false';
        $stacked = $this->chart === 'costs' ? 'true' : 'false';

        if ($this->chart === 'documents') {
            return RawJs::make(<<<'JS'
                { cutout: '62%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } } }
            JS);
        }

        return RawJs::make(<<<JS
            {
                indexAxis: {$horizontal} ? 'y' : 'x',
                layout: { padding: { left: 8 } },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: {$stacked}, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: (c) => ' ' + c.dataset.label + ': ' + new Intl.NumberFormat('id-ID').format(c.parsed[{$horizontal} ? 'x' : 'y']) + ' ' + {$unit} } },
                },
                scales: {
                    x: { stacked: {$stacked}, grid: { display: {$horizontal} }, ticks: { autoSkip: {$skip}, maxRotation: 0 } },
                    y: { stacked: {$stacked}, beginAtZero: true, grid: { display: ! {$horizontal}, color: 'rgba(120,120,120,.12)' } },
                },
            }
        JS);
    }
}
