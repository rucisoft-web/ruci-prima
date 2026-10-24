<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssignmentState;
use App\Enums\IdentificationSource;
use App\Enums\Role;
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
use App\Models\Location;
use App\Models\MaintenanceRequest;
use App\Models\PreventivePlan;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AssetIntake;
use App\Support\CurrentCompany;
use App\Support\Sequence;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh: Grand Pasundan Convention Hotel (GPCH), Bandung.
 * Fiktif tetapi realistis, dibangkitkan deterministik (seed acak tetap) agar hasilnya sama di setiap instalasi.
 */
class DemoSeeder extends Seeder
{
    protected Company $company;

    /** @var array<string, int> */
    protected array $dept = [];

    /** @var array<string, int> */
    protected array $cat = [];

    /** @var array<string, Location> */
    protected array $loc = [];

    /** @var array<string, User> */
    protected array $users = [];

    protected CarbonImmutable $today;

    public function run(): void
    {
        mt_srand(2026);
        $this->today = CarbonImmutable::today();

        if (Company::where('slug', 'gpch')->exists()) {
            $this->command?->warn('Data contoh GPCH sudah ada. Lewati.');

            return;
        }

        AuditLog::$enabled = false;

        $this->company = Company::create([
            'name' => 'Grand Pasundan Convention Hotel',
            'code' => 'GPCH',
            'slug' => 'gpch',
            'industry' => 'Perhotelan & MICE',
            'city' => 'Bandung',
            'address' => 'Jl. Peta No. 147–149, Bandung, Jawa Barat (data contoh)',
            'is_demo' => true,
        ]);

        app(CurrentCompany::class)->runAs($this->company, function () {
            DB::transaction(function () {
                $this->users();
                $this->masterData();
                $this->locations();
                $this->templates();
                $this->assets();
                $this->operations();
            });
        });

        AuditLog::$enabled = true;
    }

    protected function rand(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    protected function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    protected function users(): void
    {
        $password = config('asset.demo.password');
        $accounts = [
            'manajer' => ['Dedi Kurniawan', 'manajer@gpch.demo', Role::Manajer, 'Chief Engineer'],
            'teknisi' => ['Asep Saepudin', 'teknisi@gpch.demo', Role::Staf, 'Teknisi HVAC & Listrik'],
            'teknisi2' => ['Rina Marlina', 'supervisor@gpch.demo', Role::Staf, 'Supervisor Housekeeping'],
            'gm' => ['Hendra Wijaya', 'gm@gpch.demo', Role::Eksekutif, 'General Manager'],
        ];

        foreach ($accounts as $key => [$name, $email, $role, $title]) {
            $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password, 'is_active' => true]);
            $user->memberships()->updateOrCreate(['company_id' => $this->company->id], ['role' => $role, 'position_title' => $title, 'is_active' => true]);
            $this->users[$key] = $user;
        }
    }

    protected function masterData(): void
    {
        $departments = [
            'rooms' => 'Rooms Division', 'engineering' => 'Engineering', 'fnb' => 'Food & Beverage', 'convention' => 'Convention & Banquet',
            'housekeeping' => 'Housekeeping', 'front_office' => 'Front Office', 'security' => 'Security', 'it' => 'IT', 'admin' => 'Administrasi',
        ];

        $i = 0;
        foreach ($departments as $code => $name) {
            $this->dept[$code] = Department::create(['name' => $name, 'code' => strtoupper($code), 'sort' => ++$i * 10])->id;
        }

        $categories = [
            'hvac' => ['HVAC & Tata Udara', 15, 'high'],
            'elec' => ['Kelistrikan & Genset', 20, 'critical'],
            'lift' => ['Lift & Eskalator', 25, 'critical'],
            'plmb' => ['Plumbing, Pompa & STP', 15, 'high'],
            'fire' => ['Proteksi Kebakaran', 15, 'critical'],
            'kitchen' => ['Peralatan Dapur', 10, 'high'],
            'room' => ['Peralatan Kamar', 7, 'medium'],
            'furn' => ['Furnitur & Interior', 8, 'low'],
            'av' => ['Audio Visual', 7, 'medium'],
            'it' => ['TI & Jaringan', 5, 'medium'],
            'laundry' => ['Mesin Laundry', 12, 'medium'],
            'pool' => ['Kolam & Rekreasi', 10, 'medium'],
            'sec' => ['Keamanan & CCTV', 7, 'medium'],
        ];

        foreach ($categories as $code => [$name, $life, $crit]) {
            $this->cat[$code] = AssetCategory::create([
                'name' => $name, 'code' => strtoupper($code), 'expected_life_years' => $life, 'default_criticality' => $crit,
            ])->id;
        }
    }

    protected function locations(): void
    {
        $make = fn (string $key, string $name, ?string $parent = null) => $this->loc[$key] = Location::create([
            'name' => $name, 'parent_id' => $parent ? $this->loc[$parent]->id : null,
        ]);

        $make('tower', 'Gedung Utama');
        $make('lobby', 'Lantai 1 · Lobby', 'tower');
        $make('fo', 'Front Office', 'lobby');
        $make('lounge', 'Lobby Lounge', 'lobby');

        foreach (range(2, 6) as $floor) {
            $make("f{$floor}", "Lantai {$floor} · Kamar", 'tower');

            foreach (range(1, 6) as $n) {
                $make("r{$floor}0{$n}", 'Kamar '.$floor.'0'.$n.($n <= 2 ? ' (Suite)' : ''), "f{$floor}");
            }
        }

        $make('cc', 'Convention Center');
        $make('ballroom', 'Grand Ballroom Pasundan', 'cc');
        $make('prefunction', 'Pre-function Area', 'cc');
        foreach (range(1, 3) as $n) {
            $make("mr{$n}", "Ruang Rapat Parahyangan {$n}", 'cc');
        }

        $make('fnb', 'Food & Beverage');
        $make('kitchen', 'Main Kitchen', 'fnb');
        $make('resto', 'All-day Dining Saung Priangan', 'fnb');

        $make('boh', 'Back of House');
        $make('laundry', 'Laundry', 'boh');
        $make('workshop', 'Engineering Workshop', 'boh');
        $make('server', 'Ruang Server', 'boh');

        $make('plant', 'Plant & Utilitas');
        $make('chiller', 'Chiller Plant', 'plant');
        $make('genset', 'Ruang Genset', 'plant');
        $make('panel', 'Ruang Panel LVMDP', 'plant');
        $make('pump', 'Ruang Pompa & STP', 'plant');
        $make('liftroom', 'Lift Machine Room', 'plant');
        $make('roof', 'Rooftop', 'plant');

        $make('public', 'Area Publik');
        $make('pool', 'Kolam Renang', 'public');
        $make('parking', 'Parkir Basement', 'public');
    }

    protected function templates(): void
    {
        $room = AssetTemplate::create(['name' => 'Kamar Deluxe / Suite', 'location_type' => 'room', 'note' => 'Standar perlengkapan kamar tamu.']);
        $lines = [
            ['AC Split 1,5 PK', 'hvac', 1, 'engineering', 'Daikin', 'FTKC35'],
            ['Smart TV 43"', 'room', 1, 'rooms', 'Samsung', 'HG43'],
            ['Minibar', 'room', 1, 'rooms', 'Dometic', 'RH439'],
            ['Brankas kamar', 'room', 1, 'rooms', 'Onity', 'ES1000'],
            ['Water heater', 'plmb', 1, 'engineering', 'Ariston', 'Andris 30L'],
            ['Tempat tidur & kasur', 'furn', 1, 'housekeeping', 'Serta', 'Hotel Series'],
            ['Smoke detector', 'fire', 1, 'security', 'Hochiki', 'SLR-E3N'],
            ['Kunci pintu RFID', 'sec', 1, 'front_office', 'Salto', 'XS4'],
        ];

        foreach ($lines as $i => [$name, $cat, $qty, $dept, $brand, $model]) {
            $room->lines()->create([
                'sort' => ($i + 1) * 10, 'name' => $name, 'asset_category_id' => $this->cat[$cat], 'quantity' => $qty,
                'department_id' => $this->dept[$dept], 'brand' => $brand, 'model' => $model,
                'expected_life_years' => AssetCategory::find($this->cat[$cat])->expected_life_years,
            ]);
        }

        $meeting = AssetTemplate::create(['name' => 'Ruang Rapat', 'location_type' => 'meeting']);
        foreach ([['Proyektor', 'av', 1, 'Epson', 'EB-L630U'], ['Sound system', 'av', 1, 'Bose', 'ControlSpace'], ['AC Cassette 3 PK', 'hvac', 2, 'Daikin', 'FCNQ26'], ['Smoke detector', 'fire', 2, 'Hochiki', 'SLR-E3N']] as $i => [$name, $cat, $qty, $brand, $model]) {
            $meeting->lines()->create([
                'sort' => ($i + 1) * 10, 'name' => $name, 'asset_category_id' => $this->cat[$cat], 'quantity' => $qty,
                'department_id' => $this->dept[$cat === 'av' ? 'convention' : 'engineering'], 'brand' => $brand, 'model' => $model,
                'expected_life_years' => AssetCategory::find($this->cat[$cat])->expected_life_years,
            ]);
        }
    }

    protected function assets(): void
    {
        $survey = AssetSurvey::create([
            'name' => 'Survei Aset Awal 2026', 'date_start' => $this->today->subMonths(5)->startOfMonth(), 'state' => SurveyState::InProgress,
            'manager_id' => $this->users['manajer']->id, 'note' => 'Inventarisasi awal seluruh kamar dan ruang rapat.',
        ]);

        // Kamar: dibangkitkan dari templat, lalu diverifikasi sebagian besar oleh tim lapangan.
        $roomKeys = collect($this->loc)->keys()->filter(fn ($k) => preg_match('/^r\d{3}$/', $k))->values();
        $intake = app(AssetIntake::class);
        $roomTemplate = AssetTemplate::where('location_type', 'room')->first();
        $meetingTemplate = AssetTemplate::where('location_type', 'meeting')->first();

        $intake->fromTemplate($roomTemplate, $roomKeys->map(fn ($k) => $this->loc[$k]->id)->all(), $survey->id);
        $intake->fromTemplate($meetingTemplate, [$this->loc['mr1']->id, $this->loc['mr2']->id, $this->loc['mr3']->id], $survey->id);

        foreach (range(2, 6) as $floor) {
            $assignment = $survey->assignments()->create([
                'location_id' => $this->loc["f{$floor}"]->id, 'team_name' => "Tim Lantai {$floor}",
                'asset_template_id' => $roomTemplate->id,
                'state' => $floor <= 4 ? AssignmentState::Done : ($floor === 5 ? AssignmentState::InProgress : AssignmentState::Assigned),
            ]);
            $assignment->surveyors()->sync([$this->users['teknisi']->id, $this->users['teknisi2']->id]);
        }

        $survey->assignments()->create([
            'location_id' => $this->loc['cc']->id, 'team_name' => 'Tim Convention', 'asset_template_id' => $meetingTemplate->id, 'state' => AssignmentState::InProgress,
        ])->surveyors()->sync([$this->users['teknisi']->id]);

        // Assignment per lantai, tetapi aset ada di lokasi kamar: tautkan dengan lokasi kamar juga untuk rekap.
        $verifier = $this->users['teknisi'];

        Asset::where('asset_survey_id', $survey->id)->with('location')->get()->each(function (Asset $asset) use ($verifier) {
            $floor = (int) substr(preg_replace('/\D/', '', $asset->location->name), 0, 1);
            $roll = $this->rand(1, 100);

            $status = match (true) {
                $floor >= 6 => VerificationStatus::Expected,
                $floor === 5 && $roll > 55 => VerificationStatus::Expected,
                $roll <= 4 => VerificationStatus::Missing,
                default => VerificationStatus::Verified,
            };

            $this->randomizeHealth($asset, $status === VerificationStatus::Missing ? 30 : null);
            $asset->fill([
                'verification_status' => $status,
                'verified_by_id' => $status === VerificationStatus::Expected ? null : $verifier->id,
                'verified_at' => $status === VerificationStatus::Expected ? null : $this->today->subDays($this->rand(5, 120))->setTime($this->rand(8, 16), $this->rand(0, 59)),
                'purchase_date' => $this->today->subYears($this->rand(2, 9))->subDays($this->rand(0, 300)),
                'serial_no' => $status === VerificationStatus::Verified ? strtoupper(substr(md5((string) $asset->id), 0, 10)) : null,
                'acquisition_cost' => $this->costFor($asset->asset_category_id),
                'status' => $status === VerificationStatus::Missing ? AssetStatus::OutOfService : AssetStatus::Operational,
                'verification_note' => $status === VerificationStatus::Missing ? 'Tidak ditemukan saat survei; diduga dipindah ke gudang.' : null,
            ]);
            $asset->replacement_cost = round($asset->acquisition_cost * 1.25, -3);
            $asset->save();
        });

        // Aset tambahan yang ditemukan di lapangan.
        foreach ([['r301', 'Kulkas tambahan (permintaan tamu tetap)', 'room'], ['r205', 'Kursi santai balkon', 'furn'], ['mr2', 'Flip chart elektronik', 'av']] as [$loc, $name, $cat]) {
            $asset = new Asset([
                'name' => $name, 'asset_category_id' => $this->cat[$cat], 'location_id' => $this->loc[$loc]->id, 'department_id' => $this->dept['rooms'],
                'verification_status' => VerificationStatus::Additional, 'identification_source' => IdentificationSource::Additional,
                'asset_survey_id' => $survey->id, 'verified_by_id' => $verifier->id, 'verified_at' => $this->today->subDays($this->rand(10, 60)),
                'acquisition_cost' => $this->costFor($this->cat[$cat]),
            ]);
            $this->randomizeHealth($asset);
            $asset->save();
        }

        // Aset utama gedung, dicatat manual dan sudah terverifikasi.
        $major = [
            // [nama, kategori, lokasi, departemen, merek, model, tahun beli, nilai (juta), profil kondisi, [dampak keselamatan, tamu, pendapatan, operasional, penggantian]]
            ['Chiller #1 350 TR', 'hvac', 'chiller', 'engineering', 'York', 'YK-350', 2014, 3200, 'fair', [4, 5, 5, 5, 5]],
            ['Chiller #2 350 TR', 'hvac', 'chiller', 'engineering', 'York', 'YK-350', 2014, 3200, 'poor', [4, 5, 5, 5, 5]],
            ['Cooling Tower #1', 'hvac', 'roof', 'engineering', 'Liang Chi', 'LBC-300', 2014, 650, 'fair', [3, 4, 4, 4, 3]],
            ['Cooling Tower #2', 'hvac', 'roof', 'engineering', 'Liang Chi', 'LBC-300', 2019, 700, 'good', [3, 4, 4, 4, 3]],
            ['AHU Grand Ballroom', 'hvac', 'ballroom', 'convention', 'Trane', 'CLCP-30', 2016, 850, 'fair', [2, 5, 5, 4, 4]],
            ['AHU Lobby', 'hvac', 'lobby', 'engineering', 'Trane', 'CLCP-14', 2016, 420, 'good', [2, 4, 3, 3, 3]],
            ['Genset #1 1000 kVA', 'elec', 'genset', 'engineering', 'Cummins', 'C1000D5', 2013, 2800, 'good', [5, 5, 5, 5, 5]],
            ['Genset #2 1000 kVA', 'elec', 'genset', 'engineering', 'Cummins', 'C1000D5', 2013, 2800, 'fair', [5, 5, 5, 5, 5]],
            ['Panel LVMDP Utama', 'elec', 'panel', 'engineering', 'Schneider', 'Prisma P', 2013, 1500, 'good', [5, 5, 5, 5, 4]],
            ['Trafo 1600 kVA', 'elec', 'panel', 'engineering', 'Trafindo', '1600 kVA', 2013, 1100, 'good', [5, 5, 5, 5, 5]],
            ['UPS Ruang Server 20 kVA', 'elec', 'server', 'it', 'APC', 'Galaxy VS', 2020, 180, 'good', [2, 3, 4, 4, 2]],
            ['Lift Penumpang #1', 'lift', 'liftroom', 'engineering', 'Mitsubishi', 'NexWay', 2013, 1350, 'good', [5, 5, 4, 5, 5]],
            ['Lift Penumpang #2', 'lift', 'liftroom', 'engineering', 'Mitsubishi', 'NexWay', 2013, 1350, 'fair', [5, 5, 4, 5, 5]],
            ['Lift Penumpang #3', 'lift', 'liftroom', 'engineering', 'Mitsubishi', 'NexWay', 2013, 1350, 'critical', [5, 5, 4, 5, 5]],
            ['Lift Barang / Service', 'lift', 'liftroom', 'engineering', 'Mitsubishi', 'Freight 2000', 2013, 980, 'poor', [4, 2, 3, 5, 4]],
            ['Eskalator Convention', 'lift', 'prefunction', 'convention', 'Schindler', '9300', 2015, 1200, 'poor', [5, 4, 4, 4, 4]],
            ['Pompa Pemadam (Fire Pump) Diesel', 'fire', 'pump', 'security', 'Ebara', 'FSA 500 GPM', 2013, 620, 'good', [5, 4, 3, 4, 4]],
            ['Pompa Jockey', 'fire', 'pump', 'security', 'Grundfos', 'CR 5', 2013, 60, 'fair', [5, 3, 2, 3, 2]],
            ['Panel Fire Alarm (MCFA)', 'fire', 'fo', 'security', 'Hochiki', 'FireNET Plus', 2018, 350, 'good', [5, 4, 3, 4, 3]],
            ['Sistem Sprinkler Gedung Utama', 'fire', 'tower', 'security', '—', 'Wet pipe', 2013, 900, 'fair', [5, 4, 3, 3, 4]],
            ['Pompa Transfer Air Bersih', 'plmb', 'pump', 'engineering', 'Grundfos', 'CR 32', 2017, 95, 'fair', [2, 5, 4, 5, 2]],
            ['Sewage Treatment Plant', 'plmb', 'pump', 'engineering', 'Biotech', 'STP 200 m³', 2013, 1400, 'poor', [3, 3, 3, 5, 4]],
            ['Heat Pump Air Panas', 'plmb', 'roof', 'engineering', 'Ariston', 'Nuos 300', 2019, 520, 'good', [2, 5, 4, 4, 3]],
            ['Combi Oven 20 GN', 'kitchen', 'kitchen', 'fnb', 'Rational', 'iCombi Pro 20-1/1', 2021, 680, 'excellent', [2, 4, 5, 4, 3]],
            ['Walk-in Freezer', 'kitchen', 'kitchen', 'fnb', 'Polar', 'WIF-12', 2016, 300, 'fair', [2, 3, 4, 5, 3]],
            ['Dishwasher Conveyor', 'kitchen', 'kitchen', 'fnb', 'Hobart', 'CS-B', 2017, 390, 'fair', [2, 3, 4, 4, 3]],
            ['Kitchen Exhaust & Hood', 'kitchen', 'kitchen', 'fnb', '—', 'Custom', 2013, 250, 'poor', [4, 2, 4, 4, 3]],
            ['Washer Extractor 60 kg', 'laundry', 'laundry', 'housekeeping', 'Electrolux', 'WH6-60', 2015, 410, 'fair', [2, 3, 3, 4, 3]],
            ['Flatwork Ironer', 'laundry', 'laundry', 'housekeeping', 'Electrolux', 'IB43316', 2015, 520, 'poor', [3, 3, 3, 4, 3]],
            ['Sound System Grand Ballroom', 'av', 'ballroom', 'convention', 'Bose', 'ShowMatch', 2022, 950, 'excellent', [1, 5, 5, 3, 3]],
            ['LED Videotron Ballroom', 'av', 'ballroom', 'convention', 'Absen', 'A27 Pro', 2022, 1250, 'good', [1, 5, 5, 3, 3]],
            ['Core Switch Jaringan', 'it', 'server', 'it', 'Cisco', 'C9300', 2021, 260, 'good', [1, 4, 4, 5, 3]],
            ['Server PMS Hotel', 'it', 'server', 'it', 'Dell', 'R650', 2022, 210, 'good', [1, 4, 5, 5, 3]],
            ['NVR CCTV 64 kanal', 'sec', 'server', 'security', 'Hikvision', 'DS-9664NI', 2020, 85, 'fair', [4, 3, 2, 3, 2]],
            ['Pompa Filter Kolam', 'pool', 'pool', 'engineering', 'Hayward', 'Super Pump', 2018, 45, 'fair', [3, 4, 3, 3, 2]],
            ['Barrier Gate Parkir', 'sec', 'parking', 'security', 'Came', 'GARD 4', 2019, 70, 'good', [2, 3, 3, 3, 2]],
        ];

        foreach ($major as [$name, $cat, $loc, $dept, $brand, $model, $year, $value, $profile, $impacts]) {
            $asset = new Asset([
                'name' => $name, 'asset_category_id' => $this->cat[$cat], 'location_id' => $this->loc[$loc]->id, 'department_id' => $this->dept[$dept],
                'brand' => $brand, 'model' => $model, 'serial_no' => strtoupper(substr(md5($name), 0, 12)),
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

    protected function randomizeHealth(Asset $asset, ?int $cap = null): void
    {
        $roll = $this->rand(1, 100);
        $this->applyProfile($asset, match (true) {
            $cap !== null => 'poor',
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

    protected function costFor(int $categoryId): int
    {
        $code = array_search($categoryId, $this->cat, true);

        return match ($code) {
            'hvac' => $this->rand(7, 16), 'room' => $this->rand(3, 9), 'plmb' => $this->rand(2, 4), 'furn' => $this->rand(6, 14),
            'fire' => 1, 'sec' => $this->rand(3, 5), 'av' => $this->rand(8, 25), default => $this->rand(2, 10),
        } * 1_000_000;
    }

    protected function operations(): void
    {
        $assets = Asset::query()->get();
        $major = $assets->where('identification_source', IdentificationSource::Manual)->values();
        $tech = [$this->users['teknisi'], $this->users['manajer']];

        // Rencana pemeliharaan preventif untuk aset utama; sebagian lewat jatuh tempo.
        $frequencies = ['hvac' => 'monthly', 'elec' => 'monthly', 'lift' => 'monthly', 'fire' => 'quarterly', 'plmb' => 'quarterly', 'kitchen' => 'monthly', 'laundry' => 'quarterly', 'av' => 'semiannual', 'it' => 'quarterly', 'sec' => 'semiannual', 'pool' => 'weekly'];
        $plans = collect();

        foreach ($major as $i => $asset) {
            $code = array_search($asset->asset_category_id, $this->cat, true);
            $due = $i % 6 === 0 ? $this->today->subDays($this->rand(3, 40)) : $this->today->addDays($this->rand(1, 60));
            $plans->push(PreventivePlan::create([
                'name' => 'PM '.$asset->name,
                'asset_id' => $asset->id,
                'frequency' => $frequencies[$code] ?? 'quarterly',
                'next_due_date' => $due,
                'responsible_id' => $this->pick($tech)->id,
                'checklist' => "1. Periksa kondisi fisik dan kebersihan\n2. Ukur parameter operasi (arus, tekanan, suhu)\n3. Pelumasan dan pengencangan\n4. Uji fungsi dan catat temuan",
            ]));
        }

        // Work order 12 bulan terakhir untuk tren dasbor.
        $corrective = ['Unit tidak dingin, kompresor trip', 'Kebocoran pipa', 'Bunyi abnormal', 'Panel kontrol error', 'Sensor tidak responsif', 'Kabel terbakar', 'Motor overheat', 'Tombol rusak'];
        for ($m = 11; $m >= 0; $m--) {
            $monthStart = $this->today->startOfMonth()->subMonths($m);
            $counts = ['preventive' => $this->rand(7, 13), 'corrective' => $this->rand(3, 9), 'emergency' => $this->rand(0, 3)];

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
                    $spare = $type === 'preventive' ? $this->rand(0, 15) * 100_000 : $this->rand(5, 120) * 100_000;
                    $vendor = $type === 'emergency' || ($type === 'corrective' && $this->rand(1, 4) === 1) ? $this->rand(10, 250) * 100_000 : 0;

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
                        'description' => $type === 'preventive' ? 'Pemeliharaan preventif terjadwal' : $this->pick($corrective),
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

        // Inspeksi enam bulan terakhir.
        for ($i = 0; $i < 70; $i++) {
            $asset = $this->rand(1, 3) > 1 ? $major->random() : $assets->random();
            $score = max(10, min(100, (int) round($asset->physical_condition + $this->rand(-8, 6))));
            Inspection::create([
                'number' => null,
                'asset_id' => $asset->id,
                'inspected_at' => $this->today->subDays($this->rand(0, 180))->setTime($this->rand(7, 18), $this->rand(0, 59)),
                'inspector_id' => $this->pick($tech)->id,
                'result' => $score >= 70 ? 'pass' : ($score >= 50 ? 'monitor' : 'fail'),
                'condition_score' => $score,
                'finding' => $score >= 70 ? 'Kondisi normal.' : $this->pick(['Getaran berlebih pada bearing', 'Korosi pada rangka', 'Arus motor di atas nominal', 'Rembesan oli', 'Isolasi kabel getas']),
                'recommendation' => $score >= 70 ? null : $this->pick(['Jadwalkan penggantian bearing', 'Lakukan overhaul', 'Pantau mingguan', 'Ajukan CAPEX penggantian']),
                'meter_reading' => $asset->asset_category_id === $this->cat['elec'] ? $this->rand(8000, 26000) : null,
                'meter_unit' => $asset->asset_category_id === $this->cat['elec'] ? 'jam' : null,
            ]);
        }

        // Permintaan pemeliharaan, termasuk dari pindai QR oleh tamu/staf.
        $complaints = ['AC kamar tidak dingin', 'TV tidak menyala', 'Air panas tidak keluar', 'Minibar tidak dingin', 'Kunci kamar tidak terbaca', 'Lampu berkedip', 'Proyektor mati sendiri', 'Bunyi berisik dari unit', 'Kebocoran air di bawah unit'];
        $roomAssets = $assets->where('identification_source', IdentificationSource::Template)->values();

        for ($i = 0; $i < 36; $i++) {
            $viaQr = $this->rand(1, 100) <= 45;
            $requestedAt = $this->today->subDays($this->rand(0, 90))->setTime($this->rand(6, 22), $this->rand(0, 59));
            $state = $requestedAt->lessThan($this->today->subDays(10)) ? $this->pick(['converted', 'converted', 'cancel']) : $this->pick(['new', 'new', 'verified', 'converted']);
            $request = MaintenanceRequest::create([
                'number' => Sequence::next('request', $this->company->id, $requestedAt->year),
                'asset_id' => $roomAssets->random()->id,
                'requested_at' => $requestedAt,
                'reporter_id' => $viaQr ? null : $this->users['teknisi2']->id,
                'reporter_name' => $viaQr ? $this->pick(['Tamu kamar', 'Bu Sari (HK)', 'Pak Ujang (Security)', null]) : null,
                'reporter_contact' => $viaQr ? $this->pick(['Kamar 305', '0812-xxxx-1234', null]) : null,
                'via_qr' => $viaQr,
                'request_type' => $this->rand(1, 10) === 1 ? 'emergency' : 'corrective',
                'priority' => $this->pick(['low', 'medium', 'medium', 'high', 'urgent']),
                'description' => ($viaQr ? '[Laporan QR] ' : '').$this->pick($complaints),
                'state' => in_array($state, ['converted'], true) ? 'verified' : $state,
            ]);

            if ($state === 'converted') {
                $wo = $request->createWorkOrder();
                $wo->forceFill(['planned_at' => $requestedAt->addHours(2)])->save();

                if ($requestedAt->lessThan($this->today->subDays(10))) {
                    $wo->forceFill(['state' => 'closed', 'started_at' => $requestedAt->addHours(3), 'completed_at' => $requestedAt->addHours(6), 'sparepart_cost' => $this->rand(1, 20) * 50_000, 'action_taken' => 'Perbaikan selesai, unit berfungsi normal'])->save();
                }
            }
        }

        // Register risiko tersebar di matriks 5×5.
        $risks = [
            ['Chiller #2 gagal saat beban puncak acara', 'Chiller #2 350 TR', 4, 5, 'mitigating', 'Overhaul kompresor dan siapkan chiller sewa saat event besar.'],
            ['Lift #3 terjebak penumpang', 'Lift Penumpang #3', 4, 5, 'open', 'Modernisasi kontrol lift; sementara batasi pemakaian.'],
            ['Genset #2 gagal start saat PLN padam', 'Genset #2 1000 kVA', 3, 5, 'mitigating', 'Uji beban bulanan, ganti baterai starter.'],
            ['Eskalator berhenti mendadak saat acara', 'Eskalator Convention', 3, 4, 'open', 'Ganti step chain dan handrail.'],
            ['STP tidak memenuhi baku mutu', 'Sewage Treatment Plant', 3, 4, 'open', 'Revitalisasi blower dan media biofilter.'],
            ['Kebakaran saluran exhaust dapur', 'Kitchen Exhaust & Hood', 2, 5, 'mitigating', 'Pembersihan ducting triwulanan oleh vendor bersertifikat.'],
            ['Pompa jockey bocor, tekanan sprinkler turun', 'Pompa Jockey', 2, 4, 'open', 'Ganti mechanical seal.'],
            ['Ironer merusak linen', 'Flatwork Ironer', 3, 2, 'open', 'Kalibrasi suhu dan ganti padding.'],
            ['Walk-in freezer suhu naik', 'Walk-in Freezer', 2, 3, 'open', 'Pasang alarm suhu jarak jauh.'],
            ['Server PMS mati, check-in terganggu', 'Server PMS Hotel', 1, 5, 'mitigating', 'Replikasi ke cloud dan UPS terpisah.'],
            ['CCTV area parkir tidak merekam', 'NVR CCTV 64 kanal', 2, 2, 'closed', 'HDD diganti.'],
            ['Pompa filter kolam rusak saat musim liburan', 'Pompa Filter Kolam', 3, 3, 'open', 'Sediakan pompa cadangan.'],
            ['Cooling tower #1 korosi rangka', 'Cooling Tower #1', 4, 3, 'open', 'Pengecatan ulang dan penggantian panel.'],
            ['Panel LVMDP panas berlebih', 'Panel LVMDP Utama', 1, 4, 'open', 'Thermography rutin.'],
            ['Lift barang aus, operasional BOH terhambat', 'Lift Barang / Service', 4, 3, 'open', 'Ganti tali baja dan sheave.'],
        ];

        foreach ($risks as [$name, $assetName, $p, $c, $state, $mitigation]) {
            AssetRisk::create([
                'name' => $name, 'asset_id' => $major->firstWhere('name', $assetName)->id, 'probability' => $p, 'consequence' => $c,
                'state' => $state, 'mitigation' => $mitigation, 'owner_id' => $this->users['manajer']->id,
                'target_date' => $this->today->addDays($this->rand(-20, 120)),
            ]);
        }

        // Usulan CAPEX.
        $capex = [
            ['Penggantian Chiller #2 dengan chiller magnetic bearing', 'Chiller #2 350 TR', 'mandatory', 'replace', 4800, [5, 4, 5, 5], 'proposed'],
            ['Modernisasi kontrol Lift #3', 'Lift Penumpang #3', 'safety', 'overhaul', 1650, [5, 5, 3, 5], 'approved'],
            ['Overhaul eskalator Convention', 'Eskalator Convention', 'safety', 'overhaul', 780, [4, 4, 4, 4], 'proposed'],
            ['Revitalisasi STP', 'Sewage Treatment Plant', 'mandatory', 'overhaul', 950, [2, 3, 2, 5], 'draft'],
            ['Penggantian ironer laundry', 'Flatwork Ironer', 'efficiency', 'replace', 680, [3, 2, 2, 3], 'draft'],
            ['Renovasi soft furnishing kamar lantai 2–3', null, 'guest', 'replace', 1350, [5, 1, 4, 3], 'proposed'],
            ['Retrofit lampu LED area publik', null, 'efficiency', 'replace', 420, [3, 1, 2, 2], 'done'],
            ['Upgrade kamera CCTV ke IP 4K', 'NVR CCTV 64 kanal', 'safety', 'replace', 380, [2, 4, 1, 2], 'rejected'],
        ];

        foreach ($capex as [$name, $assetName, $category, $recommendation, $cost, [$guest, $safety, $revenue, $urgency], $state]) {
            CapexProposal::create([
                'name' => $name, 'asset_id' => $assetName ? $major->firstWhere('name', $assetName)->id : null,
                'category' => $category, 'recommendation' => $recommendation, 'target_year' => $this->today->year + ($state === 'draft' ? 1 : 0),
                'estimated_cost' => $cost * 1_000_000, 'guest_impact' => $guest, 'safety_impact' => $safety, 'revenue_impact' => $revenue,
                'urgency' => $urgency, 'state' => $state, 'justification' => 'Disusun dari hasil inspeksi, register risiko, dan biaya pemeliharaan 12 bulan terakhir.',
            ]);
        }
    }
}
