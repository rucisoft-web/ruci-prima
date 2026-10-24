<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Asset;
use App\Models\Company;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\CurrentCompany;
use Database\Seeders\Demo\DemoCompany;
use Database\Seeders\Demo\SahidDemo;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Semua halaman panel terbuka tanpa galat untuk setiap peran, memakai data contoh Hotel Sahid. */
class PanelPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SahidDemo::class);
        app(CurrentCompany::class)->set(null);
    }

    public function test_every_page_renders_for_each_role(): void
    {
        $company = Company::where('slug', 'sahid')->first();
        $base = '/app/sahid';
        $ids = fn ($model) => $model::withoutGlobalScopes()->where('company_id', $company->id)->value('id');

        $common = [
            '', '/assets', '/assets/'.$ids(Asset::class), '/inspections', '/maintenance-requests',
            '/maintenance-requests/'.$ids(MaintenanceRequest::class), '/work-orders', '/work-orders/'.$ids(WorkOrder::class),
            '/preventive-plans', '/asset-risks', '/capex-proposals', '/asset-surveys', '/asset-surveys/'.$ids(\App\Models\AssetSurvey::class), '/verifikasi-aset',
            '/dasbor-aset', '/dasbor-pimpinan', '/mengapa-ruci-prima', '/fitur-utama', '/kenapa-perlu-ruci-prima', '/panduan-penggunaan', '/panduan-penggunaan?peran=teknisi', '/jadwal-ruang-rapat', '/jadwal-ruang-rapat?minggu=2020-01-01', '/room-bookings', '/vehicle-bookings', '/vehicles',
            '/vehicles/'.$ids(\App\Models\Vehicle::class), '/supply-requests', '/supply-requests/'.$ids(\App\Models\SupplyRequest::class),
            '/supply-items', '/stock-movements', '/legal-documents',
        ];
        $managerOnly = ['/departments', '/locations', '/asset-categories', '/asset-templates', '/asset-templates/'.$ids(\App\Models\AssetTemplate::class).'/edit', '/audit-logs',
            '/meeting-rooms', '/vehicles/create', '/vehicles/'.$ids(\App\Models\Vehicle::class).'/edit'];
        $operate = ['/assets/create', '/assets/'.$ids(Asset::class).'/edit', '/inspections/create', '/work-orders/create', '/supply-requests/create'];

        foreach (['manajer@sahid.demo' => Role::Manajer, 'teknisi@sahid.demo' => Role::Staf, 'direktur@sahid.demo' => Role::Eksekutif] as $email => $role) {
            $user = User::where('email', $email)->first();
            $this->flushSession();
            $this->app['auth']->forgetGuards();
            $this->actingAs($user);

            $check = fn (string $path, int $status) => $this->assertSame($status, $this->get($base.$path)->status(), "{$email} {$path}");

            foreach ($common as $path) {
                $check($path, 200);
            }

            foreach ($managerOnly as $path) {
                $check($path, $role === Role::Manajer ? 200 : 403);
            }

            foreach ($operate as $path) {
                $check($path, $role === Role::Eksekutif ? 403 : 200);
            }
        }
    }

    public function test_dashboard_shows_kpis_and_drilldown_links(): void
    {
        $this->actingAs(User::where('email', 'direktur@sahid.demo')->first())
            ->get('/app/sahid/dasbor-aset')
            ->assertOk()
            ->assertSee('Asset Care Index')
            ->assertSee('Lift Penumpang #3')
            ->assertSee('tab=kritis', false);
    }

    public function test_general_affairs_dashboard_shows_deadlines_and_low_stock(): void
    {
        $this->actingAs(User::where('email', 'umum@sahid.demo')->first())
            ->get('/app/sahid')
            ->assertOk()
            ->assertSee('Ruci PRIMA')
            ->assertSee('SILO Genset 1500 kVA')
            ->assertSee('Toner HP 85A')
            ->assertSee('Pajak tahunan B 9123 SHJ');
    }

    public function test_leadership_dashboard_and_explainer(): void
    {
        $this->actingAs(User::where('email', 'direktur@sahid.demo')->first());

        $this->get('/app/sahid/dasbor-pimpinan')->assertOk()
            ->assertSee('Indeks Kesiapan Operasional')
            ->assertSee('Menunggu keputusan Anda')
            ->assertSee('Renovasi kamar tahap II')
            ->assertSee('Biaya operasional 12 bulan');

        $this->get('/app/sahid/mengapa-ruci-prima')->assertOk()
            ->assertSee('Contoh di perhotelan')
            ->assertSee('Dewan Komisaris');
    }

    public function test_group_executive_sees_all_three_demo_companies(): void
    {
        $this->seed(DemoSeeder::class);
        $user = User::where('email', DemoCompany::GROUP_EXECUTIVE)->first();
        $this->actingAs($user);

        foreach (['sahid' => 'Contoh di perhotelan', 'bluebird' => 'Contoh di transportasi', 'diamond' => 'Contoh di manufaktur'] as $slug => $text) {
            $this->get("/app/{$slug}/dasbor-pimpinan")->assertOk()->assertSee('Indeks Kesiapan Operasional');
            $this->get("/app/{$slug}/mengapa-ruci-prima")->assertOk()->assertSee($text);
        }
    }

    public function test_non_member_cannot_open_company(): void
    {
        $outsider = User::factory()->create();
        $this->actingAs($outsider)->get('/app/sahid')->assertNotFound();
    }

    public function test_platform_panel_for_admin_only(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($admin)->get('/platform/companies')->assertOk()->assertSee('Hotel Sahid');
        $this->actingAs($admin)->get('/platform/users')->assertOk();

        $this->actingAs(User::where('email', 'manajer@sahid.demo')->first())->get('/platform/companies')->assertForbidden();
    }

    public function test_user_without_company_is_redirected_instead_of_404(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@contoh.id', 'password' => 'RahasiaKuat#1', 'is_platform_admin' => true]);
        $this->actingAs($admin)->get('/app')->assertRedirect('/platform');

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $user = User::create(['name' => 'Staf', 'email' => 'staf@contoh.id', 'password' => 'RahasiaKuat#1']);
        $this->actingAs($user)->get('/app')->assertRedirect(route('no-company'));
        $this->get(route('no-company'))->assertOk()->assertSee('staf@contoh.id');
    }
}
