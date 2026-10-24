<?php

namespace Database\Seeders\Demo;

use App\Enums\AssetStatus;
use App\Enums\AssignmentState;
use App\Enums\IdentificationSource;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Enums\SurveyState;
use App\Enums\VerificationStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRisk;
use App\Models\AssetSurvey;
use App\Models\AssetTemplate;
use App\Models\AuditLog;
use App\Models\CapexProposal;
use App\Models\Company;
use App\Models\Department;
use App\Models\Inspection;
use App\Models\LegalDocument;
use App\Models\Location;
use App\Models\MaintenanceRequest;
use App\Models\MeetingRoom;
use App\Models\PreventivePlan;
use App\Models\RoomBooking;
use App\Models\SupplyItem;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use App\Models\VehicleFuelLog;
use App\Models\VehicleService;
use App\Models\WorkOrder;
use App\Services\AssetIntake;
use App\Support\CurrentCompany;
use App\Support\Sequence;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pembangkit data contoh satu perusahaan dari sebuah profil (lihat subkelas).
 * Profil berisi struktur organisasi, lokasi, aset, armada, persediaan, dan dokumen;
 * kelas ini mengisi seluruh modul Ruci PRIMA secara deterministik (seed acak tetap).
 */
abstract class DemoCompany extends Seeder
{
    /** Akun yang dipakai bersama oleh ketiga perusahaan contoh untuk melihat lintas perusahaan. */
    public const GROUP_EXECUTIVE = 'pimpinan@prima.demo';

    protected Company $company;

    protected CarbonImmutable $today;

    protected array $p = [];

    /** @var array<string, int> */
    protected array $dept = [];

    /** @var array<string, int> */
    protected array $cat = [];

    /** @var array<string, Location> */
    protected array $loc = [];

    /** @var array<string, User> */
    protected array $users = [];

    /** @var Collection<string, array{0: SupplyItem, 1: int}> */
    protected Collection $supplies;

    abstract protected function profile(): array;

    public function run(): void
    {
        $this->p = $this->profile();
        $this->today = CarbonImmutable::today();
        mt_srand($this->p['seed']);

        if (Company::where('slug', $this->p['company']['slug'])->exists()) {
            $this->command?->warn("Data contoh {$this->p['company']['name']} sudah ada. Lewati.");

            return;
        }

        AuditLog::$enabled = false;
        $this->company = Company::create($this->p['company'] + ['is_demo' => true]);

        app(CurrentCompany::class)->runAs($this->company, function () {
            DB::transaction(function () {
                $this->users();
                $this->masterData();
                $this->locations();
                $this->assets();
                $this->maintenance();
                $this->risksAndCapex();
                $this->rooms();
                $this->vehicles();
                $this->supplies();
                $this->documents();
            });
        });

        AuditLog::$enabled = true;
    }

    /** Akun contoh perusahaan ini: email = lokal@domain profil. */
    public function emails(): array
    {
        $p = $this->p ?: $this->profile();

        return collect($p['users'])->map(fn (array $u) => $u[1].'@'.$p['domain'])->values()->all();
    }

    protected function rand(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    protected function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    protected function day(?int $offset): ?CarbonImmutable
    {
        return $offset === null ? null : $this->today->addDays($offset);
    }

    protected function dept(?string $code): ?int
    {
        return $code ? ($this->dept[$code] ?? null) : null;
    }

    protected function users(): void
    {
        $password = config('asset.demo.password');

        foreach ($this->p['users'] as $key => [$name, $local, $role, $title]) {
            $user = User::updateOrCreate(['email' => $local.'@'.$this->p['domain']], ['name' => $name, 'password' => $password, 'is_active' => true]);
            $user->memberships()->updateOrCreate(['company_id' => $this->company->id], ['role' => $role, 'position_title' => $title, 'is_active' => true]);
            $this->users[$key] = $user;
        }

        $group = User::updateOrCreate(['email' => self::GROUP_EXECUTIVE], ['name' => 'Pimpinan Grup (Demo)', 'password' => $password, 'is_active' => true]);
        $group->memberships()->updateOrCreate(['company_id' => $this->company->id], ['role' => Role::Eksekutif, 'position_title' => 'Komisaris / Pimpinan', 'is_active' => true]);
    }

    protected function masterData(): void
    {
        $i = 0;
        foreach ($this->p['departments'] as $code => $name) {
            $this->dept[$code] = Department::create(['name' => $name, 'code' => strtoupper($code), 'sort' => ++$i * 10])->id;
        }

        foreach ($this->p['categories'] as $code => [$name, $life, $crit]) {
            $this->cat[$code] = AssetCategory::create([
                'name' => $name, 'code' => strtoupper($code), 'expected_life_years' => $life, 'default_criticality' => $crit,
            ])->id;
        }
    }

    protected function locations(): void
    {
        foreach ($this->p['locations'] as [$key, $name, $parent]) {
            $this->loc[$key] = Location::create(['name' => $name, 'parent_id' => $parent ? $this->loc[$parent]->id : null]);
        }
    }

    protected function assets(): void
    {
        $survey = AssetSurvey::create([
            'name' => $this->p['survey']['name'], 'date_start' => $this->today->subMonths(5)->startOfMonth(), 'state' => SurveyState::InProgress,
            'manager_id' => $this->users['manajer']->id, 'note' => $this->p['survey']['note'],
        ]);
        $intake = app(AssetIntake::class);
        $verifier = $this->users['teknisi'];

        foreach ($this->p['templates'] as $def) {
            $template = AssetTemplate::create(['name' => $def['name'], 'location_type' => $def['location_type'], 'note' => $def['note'] ?? null]);

            foreach ($def['lines'] as $i => [$name, $cat, $qty, $dept, $brand, $model]) {
                $template->lines()->create([
                    'sort' => ($i + 1) * 10, 'name' => $name, 'asset_category_id' => $this->cat[$cat], 'quantity' => $qty,
                    'department_id' => $this->dept[$dept], 'brand' => $brand, 'model' => $model,
                    'expected_life_years' => $this->p['categories'][$cat][1],
                ]);
            }

            $locationIds = collect($def['locations'])->map(fn ($k) => $this->loc[$k]->id)->all();
            $intake->fromTemplate($template, $locationIds, $survey->id);

            foreach ($def['teams'] as [$scope, $team, $state]) {
                $survey->assignments()->create([
                    'location_id' => $this->loc[$scope]->id, 'team_name' => $team, 'asset_template_id' => $template->id, 'state' => $state,
                ])->surveyors()->sync([$this->users['teknisi']->id, $this->users['supervisor']->id]);
            }

            // Lokasi awal sudah selesai disurvei, sebagian sedang berjalan, sisanya belum dikunjungi.
            $order = array_flip($locationIds);
            $count = max(1, count($locationIds));

            Asset::where('asset_survey_id', $survey->id)->whereIn('location_id', $locationIds)->whereNull('verified_at')->get()
                ->each(function (Asset $asset) use ($order, $count, $verifier) {
                    $progress = $order[$asset->location_id] / $count;
                    $roll = $this->rand(1, 100);
                    $status = match (true) {
                        $progress >= 0.8 => VerificationStatus::Expected,
                        $progress >= 0.62 && $roll > 50 => VerificationStatus::Expected,
                        $roll <= 4 => VerificationStatus::Missing,
                        default => VerificationStatus::Verified,
                    };

                    $this->randomizeHealth($asset, $status === VerificationStatus::Missing);
                    $asset->fill([
                        'verification_status' => $status,
                        'verified_by_id' => $status === VerificationStatus::Expected ? null : $verifier->id,
                        'verified_at' => $status === VerificationStatus::Expected ? null : $this->today->subDays($this->rand(5, 120))->setTime($this->rand(8, 16), $this->rand(0, 59)),
                        'purchase_date' => $this->today->subYears($this->rand(1, 11))->subDays($this->rand(0, 300)),
                        'serial_no' => $status === VerificationStatus::Verified ? strtoupper(substr(md5($this->company->code.$asset->id), 0, 10)) : null,
                        'acquisition_cost' => $this->costFor($asset->asset_category_id),
                        'status' => $status === VerificationStatus::Missing ? AssetStatus::OutOfService : AssetStatus::Operational,
                        'verification_note' => $status === VerificationStatus::Missing ? 'Tidak ditemukan saat survei; diduga dipindah ke lokasi lain.' : null,
                    ]);
                    $asset->replacement_cost = round($asset->acquisition_cost * 1.25, -3);
                    $asset->save();
                });
        }

        foreach ($this->p['additional'] as [$loc, $name, $cat, $dept]) {
            $asset = new Asset([
                'name' => $name, 'asset_category_id' => $this->cat[$cat], 'location_id' => $this->loc[$loc]->id, 'department_id' => $this->dept[$dept],
                'verification_status' => VerificationStatus::Additional, 'identification_source' => IdentificationSource::Additional,
                'asset_survey_id' => $survey->id, 'verified_by_id' => $verifier->id, 'verified_at' => $this->today->subDays($this->rand(10, 60)),
                'acquisition_cost' => $this->costFor($this->cat[$cat]),
            ]);
            $this->randomizeHealth($asset);
            $asset->save();
        }

        // Aset utama: [nama, kategori, lokasi, departemen, merek, model, tahun beli, nilai (juta), profil kondisi, [keselamatan, pelanggan, pendapatan, operasional, penggantian]]
        foreach ($this->p['major'] as [$name, $cat, $loc, $dept, $brand, $model, $year, $value, $profile, $impacts]) {
            $asset = new Asset([
                'name' => $name, 'asset_category_id' => $this->cat[$cat], 'location_id' => $this->loc[$loc]->id, 'department_id' => $this->dept[$dept],
                'brand' => $brand, 'model' => $model, 'serial_no' => strtoupper(substr(md5($this->company->code.$name), 0, 12)),
                'purchase_date' => CarbonImmutable::create($year, $this->rand(1, 12), $this->rand(1, 28)),
                'acquisition_cost' => $value * 1_000_000, 'replacement_cost' => round($value * 1_000_000 * 1.35, -6),
                'verification_status' => VerificationStatus::Verified, 'identification_source' => IdentificationSource::Manual,
                'verified_by_id' => $this->users['manajer']->id, 'verified_at' => $this->today->subDays($this->rand(20, 150)),
                'safety_impact' => $impacts[0], 'guest_impact' => $impacts[1], 'revenue_impact' => $impacts[2],
                'operational_impact' => $impacts[3], 'replacement_difficulty' => $impacts[4],
                'status' => match ($profile) { 'critical' => AssetStatus::Repair, 'poor' => AssetStatus::Monitor, default => AssetStatus::Operational },
            ]);
            $this->applyProfile($asset, $profile);
            $asset->save();
        }
    }

    /** Profil kondisi → komponen skor kesehatan yang wajar. */
    protected function applyProfile(Asset $asset, string $profile): void
    {
        [$lo, $hi] = match ($profile) {
            'excellent' => [88, 99], 'good' => [74, 90], 'fair' => [58, 76], 'poor' => [38, 60], default => [15, 42],
        };

        foreach (['physical_condition', 'maintenance_compliance', 'breakdown_reliability', 'remaining_life_score', 'safety_condition', 'operational_reliability', 'documentation_score'] as $field) {
            $asset->{$field} = $this->rand($lo, $hi);
        }
    }

    protected function randomizeHealth(Asset $asset, bool $poor = false): void
    {
        $roll = $this->rand(1, 100);
        $this->applyProfile($asset, match (true) {
            $poor => 'poor',
            $roll <= 18 => 'excellent',
            $roll <= 70 => 'good',
            $roll <= 90 => 'fair',
            $roll <= 98 => 'poor',
            default => 'critical',
        });

        foreach (['safety_impact', 'guest_impact', 'revenue_impact', 'operational_impact', 'replacement_difficulty'] as $field) {
            $asset->{$field} = $this->rand(1, 3);
        }
    }

    /** Nilai perolehan aset templat, dalam rupiah, dari rentang juta per kategori di profil. */
    protected function costFor(int $categoryId): int
    {
        $code = array_search($categoryId, $this->cat, true);
        [$lo, $hi] = $this->p['unit_costs'][$code] ?? [2, 10];

        return $this->rand($lo, $hi) * 1_000_000;
    }

    protected function maintenance(): void
    {
        $assets = Asset::query()->get();
        $major = $assets->where('identification_source', IdentificationSource::Manual)->values();
        $templated = $assets->where('identification_source', IdentificationSource::Template)->values();
        $tech = [$this->users['teknisi'], $this->users['manajer'], $this->users['supervisor']];
        $scale = $this->p['cost_scale'] ?? 1;

        $plans = collect();
        foreach ($major as $i => $asset) {
            $code = array_search($asset->asset_category_id, $this->cat, true);
            $plans->push(PreventivePlan::create([
                'name' => 'PM '.$asset->name,
                'asset_id' => $asset->id,
                'frequency' => $this->p['pm_frequency'][$code] ?? 'quarterly',
                'next_due_date' => $i % 6 === 0 ? $this->today->subDays($this->rand(3, 40)) : $this->today->addDays($this->rand(1, 60)),
                'responsible_id' => $this->pick($tech)->id,
                'checklist' => "1. Periksa kondisi fisik dan kebersihan\n2. Ukur parameter operasi (arus, tekanan, suhu)\n3. Pelumasan dan pengencangan\n4. Uji fungsi dan catat temuan",
            ]));
        }

        // Work order 12 bulan; korektif & darurat sedikit menurun di bulan terakhir (efek PM yang lebih disiplin).
        [$pLo, $pHi] = $this->p['wo']['preventive'];
        [$cLo, $cHi] = $this->p['wo']['corrective'];
        [$eLo, $eHi] = $this->p['wo']['emergency'];

        for ($m = 11; $m >= 0; $m--) {
            $monthStart = $this->today->startOfMonth()->subMonths($m);
            $improve = $m <= 3 ? 1 : 0;
            $counts = [
                'preventive' => $this->rand($pLo, $pHi) + $improve,
                'corrective' => max(1, $this->rand($cLo, $cHi) - $improve),
                'emergency' => max(0, $this->rand($eLo, $eHi) - $improve),
            ];

            foreach ($counts as $type => $count) {
                for ($n = 0; $n < $count; $n++) {
                    $planned = $monthStart->addDays($this->rand(0, $monthStart->daysInMonth - 1))->setTime($this->rand(7, 17), 0);
                    if ($planned->greaterThan($this->today->endOfDay())) {
                        $planned = $this->today->setTime(9, 0);
                    }

                    $asset = $type === 'preventive' || $this->rand(1, 3) > 1 ? $major->random() : $assets->random();
                    $isPast = $planned->lessThan($this->today->subDays(3));
                    $state = $isPast ? $this->pick(['closed', 'closed', 'closed', 'closed', 'closed', 'closed', 'verified', 'done']) : $this->pick(['draft', 'assigned', 'progress', 'waiting']);
                    $done = in_array($state, ['done', 'verified', 'closed'], true);
                    $spare = ($type === 'preventive' ? $this->rand(0, 15) : $this->rand(5, 120)) * 100_000 * $scale;
                    $vendor = $type === 'emergency' || ($type === 'corrective' && $this->rand(1, 4) === 1) ? $this->rand(10, 250) * 100_000 * $scale : 0;

                    WorkOrder::create([
                        'number' => Sequence::next('work_order', $this->company->id, $planned->year),
                        'asset_id' => $asset->id,
                        'preventive_plan_id' => $type === 'preventive' ? $plans->firstWhere('asset_id', $asset->id)?->id : null,
                        'work_type' => $type,
                        'priority' => $type === 'emergency' ? 'urgent' : $this->pick(['low', 'medium', 'medium', 'high']),
                        'state' => $state,
                        'technician_id' => $this->pick($tech)->id,
                        'planned_at' => $planned,
                        'started_at' => $state !== 'draft' && $state !== 'assigned' ? $planned->addHours(1) : null,
                        'completed_at' => $done ? $planned->addHours($this->rand(2, 30)) : null,
                        'description' => $type === 'preventive' ? 'Pemeliharaan preventif terjadwal' : $this->pick($this->p['corrective']),
                        'root_cause' => $done && $type !== 'preventive' ? $this->pick(['Keausan komponen', 'Kurang pelumasan', 'Fluktuasi tegangan', 'Usia pakai', 'Kesalahan operasi']) : null,
                        'action_taken' => $done ? ($type === 'preventive' ? 'Checklist PM selesai, semua parameter normal' : 'Penggantian komponen dan uji fungsi') : null,
                        'labor_hours' => $done ? $this->rand(1, 12) : 0,
                        'downtime_hours' => $type === 'emergency' ? $this->rand(2, 24) : ($type === 'corrective' ? $this->rand(0, 6) : 0),
                        'sparepart_cost' => $done ? $spare : 0,
                        'vendor_cost' => $done ? $vendor : 0,
                        'other_cost' => $done ? $this->rand(0, 5) * 50_000 : 0,
                    ]);
                }
            }
        }

        [$meterCat, $meterUnit] = $this->p['meter'] ?? [null, null];
        for ($i = 0; $i < 70; $i++) {
            $asset = $this->rand(1, 3) > 1 ? $major->random() : $assets->random();
            $score = max(10, min(100, (int) round($asset->physical_condition + $this->rand(-8, 6))));
            $metered = $meterCat && $asset->asset_category_id === ($this->cat[$meterCat] ?? null);
            Inspection::create([
                'asset_id' => $asset->id,
                'inspected_at' => $this->today->subDays($this->rand(0, 180))->setTime($this->rand(7, 18), $this->rand(0, 59)),
                'inspector_id' => $this->pick($tech)->id,
                'result' => $score >= 70 ? 'pass' : ($score >= 50 ? 'monitor' : 'fail'),
                'condition_score' => $score,
                'finding' => $score >= 70 ? 'Kondisi normal.' : $this->pick(['Getaran berlebih pada bearing', 'Korosi pada rangka', 'Arus motor di atas nominal', 'Rembesan oli', 'Isolasi kabel getas', 'Suhu kerja di atas batas']),
                'recommendation' => $score >= 70 ? null : $this->pick(['Jadwalkan penggantian bearing', 'Lakukan overhaul', 'Pantau mingguan', 'Ajukan CAPEX penggantian']),
                'meter_reading' => $metered ? $this->rand(8000, 26000) : null,
                'meter_unit' => $metered ? $meterUnit : null,
            ]);
        }

        // Permintaan pemeliharaan, sebagian dari pindai QR di lapangan.
        $pool = $templated->isNotEmpty() ? $templated : $assets;
        for ($i = 0; $i < 36; $i++) {
            $viaQr = $this->rand(1, 100) <= 45;
            $requestedAt = $this->today->subDays($this->rand(0, 90))->setTime($this->rand(6, 22), $this->rand(0, 59));
            $state = $requestedAt->lessThan($this->today->subDays(10)) ? $this->pick(['converted', 'converted', 'cancel']) : $this->pick(['new', 'new', 'verified', 'converted']);
            $request = MaintenanceRequest::create([
                'number' => Sequence::next('request', $this->company->id, $requestedAt->year),
                'asset_id' => $pool->random()->id,
                'requested_at' => $requestedAt,
                'reporter_id' => $viaQr ? null : $this->users['supervisor']->id,
                'reporter_name' => $viaQr ? $this->pick($this->p['reporters']) : null,
                'reporter_contact' => $viaQr ? $this->pick(['0812-xxxx-1234', '0857-xxxx-8810', null]) : null,
                'via_qr' => $viaQr,
                'request_type' => $this->rand(1, 10) === 1 ? 'emergency' : 'corrective',
                'priority' => $this->pick(['low', 'medium', 'medium', 'high', 'urgent']),
                'description' => ($viaQr ? '[Laporan QR] ' : '').$this->pick($this->p['complaints']),
                'state' => $state === 'converted' ? 'verified' : $state,
            ]);

            if ($state === 'converted') {
                $wo = $request->createWorkOrder();
                $wo->forceFill(['planned_at' => $requestedAt->addHours(2)])->save();

                if ($requestedAt->lessThan($this->today->subDays(10))) {
                    $wo->forceFill(['state' => 'closed', 'started_at' => $requestedAt->addHours(3), 'completed_at' => $requestedAt->addHours(6), 'sparepart_cost' => $this->rand(1, 20) * 50_000, 'action_taken' => 'Perbaikan selesai, unit berfungsi normal'])->save();
                }
            }
        }
    }

    protected function risksAndCapex(): void
    {
        $major = Asset::query()->where('identification_source', IdentificationSource::Manual)->get();
        $assetId = fn (?string $name) => $name ? $major->firstWhere('name', $name)?->id : null;

        foreach ($this->p['risks'] as [$name, $assetName, $p, $c, $state, $mitigation]) {
            AssetRisk::create([
                'name' => $name, 'asset_id' => $assetId($assetName), 'probability' => $p, 'consequence' => $c,
                'state' => $state, 'mitigation' => $mitigation, 'owner_id' => $this->users['manajer']->id,
                'target_date' => $this->today->addDays($this->rand(-20, 120)),
            ]);
        }

        foreach ($this->p['capex'] as [$name, $assetName, $category, $recommendation, $cost, [$guest, $safety, $revenue, $urgency], $state, $justification]) {
            CapexProposal::create([
                'name' => $name, 'asset_id' => $assetId($assetName),
                'category' => $category, 'recommendation' => $recommendation, 'target_year' => $this->today->year + ($state === 'draft' ? 1 : 0),
                'estimated_cost' => $cost * 1_000_000, 'guest_impact' => $guest, 'safety_impact' => $safety, 'revenue_impact' => $revenue,
                'urgency' => $urgency, 'state' => $state, 'justification' => $justification,
            ]);
        }
    }

    protected function rooms(): void
    {
        $rooms = collect($this->p['rooms'])->map(fn (array $r) => MeetingRoom::create(['name' => $r[0], 'capacity' => $r[1], 'facilities' => $r[2], 'location_id' => isset($r[3]) ? $this->loc[$r[3]]->id : null]));
        $organizers = array_values($this->users);
        $slots = [[8, 0, 9, 0], [9, 30, 11, 0], [13, 0, 14, 30], [15, 0, 16, 30]];

        for ($offset = -30; $offset <= 12; $offset++) {
            $day = $this->today->addDays($offset);

            if ($day->isWeekend()) {
                continue;
            }

            foreach ($rooms as $room) {
                foreach ($slots as [$h1, $m1, $h2, $m2]) {
                    if ($this->rand(1, 100) > ($room->capacity >= 20 ? 48 : 38)) {
                        continue;
                    }

                    [$title, $dept] = $this->pick($this->p['agendas']);
                    $state = match (true) {
                        $offset < 0 => $this->pick(['approved', 'approved', 'approved', 'approved', 'approved', 'cancelled']),
                        $offset <= 1 => 'approved',
                        default => $this->pick(['approved', 'approved', 'approved', 'approved', 'approved', 'requested']),
                    };

                    RoomBooking::create([
                        'meeting_room_id' => $room->id,
                        'title' => $title,
                        'organizer_id' => $this->pick($organizers)->id,
                        'department_id' => $this->dept($dept),
                        'starts_at' => $day->setTime($h1, $m1),
                        'ends_at' => $day->setTime($h2, $m2),
                        'attendees' => max(2, (int) round($room->capacity * $this->rand(30, 90) / 100)),
                        'needs' => $this->pick([null, 'Coffee break pagi', 'Snack dan air mineral', 'Makan siang prasmanan', 'Proyektor dan pointer', 'Sambungan video conference']),
                        'state' => $state,
                        'approved_by' => $state === 'approved' ? $this->users['manajer']->id : null,
                    ]);
                }
            }
        }

        $small = $rooms->sortBy('capacity')->first();
        RoomBooking::create([
            'meeting_room_id' => $small->id, 'title' => 'Rapat evaluasi anggaran akhir tahun', 'organizer_id' => $this->users['supervisor']->id,
            'department_id' => $this->dept(array_key_first($this->p['departments'])), 'starts_at' => $this->today->addDays(15)->setTime(9, 0), 'ends_at' => $this->today->addDays(15)->setTime(11, 0),
            'attendees' => $small->capacity + 6, 'state' => 'rejected', 'approved_by' => $this->users['manajer']->id,
            'note' => 'Jumlah peserta melebihi kapasitas, silakan pesan ruang yang lebih besar.',
        ]);
    }

    protected function vehicles(): void
    {
        $vehicles = collect($this->p['vehicles'])->map(fn (array $v) => Vehicle::create([
            'plate_number' => $v[0], 'name' => $v[1], 'type' => $v[2], 'year' => $v[3], 'fuel_type' => $v[4], 'seats' => $v[5], 'odometer' => $v[6],
            'default_driver' => $v[7], 'tax_due_date' => $this->day($v[8]), 'stnk_due_date' => $this->day($v[9]), 'kir_due_date' => $this->day($v[10]),
            'insurance_due_date' => $this->day($v[11]), 'next_service_date' => $this->day($v[12]), 'next_service_km' => $v[13],
            'status' => $v[14] ?? 'available',
        ]));

        $prices = ['Dexlite' => 13_250, 'Pertamax' => 12_500, 'Pertalite' => 10_000, 'Solar' => 6_800, 'Pertamina Dex' => 13_900, 'Listrik' => 2_467];
        $tank = ['minibus' => [45, 60], 'car' => [30, 45], 'pickup' => [40, 70], 'motorcycle' => [3, 5]];

        foreach ($vehicles as $vehicle) {
            $electric = $vehicle->fuel_type === 'Listrik';
            // Pengisian BBM/listrik 12 bulan; odometer naik sampai angka sekarang.
            $km = $vehicle->odometer - $this->rand(9_000, 14_000);
            for ($date = $this->today->subMonths(12)->addDays($this->rand(0, 5)); $date->lt($this->today); $date = $date->addDays($this->rand(6, 12))) {
                $liters = $electric ? $this->rand(35, 60) + $this->rand(0, 9) / 10 : $this->rand(...$tank[$vehicle->type->value]) + $this->rand(0, 9) / 10;
                $km += $this->rand(180, 520);
                VehicleFuelLog::create([
                    'vehicle_id' => $vehicle->id, 'filled_at' => $date, 'odometer' => min($km, $vehicle->odometer), 'liters' => $liters,
                    'amount' => round($liters * ($prices[$vehicle->fuel_type] ?? 12_000)), 'station' => $this->pick($electric ? $this->p['chargers'] ?? $this->p['stations'] : $this->p['stations']),
                    'recorded_by' => $this->users['umum']->id,
                ]);
            }

            foreach ([330, 240, 160, 60] as $i => $daysAgo) {
                if ($vehicle->type->value === 'motorcycle' && $i % 2 === 0) {
                    continue;
                }
                $type = match (true) {
                    $i === 1 && $vehicle->type->value !== 'motorcycle' => 'tires',
                    $i === 2 && $this->rand(1, 3) === 1 => 'repair',
                    default => 'routine',
                };
                VehicleService::create([
                    'vehicle_id' => $vehicle->id, 'service_date' => $this->today->subDays($daysAgo + $this->rand(0, 10)),
                    'odometer' => $vehicle->odometer - (int) ($daysAgo * $this->rand(25, 40)),
                    'service_type' => $type,
                    'workshop' => $this->p['workshops'][$vehicle->type->value] ?? $this->p['workshops']['default'],
                    'description' => match ($type) {
                        'tires' => 'Ganti 2 ban depan dan spooring balancing',
                        'repair' => 'Perbaikan sistem rem dan ganti kampas',
                        default => $electric ? 'Servis berkala kendaraan listrik: cek baterai, rem, dan pembaruan perangkat lunak' : 'Servis berkala: ganti oli mesin, filter oli, cek rem dan kaki-kaki',
                    },
                    'cost' => $vehicle->type->value === 'motorcycle' ? $this->rand(9, 18) * 10_000 : $this->rand(9, 32) * 100_000 * ($type === 'repair' ? 2 : 1),
                ]);
            }
        }

        $requesters = array_values($this->users);
        $cars = $vehicles->filter(fn (Vehicle $v) => $v->type->value !== 'motorcycle' && $v->status->value === 'available')->values();

        for ($offset = -45; $offset <= 6; $offset += $this->rand(1, 2)) {
            [$purpose, $destination, $dept, $pax] = $this->pick($this->p['trips']);
            $vehicle = $cars[$this->rand(0, $cars->count() - 1)];
            $start = $this->today->addDays($offset)->setTime($this->pick([7, 8, 9, 10, 13]), 0);
            $end = $start->addHours($this->rand(2, 6));
            $state = match (true) {
                $offset < 0 => $this->pick(['returned', 'returned', 'returned', 'returned', 'cancelled']),
                $offset === 0 => 'in_use',
                $offset <= 2 => 'approved',
                default => 'requested',
            };

            if (VehicleBooking::conflictFor($vehicle->id, $start, $end)) {
                continue;
            }

            $distance = $state === 'returned' ? $this->rand(18, 140) : null;
            $odometerStart = in_array($state, ['returned', 'in_use'], true) ? $vehicle->odometer - $this->rand(400, 2500) : null;

            VehicleBooking::create([
                'vehicle_id' => $state === 'requested' ? null : $vehicle->id,
                'requester_id' => $this->pick($requesters)->id,
                'department_id' => $this->dept($dept),
                'purpose' => $purpose, 'destination' => $destination, 'passengers' => $pax,
                'starts_at' => $start, 'ends_at' => $end,
                'driver_name' => $state === 'requested' ? null : ($vehicle->default_driver ?? 'Pengemudi pool'),
                'state' => $state,
                'odometer_start' => $odometerStart,
                'odometer_end' => $distance ? $odometerStart + $distance : null,
                'distance_km' => $distance,
                'approved_by' => in_array($state, ['returned', 'in_use', 'approved'], true) ? $this->users['manajer']->id : null,
            ]);
        }
    }

    protected function supplies(): void
    {
        $this->supplies = collect();
        $ga = $this->users['umum']->id;

        // kode, nama, kategori, satuan, stok awal, minimum, harga, pemakaian per bulan
        foreach ($this->p['supplies'] as [$code, $name, $category, $unit, $opening, $min, $cost, $monthly]) {
            $item = SupplyItem::create([
                'code' => $code, 'name' => $name, 'category' => $category, 'unit' => $unit, 'min_stock' => $min, 'unit_cost' => $cost,
                'storage_location' => $category === 'pantry' ? $this->p['storage']['pantry'] : $this->p['storage']['default'],
            ]);
            $item->move(StockMovementType::In, $opening * 3, [
                'moved_at' => $this->today->subMonths(6)->startOfMonth(), 'unit_cost' => $cost, 'reference' => 'Stok awal', 'user_id' => $ga,
            ]);
            $this->supplies->put($code, [$item, $monthly]);
        }

        // Pemakaian mingguan 6 bulan dengan pembelian ulang saat stok menipis.
        foreach ($this->supplies as [$item, $monthly]) {
            for ($week = 26; $week >= 1; $week--) {
                $qty = max(1, (int) round($monthly / 4 * $this->rand(70, 150) / 100));
                $stock = (float) $item->fresh()->stock;

                if ($stock - $qty < $item->min_stock && $week > 2) {
                    $item->move(StockMovementType::In, $monthly * 2, [
                        'moved_at' => $this->today->subWeeks($week), 'unit_cost' => $item->unit_cost,
                        'reference' => 'PO/GA/'.$this->today->subWeeks($week)->format('Y/m').'/'.str_pad((string) $this->rand(10, 99), 3, '0', STR_PAD_LEFT),
                        'user_id' => $ga,
                    ]);
                    $stock += $monthly * 2;
                }

                if ($stock - $qty < 0) {
                    continue;
                }

                $item->move(StockMovementType::Out, $qty, [
                    'moved_at' => $this->today->subWeeks($week)->addDays($this->rand(0, 4)),
                    'reference' => $this->pick($this->p['supply_users']),
                    'user_id' => $ga,
                ]);
            }
        }

        foreach ($this->p['supply_requests'] as [$dept, $purpose, $lines, $state, $days]) {
            $request = SupplyRequest::create([
                'requester_id' => $this->pick(array_values($this->users))->id,
                'department_id' => $this->dept($dept),
                'requested_at' => $this->today->addDays($days)->setTime($this->rand(8, 15), 0),
                'needed_by' => $this->today->addDays($days + 2),
                'purpose' => $purpose,
            ]);
            foreach ($lines as [$code, $qty]) {
                [$item, $monthly] = $this->supplies[$code];
                $request->lines()->create(['supply_item_id' => $item->id, 'quantity' => $qty]);

                // Barang datang dari pembelian sebelum permintaan diserahkan.
                if ($state === 'issued' && $item->fresh()->stock < $qty + $item->min_stock) {
                    $item->move(StockMovementType::In, $qty + $monthly, [
                        'moved_at' => $this->today->addDays($days - 1), 'unit_cost' => $item->unit_cost, 'reference' => 'PO/GA/'.$this->today->format('Y/m').'/'.$this->rand(100, 199), 'user_id' => $ga,
                    ]);
                }
            }

            match ($state) {
                'issued' => $request->issue(),
                'approved' => $request->approve(),
                'rejected' => $request->reject('Gunakan stok yang masih ada di unit kerja.'),
                default => null,
            };

            $request->update(['approved_by' => $state === 'submitted' ? null : $this->users['manajer']->id]);
        }

        // Beberapa barang sengaja menipis supaya terlihat di dasbor.
        foreach ($this->p['low_stock'] as $code => $target) {
            $item = $this->supplies[$code][0]->fresh();
            if ($item->stock > $target) {
                $item->move(StockMovementType::Out, $item->stock - $target, [
                    'moved_at' => $this->today->subDays(2), 'reference' => $this->pick($this->p['supply_users']), 'user_id' => $ga,
                ]);
            }
        }
    }

    protected function documents(): void
    {
        // nama, jenis, nomor, penerbit, terbit (hari), berakhir (hari), pengingat, [plat kendaraan]
        foreach ($this->p['documents'] as $row) {
            [$name, $type, $number, $issuer, $issued, $expiry, $reminder] = $row;
            $expiryDate = $this->day($expiry);
            LegalDocument::create([
                'name' => $name, 'document_type' => $type, 'number' => $number, 'issuer' => $issuer,
                'issued_date' => $this->day($issued), 'expiry_date' => $expiryDate, 'reminder_days' => $reminder,
                'responsible_id' => $this->pick([$this->users['umum'], $this->users['manajer']])->id,
                'vehicle_id' => isset($row[7]) ? Vehicle::where('plate_number', $row[7])->value('id') : null,
                'notes' => $expiryDate && $expiryDate->lt($this->today) ? 'Perpanjangan sedang diproses, menunggu jadwal pemeriksaan.' : null,
            ]);
        }
    }

    /** Daftar ATK & kebutuhan kantor umum; profil boleh menambah barang khas industrinya. */
    protected function officeSupplies(array $extra = []): array
    {
        return array_merge([
            ['ATK-001', 'Kertas HVS A4 70 gsm', 'stationery', 'rim', 60, 20, 52_000, 18],
            ['ATK-002', 'Kertas HVS F4 70 gsm', 'stationery', 'rim', 25, 10, 58_000, 6],
            ['ATK-003', 'Pulpen gel hitam', 'stationery', 'pcs', 120, 40, 4_500, 30],
            ['ATK-004', 'Spidol whiteboard', 'stationery', 'pcs', 36, 12, 9_000, 10],
            ['ATK-005', 'Map plastik kancing', 'stationery', 'pcs', 80, 30, 3_500, 25],
            ['ATK-006', 'Ordner / binder besar', 'stationery', 'pcs', 24, 10, 28_000, 4],
            ['ATK-007', 'Amplop cokelat folio', 'stationery', 'pak', 15, 5, 35_000, 3],
            ['ATK-008', 'Sticky notes 3x3', 'stationery', 'pak', 30, 10, 12_000, 9],
            ['CTK-001', 'Toner HP 85A', 'printing', 'pcs', 8, 4, 650_000, 3],
            ['CTK-002', 'Tinta Epson 003 hitam', 'printing', 'botol', 10, 4, 85_000, 3],
            ['KBR-001', 'Sabun cuci tangan 5 L', 'cleaning', 'galon', 12, 4, 95_000, 4],
            ['KBR-002', 'Tisu toilet jumbo roll', 'cleaning', 'roll', 90, 36, 22_000, 30],
            ['KBR-003', 'Kantong sampah hitam besar', 'cleaning', 'pak', 40, 15, 18_000, 12],
            ['KBR-004', 'Pembersih lantai 5 L', 'cleaning', 'galon', 10, 4, 120_000, 3],
            ['PTR-001', 'Kopi bubuk 1 kg', 'pantry', 'pak', 12, 4, 145_000, 4],
            ['PTR-002', 'Teh celup isi 50', 'pantry', 'box', 20, 6, 28_000, 6],
            ['PTR-003', 'Gula pasir 1 kg', 'pantry', 'kg', 25, 8, 18_500, 8],
            ['PTR-004', 'Air mineral galon', 'pantry', 'galon', 30, 10, 21_000, 22],
        ], $extra);
    }
}
