<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_ignores_previous_member_destination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession(['url.intended' => route('profile')])
            ->post(route('login.submit'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('filament.admin.pages.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_panel_login_also_redirects_admin_to_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->withSession(['url.intended' => route('profile')]);
        Livewire::test(Login::class)->fillForm(['email' => $admin->email, 'password' => 'password'])
            ->call('authenticate')->assertHasNoFormErrors()
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_member_login_keeps_intended_destination(): void
    {
        $member = User::factory()->create();
        $this->withSession(['url.intended' => route('profile')])
            ->post(route('login.submit'), ['email' => $member->email, 'password' => 'password'])
            ->assertRedirect(route('profile'));
    }

    public function test_admin_sidebar_links_to_landing_and_catalog(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('home'))->assertOk()
            ->assertSee('href="'.route('filament.admin.pages.dashboard').'"', false)->assertSee('Panel Admin');
        $this->get(route('filament.admin.pages.dashboard'))->assertOk()
            ->assertSee('href="'.route('home').'"', false)->assertSee('Landing Page')
            ->assertSee('href="'.route('catalog').'"', false)->assertSee('Halaman Katalog')
            ->assertDontSee('admin-landing-link');
        $this->get(route('catalog'))->assertOk()->assertSee('Panel Admin');
        $this->get(route('login'))->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_public_and_member_headers_do_not_offer_admin_access(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('Panel Admin');
        $this->actingAs(User::factory()->create());
        $this->get(route('home'))->assertOk()->assertDontSee('Panel Admin')
            ->assertSee('href="'.route('dashboard').'"', false);
    }
}
