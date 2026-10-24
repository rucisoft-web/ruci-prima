<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CurrentCompany;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tombol tamu di halaman masuk, pita pindah perusahaan, dan halaman fitur utama. */
class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_features_and_login_pages_without_demo_data(): void
    {
        $this->get('/fitur')->assertOk()->assertSee('Fitur utama')->assertDontSee('Coba sebagai Tamu');
        $this->get('/app/login')->assertOk()->assertSee('Pelajari fitur utama Ruci PRIMA')->assertDontSee('Masuk sebagai Tamu');
        $this->get('/tamu')->assertNotFound();
    }

    public function test_guest_enters_hotel_sahid_by_default_and_can_switch(): void
    {
        $this->seed(DemoSeeder::class);
        app(CurrentCompany::class)->set(null);

        $this->get('/app/login')->assertOk()->assertSee('Masuk sebagai Tamu')->assertSee('Hotel Sahid Jaya')->assertSee('Blue Bird')->assertSee('Diamond Food');
        $this->get('/fitur')->assertOk()->assertSee('Coba sebagai Tamu')->assertSee('perusahaan=bluebird', false);

        $this->get('/tamu')->assertRedirect('/app/sahid/dasbor-pimpinan');
        $guest = User::where('email', User::GUEST_EMAIL)->first();
        $this->assertAuthenticatedAs($guest);

        $this->get('/app/sahid/dasbor-pimpinan')->assertOk()
            ->assertSee('Mode tamu')->assertSee('/app/bluebird/dasbor-pimpinan', false)->assertSee('/app/diamond/dasbor-pimpinan', false);
        $this->get('/app/sahid/fitur-utama')->assertOk()->assertSee('Nilai bagi setiap pemangku kepentingan');

        $this->get('/tamu?perusahaan=bluebird')->assertRedirect('/app/bluebird/dasbor-pimpinan');
        $this->get('/tamu?perusahaan=tidak-ada')->assertRedirect('/app/sahid/dasbor-pimpinan');
        $this->get('/app/bluebird/dasbor-pimpinan')->assertOk()->assertSee('Mode tamu');
    }

    public function test_guest_is_read_only(): void
    {
        $this->seed(DemoSeeder::class);
        app(CurrentCompany::class)->set(null);
        $this->get('/tamu');
        $guest = User::where('email', User::GUEST_EMAIL)->first();

        $this->get('/app/sahid/assets/create')->assertForbidden();
        $this->get('/app/profile')->assertRedirect();

        $guest->update(['name' => 'Diubah', 'password' => 'baru-sekali']);
        $this->assertSame('Tamu', $guest->fresh()->name);
    }

    public function test_regular_users_do_not_see_guest_bar(): void
    {
        $this->seed(DemoSeeder::class);
        app(CurrentCompany::class)->set(null);
        $this->actingAs(User::where('email', 'direktur@sahid.demo')->first());

        $this->get('/app/sahid/dasbor-pimpinan')->assertOk()->assertDontSee('Mode tamu');
    }

    public function test_usage_guide_defaults_to_the_users_role_and_shows_screenshots(): void
    {
        $this->seed(DemoSeeder::class);
        app(CurrentCompany::class)->set(null);

        foreach (['manajer@sahid.demo' => 'Manajer / Kepala Bagian Umum', 'teknisi@sahid.demo' => 'Staf Teknik / Teknisi', 'umum@sahid.demo' => 'Staf Bagian Umum', 'direktur@sahid.demo' => 'Direksi &amp; Komisaris'] as $email => $title) {
            $this->flushSession();
            $this->app['auth']->forgetGuards();
            $this->actingAs(User::where('email', $email)->first());
            $this->get('/app/sahid/panduan-penggunaan')->assertOk()->assertSee('<h1>'.$title.'</h1>', false)->assertSee('img/panduan/', false);
        }

        $this->get('/app/sahid/panduan-penggunaan?peran=tamu')->assertOk()->assertSee('Masuk sebagai Tamu')->assertSee('rp-shot-placeholder', false);
        $this->get('/app/sahid/kenapa-perlu-ruci-prima')->assertOk()->assertSee('Masalah dan solusinya')->assertSee('Solusi Ruci PRIMA');
    }
}
