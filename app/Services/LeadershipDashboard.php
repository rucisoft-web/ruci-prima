<?php

namespace App\Services;

use App\Enums\CapexCategory;
use App\Enums\Condition;
use App\Enums\RiskState;
use App\Enums\StockMovementType;
use App\Enums\VehicleStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRisk;
use App\Models\CapexProposal;
use App\Models\LegalDocument;
use App\Models\MeetingRoom;
use App\Models\RoomBooking;
use App\Models\StockMovement;
use App\Models\SupplyItem;
use App\Models\Vehicle;
use App\Models\VehicleFuelLog;
use App\Models\VehicleService;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Angka untuk Dasbor Pimpinan: menggabungkan aset, pemeliharaan, armada, ruang, persediaan, dan kepatuhan
 * menjadi indikator yang bisa langsung dipakai untuk keputusan. Semua query dibatasi ke perusahaan aktif.
 */
class LeadershipDashboard
{
    /** Hasil dihitung sekali per request karena dipakai halaman dan setiap grafik. */
    protected static array $memo = [];

    public function data(): array
    {
        $key = (string) filament()->getTenant()?->getKey();

        return static::$memo[$key] ??= $this->build(CarbonImmutable::today());
    }

    public static function flush(): void
    {
        static::$memo = [];
    }

    protected function build(CarbonImmutable $today): array
    {
        $asset = app(ExecutiveDashboard::class)->data($today);
        $months = collect(range(11, 0))->map(fn (int $i) => $today->startOfMonth()->subMonths($i));

        $costs = $this->monthlyCosts($months);
        $reactive = $this->reactiveTrend($months);
        $documents = $this->documentStatus();
        $fleet = $this->fleet($today);
        $rooms = $this->roomUtilization($today);
        $supplies = $this->supplyAvailability();
        $capex = $this->capex();
        $overdueLife = $this->pastEconomicLife($today);

        $ytd = $costs['rows']->filter(fn (array $m) => $m['date']->year === $today->year);
        $ytdCost = $ytd->sum('total');
        $prevQuarter = $costs['rows']->slice(6, 3)->sum('total');
        $lastQuarter = $costs['rows']->slice(9, 3)->sum('total');

        $readiness = [
            'Kesehatan & perawatan aset' => $asset['kpi']['asset_care_index'],
            'Kepatuhan izin & dokumen' => $documents['compliance'],
            'Kesiapan armada' => $fleet['readiness'],
            'Ketersediaan persediaan' => $supplies['availability'],
            'Kepatuhan PM' => $asset['kpi']['pm_compliance'],
        ];

        $openRisks = AssetRisk::query()->where('state', '!=', RiskState::Closed->value);

        $data = [
            'today' => $today,
            'readiness' => [
                'score' => round(collect($readiness)->avg(), 1),
                'parts' => $readiness,
            ],
            'kpi' => [
                'asset_value' => $asset['kpi']['asset_value'],
                'total_assets' => $asset['kpi']['total_assets'],
                'opex_ytd' => $ytdCost,
                'opex_change' => $prevQuarter > 0 ? round(($lastQuarter - $prevQuarter) / $prevQuarter * 100, 1) : 0.0,
                'reactive_now' => $reactive['last_quarter'],
                'reactive_before' => $reactive['previous_quarter'],
                'capex_pending' => $capex['pending_value'],
                'capex_pending_count' => $capex['pending_count'],
                'high_risks' => (clone $openRisks)->whereIn('level', ['high', 'critical'])->count(),
                'critical_risks' => (clone $openRisks)->where('level', 'critical')->count(),
                'documents_expired' => $documents['counts']['expired'],
                'documents_expiring' => $documents['counts']['expiring'],
                'room_utilization' => $rooms['overall'],
                'fleet_ready' => $fleet['ready'],
                'fleet_total' => $fleet['total'],
                'past_life_value' => $overdueLife['value'],
                'past_life_count' => $overdueLife['count'],
            ],
            'decisions' => $this->decisions($today),
            'charts' => [
                'costs' => $costs['chart'],
                'reactive' => $reactive['chart'],
                'condition_value' => $this->conditionValue(),
                'capex' => $capex['chart'],
                'past_life' => $overdueLife['chart'],
                'rooms' => $rooms['chart'],
                'fleet_cost' => $fleet['chart'],
                'documents' => $documents['chart'],
            ],
            'risk_matrix' => $asset['risk_matrix'],
        ];

        $data['insights'] = $this->insights($data, $costs, $fleet, $rooms, $capex);

        return $data;
    }

    /** Biaya operasional per bulan: pemeliharaan aset, BBM/energi, servis kendaraan, dan persediaan terpakai. */
    protected function monthlyCosts(Collection $months): array
    {
        $from = $months->first();
        $key = fn ($date) => CarbonImmutable::parse($date)->format('Y-m');

        $maintenance = WorkOrder::query()->whereIn('state', WorkOrder::COMPLETED_STATES)->where('completed_at', '>=', $from)
            ->get(['completed_at', 'total_cost'])->groupBy(fn ($w) => $key($w->completed_at))->map->sum('total_cost');
        $fuel = VehicleFuelLog::query()->where('filled_at', '>=', $from)->get(['filled_at', 'amount'])
            ->groupBy(fn ($f) => $key($f->filled_at))->map->sum('amount');
        $service = VehicleService::query()->where('service_date', '>=', $from)->get(['service_date', 'cost'])
            ->groupBy(fn ($s) => $key($s->service_date))->map->sum('cost');
        $supplies = StockMovement::query()->where('type', StockMovementType::Out->value)->where('moved_at', '>=', $from)
            ->with('item:id,unit_cost')->get(['id', 'supply_item_id', 'moved_at', 'quantity', 'unit_cost'])
            ->groupBy(fn ($m) => $key($m->moved_at))
            ->map(fn ($rows) => $rows->sum(fn ($m) => abs((float) $m->quantity) * (float) ($m->unit_cost ?: $m->item?->unit_cost)));

        $rows = $months->map(function (CarbonImmutable $month) use ($maintenance, $fuel, $service, $supplies) {
            $k = $month->format('Y-m');
            $row = [
                'date' => $month,
                'label' => $month->translatedFormat('M y'),
                'maintenance' => (float) ($maintenance[$k] ?? 0),
                'fuel' => (float) ($fuel[$k] ?? 0),
                'service' => (float) ($service[$k] ?? 0),
                'supplies' => (float) ($supplies[$k] ?? 0),
            ];

            return $row + ['total' => $row['maintenance'] + $row['fuel'] + $row['service'] + $row['supplies']];
        })->values();

        $juta = fn (string $field) => $rows->map(fn ($r) => round($r[$field] / 1e6, 1))->all();

        return [
            'rows' => $rows,
            'chart' => [
                'labels' => $rows->pluck('label')->all(),
                'datasets' => [
                    ['label' => 'Pemeliharaan aset', 'data' => $juta('maintenance')],
                    ['label' => 'BBM & energi kendaraan', 'data' => $juta('fuel')],
                    ['label' => 'Servis kendaraan', 'data' => $juta('service')],
                    ['label' => 'Persediaan terpakai', 'data' => $juta('supplies')],
                ],
                'unit' => 'Rp juta',
            ],
        ];
    }

    /** Persentase pekerjaan korektif + darurat dari seluruh work order per bulan. */
    protected function reactiveTrend(Collection $months): array
    {
        $rows = WorkOrder::query()->where('planned_at', '>=', $months->first())->where('state', '!=', 'cancel')
            ->get(['planned_at', 'work_type'])->groupBy(fn ($w) => $w->planned_at->format('Y-m'));

        $values = $months->map(function (CarbonImmutable $m) use ($rows) {
            $items = $rows->get($m->format('Y-m'), collect());
            $total = $items->count();

            return $total ? round($items->filter(fn ($w) => $w->work_type->value !== 'preventive')->count() / $total * 100, 1) : 0.0;
        })->values();

        return [
            'last_quarter' => round($values->slice(9, 3)->avg() ?? 0, 1),
            'previous_quarter' => round($values->slice(6, 3)->avg() ?? 0, 1),
            'chart' => [
                'labels' => $months->map(fn ($m) => $m->translatedFormat('M y'))->all(),
                'datasets' => [['label' => 'Pekerjaan reaktif', 'data' => $values->all()]],
                'unit' => '%',
            ],
        ];
    }

    /** Nilai perolehan aset per kondisi: berapa modal yang tertanam di aset berkondisi buruk. */
    protected function conditionValue(): array
    {
        $sums = Asset::query()->where('is_active', true)->get(['condition', 'acquisition_cost'])
            ->groupBy(fn ($a) => $a->condition?->value)->map->sum('acquisition_cost');
        $cases = collect(Condition::cases());

        return [
            'labels' => $cases->map->getLabel()->all(),
            'keys' => $cases->map->value->all(),
            'datasets' => [['label' => 'Nilai aset', 'data' => $cases->map(fn ($c) => round(($sums[$c->value] ?? 0) / 1e9, 2))->all()]],
            'unit' => 'Rp miliar',
        ];
    }

    protected function capex(): array
    {
        $open = CapexProposal::query()->whereIn('state', ['draft', 'proposed'])->get();
        $byCategory = $open->groupBy(fn ($c) => $c->category instanceof CapexCategory ? $c->category->value : $c->category);
        $cases = collect(CapexCategory::cases())->filter(fn ($c) => $byCategory->has($c->value))->values();

        return [
            'pending_value' => (float) $open->sum('estimated_cost'),
            'pending_count' => $open->count(),
            'items' => $open,
            'chart' => [
                'labels' => $cases->map->getLabel()->all(),
                'datasets' => [['label' => 'Usulan CAPEX belum diputuskan', 'data' => $cases->map(fn ($c) => round($byCategory[$c->value]->sum('estimated_cost') / 1e9, 2))->all()]],
                'unit' => 'Rp miliar',
            ],
        ];
    }

    /** Aset aktif yang umurnya sudah melewati umur ekonomis kategorinya. */
    protected function pastEconomicLife(CarbonImmutable $today): array
    {
        $categories = AssetCategory::query()->pluck('name', 'id');
        $past = Asset::query()->where('is_active', true)->whereNotNull('purchase_date')
            ->get(['id', 'asset_category_id', 'purchase_date', 'expected_life_years', 'acquisition_cost'])
            ->filter(fn (Asset $a) => $a->expected_life_years > 0 && $a->purchase_date->diffInYears($today) >= $a->expected_life_years);
        $grouped = $past->groupBy('asset_category_id')->sortByDesc(fn ($rows) => $rows->sum('acquisition_cost'))->take(8);

        return [
            'count' => $past->count(),
            'value' => (float) $past->sum('acquisition_cost'),
            'chart' => [
                'labels' => $grouped->keys()->map(fn ($id) => $categories[$id] ?? 'Lainnya')->values()->all(),
                'datasets' => [['label' => 'Nilai aset melewati umur ekonomis', 'data' => $grouped->map(fn ($rows) => round($rows->sum('acquisition_cost') / 1e9, 2))->values()->all()]],
                'unit' => 'Rp miliar',
            ],
        ];
    }

    /** Utilisasi 30 hari kerja terakhir: jam terpakai ÷ (hari kerja × 8 jam). */
    protected function roomUtilization(CarbonImmutable $today): array
    {
        $from = $today->subDays(30);
        $workdays = collect(range(1, 30))->filter(fn ($i) => ! $today->subDays($i)->isWeekend())->count();
        $capacityHours = max(1, $workdays * 8);
        $rooms = MeetingRoom::query()->where('is_active', true)->get();
        $hours = RoomBooking::query()->where('state', 'approved')->where('starts_at', '>=', $from)->where('starts_at', '<', $today)
            ->get(['meeting_room_id', 'starts_at', 'ends_at'])
            ->groupBy('meeting_room_id')->map(fn ($rows) => $rows->sum(fn ($b) => $b->starts_at->diffInMinutes($b->ends_at) / 60));

        $values = $rooms->mapWithKeys(fn ($room) => [$room->name => round(($hours[$room->id] ?? 0) / $capacityHours * 100, 1)]);

        return [
            'overall' => $rooms->count() ? round($values->avg(), 1) : 0.0,
            'values' => $values,
            'chart' => [
                'labels' => $values->keys()->all(),
                'datasets' => [['label' => 'Utilisasi 30 hari', 'data' => $values->values()->all()]],
                'unit' => '%',
            ],
        ];
    }

    /** Kesiapan armada operasional dan biaya per km 12 bulan (BBM + servis ÷ jarak tempuh). */
    protected function fleet(CarbonImmutable $today): array
    {
        $vehicles = Vehicle::query()->get();
        $from = $today->subMonths(12);
        $fuel = VehicleFuelLog::query()->where('filled_at', '>=', $from)->get(['vehicle_id', 'amount', 'odometer'])->groupBy('vehicle_id');
        $service = VehicleService::query()->where('service_date', '>=', $from)->get(['vehicle_id', 'cost'])->groupBy('vehicle_id');

        $perKm = $vehicles->mapWithKeys(function (Vehicle $v) use ($fuel, $service) {
            $logs = $fuel->get($v->id, collect());
            $distance = $logs->count() > 1 ? $logs->max('odometer') - $logs->min('odometer') : 0;
            $cost = $logs->sum('amount') + $service->get($v->id, collect())->sum('cost');

            return [$v->plate_number.' · '.str($v->name)->before(' (')->limit(28) => $distance > 0 ? round($cost / $distance) : 0];
        })->filter()->sortDesc();

        $dueSoon = $vehicles->filter(fn (Vehicle $v) => $v->dueItems($today->addDays(14))->isNotEmpty())->count();
        $active = $vehicles->where('status', '!=', VehicleStatus::Inactive)->count();
        $ready = $vehicles->where('status', VehicleStatus::Available)->count();

        return [
            'total' => $active,
            'ready' => $ready,
            'due_soon' => $dueSoon,
            'readiness' => $active ? round($ready / $active * 100 - $dueSoon / $active * 10, 1) : 100.0,
            'per_km' => $perKm,
            'chart' => [
                'labels' => $perKm->keys()->all(),
                'datasets' => [['label' => 'Biaya per km', 'data' => $perKm->values()->all()]],
                'unit' => 'Rp/km',
            ],
        ];
    }

    protected function documentStatus(): array
    {
        $docs = LegalDocument::query()->where('is_active', true)->get();
        $counts = ['valid' => 0, 'permanent' => 0, 'expiring' => 0, 'expired' => 0];
        foreach ($docs as $doc) {
            $counts[$doc->status->value]++;
        }
        $total = max(1, $docs->count());

        return [
            'counts' => $counts,
            'compliance' => round(($total - $counts['expired'] - $counts['expiring'] * 0.5) / $total * 100, 1),
            'chart' => [
                'labels' => ['Berlaku', 'Segera berakhir', 'Kedaluwarsa'],
                'datasets' => [['label' => 'Dokumen', 'data' => [$counts['valid'] + $counts['permanent'], $counts['expiring'], $counts['expired']]]],
                'unit' => 'dokumen',
            ],
        ];
    }

    protected function supplyAvailability(): array
    {
        $items = SupplyItem::query()->where('is_active', true)->get();

        return ['availability' => $items->count() ? round($items->reject->isLow()->count() / $items->count() * 100, 1) : 100.0];
    }

    /** Hal yang perlu keputusan atau perhatian pimpinan, diurutkan dari yang paling berdampak. */
    protected function decisions(CarbonImmutable $today): Collection
    {
        $capex = CapexProposal::query()->where('state', 'proposed')->with('asset')->orderByDesc('priority_score')->limit(4)->get()
            ->map(fn (CapexProposal $c) => [
                'kind' => 'capex', 'tone' => 'info', 'label' => 'Usulan CAPEX',
                'title' => $c->name, 'detail' => 'Skor prioritas '.number_format((float) $c->priority_score, 1, ',', '.').' · target '.$c->target_year,
                'amount' => (float) $c->estimated_cost, 'id' => $c->id, 'weight' => 300 + (float) $c->priority_score,
            ]);

        $risks = AssetRisk::query()->where('state', '!=', RiskState::Closed->value)->where('level', 'critical')->with('asset')->orderByDesc('risk_score')->limit(3)->get()
            ->map(fn (AssetRisk $r) => [
                'kind' => 'risk', 'tone' => 'danger', 'label' => 'Risiko kritis',
                'title' => $r->name, 'detail' => ($r->asset?->name ? $r->asset->name.' · ' : '').'Mitigasi: '.str($r->mitigation)->limit(70),
                'amount' => null, 'id' => $r->id, 'weight' => 400 + $r->risk_score,
            ]);

        $docs = LegalDocument::query()->where('is_active', true)->whereDate('expiry_date', '<', $today)->orderBy('expiry_date')->limit(3)->get()
            ->map(fn (LegalDocument $d) => [
                'kind' => 'document', 'tone' => 'danger', 'label' => 'Izin kedaluwarsa',
                'title' => $d->name, 'detail' => 'Berakhir '.$d->expiry_date->translatedFormat('d M Y').' · '.abs($d->daysLeft()).' hari lalu',
                'amount' => null, 'id' => $d->id, 'weight' => 500 - abs($d->daysLeft()),
            ]);

        return $docs->concat($risks)->concat($capex)->sortByDesc('weight')->values();
    }

    /** Kalimat ringkas yang dibangkitkan dari angka, untuk dibaca dalam satu menit. */
    protected function insights(array $data, array $costs, array $fleet, array $rooms, array $capex): array
    {
        $k = $data['kpi'];
        $notes = [];

        $delta = $k['reactive_now'] - $k['reactive_before'];
        $notes[] = [
            'tone' => $delta <= 0 ? 'good' : 'warning',
            'text' => sprintf('Pekerjaan reaktif (korektif + darurat) %s menjadi %s%% dari seluruh pekerjaan dalam 3 bulan terakhir (sebelumnya %s%%). %s',
                $delta <= 0 ? 'turun' : 'naik', $this->num($k['reactive_now']), $this->num($k['reactive_before']),
                $delta <= 0 ? 'Disiplin pemeliharaan preventif mulai menekan kerusakan mendadak.' : 'Periksa aset yang paling sering rusak sebelum biayanya membesar.'),
        ];

        if ($k['documents_expired'] + $k['documents_expiring'] > 0) {
            $notes[] = [
                'tone' => $k['documents_expired'] > 0 ? 'danger' : 'warning',
                'text' => sprintf('%d izin/dokumen sudah kedaluwarsa dan %d akan berakhir dalam masa pengingat. Izin yang lewat (SLF, SILO, sertifikat) berisiko sanksi dan klaim asuransi ditolak.',
                    $k['documents_expired'], $k['documents_expiring']),
            ];
        }

        if ($k['past_life_count'] > 0) {
            $notes[] = [
                'tone' => 'warning',
                'text' => sprintf('%d aset senilai %s sudah melewati umur ekonomisnya. Ini dasar perencanaan peremajaan dan CAPEX beberapa tahun ke depan.',
                    $k['past_life_count'], \App\Support\Money::short($k['past_life_value'])),
            ];
        }

        $top = $costs['rows']->slice(-3);
        $mix = collect(['maintenance' => 'pemeliharaan aset', 'fuel' => 'BBM & energi', 'service' => 'servis kendaraan', 'supplies' => 'persediaan'])
            ->map(fn ($label, $field) => ['label' => $label, 'value' => $top->sum($field)])->sortByDesc('value')->first();
        if ($top->sum('total') > 0) {
            $notes[] = [
                'tone' => 'info',
                'text' => sprintf('Biaya operasional 3 bulan terakhir %s, %s dibanding 3 bulan sebelumnya. Porsi terbesar dari %s (%s%%).',
                    \App\Support\Money::short($top->sum('total')),
                    $k['opex_change'] >= 0 ? 'naik '.$this->num($k['opex_change']).'%' : 'turun '.$this->num(abs($k['opex_change'])).'%',
                    $mix['label'], $this->num($mix['value'] / $top->sum('total') * 100)),
            ];
        }

        if ($rooms['values']->isNotEmpty()) {
            $low = $rooms['values']->sort()->keys()->first();
            $high = $rooms['values']->sortDesc()->keys()->first();
            $notes[] = [
                'tone' => 'info',
                'text' => sprintf('Utilisasi ruang rapat rata-rata %s%%. Tertinggi %s (%s%%), terendah %s (%s%%): pertimbangkan menggabung atau mengalihfungsikan ruang yang jarang dipakai.',
                    $this->num($k['room_utilization']), $high, $this->num($rooms['values'][$high]), $low, $this->num($rooms['values'][$low])),
            ];
        }

        if ($fleet['per_km']->isNotEmpty()) {
            $notes[] = [
                'tone' => 'info',
                'text' => sprintf('Kendaraan dengan biaya per km tertinggi: %s (Rp %s/km). Bandingkan dengan opsi sewa atau peremajaan.',
                    $fleet['per_km']->keys()->first(), number_format($fleet['per_km']->first(), 0, ',', '.')),
            ];
        }

        if ($capex['pending_count'] > 0) {
            $notes[] = [
                'tone' => 'info',
                'text' => sprintf('%d usulan CAPEX senilai %s menunggu keputusan; setiap usulan sudah memiliki skor prioritas berbasis keselamatan, pelanggan, pendapatan, dan urgensi.',
                    $capex['pending_count'], \App\Support\Money::short($capex['items']->sum('estimated_cost'))),
            ];
        }

        return $notes;
    }

    protected function num(float $value): string
    {
        return number_format($value, 1, ',', '.');
    }
}
