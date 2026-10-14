<?php

namespace Tests\Feature;

use App\Filament\Pages\AdminNotifications;
use App\Filament\Resources\Handovers\HandoverResource;
use App\Livewire\AdminNotificationBell;
use App\Models\ChatConversation;
use App\Models\Handover;
use App\Models\Payment;
use App\Models\User;
use App\Services\SavingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notifications_only_reach_active_admins_and_are_private(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);
        $inactive = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $member = User::factory()->create();
        SavingService::notifyAdmins('payment', 'Pembayaran baru', 'Menunggu konfirmasi');
        $this->assertSame(0, $inactive->memberNotifications()->count());
        $this->assertSame(0, $member->memberNotifications()->count());
        $this->actingAs($admin);
        $this->assertSame('1', AdminNotifications::getNavigationBadge());
        Livewire::test(AdminNotifications::class)
            ->assertCanSeeTableRecords($admin->memberNotifications)
            ->assertCanNotSeeTableRecords($other->memberNotifications)
            ->callTableAction('read', $admin->memberNotifications->first());
        $this->assertNull(AdminNotifications::getNavigationBadge());
        $this->assertNull($other->memberNotifications->first()->fresh()->read_at);
        $this->actingAs($member)->get('/admin/admin-notifications')->assertForbidden();
    }

    public function test_bell_renders_popup_and_updates_only_own_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);
        SavingService::notifyAdmins('payment', 'Pembayaran baru', 'Menunggu konfirmasi');
        $this->actingAs($admin);
        Livewire::test(AdminNotificationBell::class)
            ->assertSee('admin-notification-popup')
            ->assertSee('Pembayaran baru')
            ->assertViewHas('unreadCount', 1)
            ->call('markRead', $admin->memberNotifications()->first()->id)
            ->assertViewHas('unreadCount', 0);
        $this->assertNull($other->memberNotifications()->first()->read_at);
        Livewire::test(AdminNotificationBell::class)
            ->call('markRead', $other->memberNotifications()->first()->id)->assertNotFound();
    }

    public function test_popup_shows_existing_menu_queues_without_notification_history(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $payments = Payment::factory()->count(2)->create(['status' => 'pending']);
        Handover::factory()->create(['status' => 'requested']);
        $popup = Livewire::test(AdminNotificationBell::class)
            ->assertViewHas('actionCount', 3)->assertViewHas('unreadCount', 0)
            ->assertSee('Konfirmasi Pembayaran')->assertSee('Serah Terima')
            ->assertSee('3 perlu ditindaklanjuti')
            ->call('toggleMenu', 'payment')->assertSet('expandedMenu', 'payment')
            ->assertViewHas('queueDetails', fn (array $details): bool => count($details) === 2)
            ->call('toggleMenu', 'handover')->assertSet('expandedMenu', 'handover')
            ->assertViewHas('queueDetails', fn (array $details): bool => count($details) === 1)
            ->call('toggleMenu', 'chat')->assertSee('Tidak ada antrean pada menu ini.')
            ->call('toggleMenu', 'chat')->assertSet('expandedMenu', null);
        $popup->call('markAllRead')->assertViewHas('actionCount', 3);
        $payments->first()->update(['status' => 'approved']);
        $popup->call('$refresh')->assertViewHas('actionCount', 2);
    }

    public function test_polling_updates_bell_and_dispatches_sidebar_refresh_when_queues_change(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $bell = Livewire::test(AdminNotificationBell::class)->assertViewHas('actionCount', 0);
        $payment = Payment::factory()->create(['status' => 'pending']);
        $handover = Handover::factory()->create(['status' => 'requested']);
        $conversation = ChatConversation::create(['user_id' => User::factory()->create()->id]);
        $message = $conversation->messages()->create(['is_admin' => false, 'body' => 'Pesan baru']);
        $bell->call('syncNotifications')->assertViewHas('actionCount', 3)->assertDispatched('refresh-sidebar');
        $payment->update(['status' => 'approved']);
        $handover->update(['status' => 'completed']);
        $message->forceFill(['read_at' => now()])->save();
        $bell->call('syncNotifications')->assertViewHas('actionCount', 0)->assertDispatched('refresh-sidebar');
    }

    public function test_unchanged_counts_do_not_refresh_sidebar_and_sync_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(AdminNotificationBell::class)->call('syncNotifications')->assertNotDispatched('refresh-sidebar');
        $this->actingAs(User::factory()->create());
        Livewire::test(AdminNotificationBell::class)->assertForbidden();
    }

    public function test_handover_badge_counts_only_unfinished_records(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Handover::factory()->create(['status' => 'requested']);
        $scheduled = Handover::factory()->create(['status' => 'scheduled']);
        Handover::factory()->create(['status' => 'completed']);
        $this->assertSame('2', HandoverResource::getNavigationBadge());
        $this->get('/admin/handovers')->assertOk()->assertSee('2 perlu ditindaklanjuti');
        $scheduled->update(['status' => 'completed']);
        $this->assertSame('1', HandoverResource::getNavigationBadge());
    }
}
