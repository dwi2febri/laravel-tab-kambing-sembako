<?php

namespace Tests\Feature;

use App\Filament\Resources\Handovers\HandoverResource;
use App\Filament\Resources\Handovers\Pages\ViewHandover;
use App\Models\Handover;
use App\Models\User;
use App\Models\UserSaving;
use App\Services\HandoverService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HandoverReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function pendingHandover(): Handover
    {
        $saving = UserSaving::factory()->create(['status' => 'handover_requested', 'remaining_amount' => 0]);

        return Handover::factory()->create(['user_saving_id' => $saving->id, 'preferred_at' => now()->addDays(2), 'location' => 'Lokasi pembeli Semarang', 'latitude' => -6.9667, 'longitude' => 110.4167]);
    }

    public function test_admin_confirms_from_detail_and_only_buyer_receipt_completes_delivery(): void
    {
        $handover = $this->pendingHandover();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(HandoverResource::getUrl('view', ['record' => $handover]))->assertOk()->assertSee('Rincian pengajuan')->assertSee('data-handover-detail-map')->assertSee('Lokasi pembeli Semarang');
        Livewire::test(ViewHandover::class, ['record' => $handover->id])->callAction('confirm')->assertHasNoErrors();
        $this->assertSame('scheduled', $handover->fresh()->status);
        $this->assertSame('handover_scheduled', $handover->userSaving->fresh()->status);
        $this->assertNull($handover->fresh()->completed_at);
        $saving = $handover->userSaving;
        $this->actingAs($saving->user)->get(route('savings.show', $saving))->assertOk()->assertSee('Proses Penyerahan')->assertSee('Terima')->assertSee('name="received"', false);
        $this->post(route('handovers.receive', $saving), ['received' => 1])->assertSessionHasNoErrors()->assertRedirect(route('savings.show', $saving));
        $this->assertSame('completed', $handover->fresh()->status);
        $this->assertSame('completed', $saving->fresh()->status);
        $this->assertSame($admin->id, $handover->fresh()->handled_by);
        $this->assertNotNull($handover->fresh()->completed_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'title' => 'Serah terima selesai']);
        $this->get(route('savings.show', $saving))->assertOk()->assertSee('Diterima pada')->assertDontSee('name="received"', false);
    }

    public function test_receipt_is_forbidden_for_other_members_and_admins(): void
    {
        $handover = $this->pendingHandover();
        $this->actingAs(User::factory()->create())->post(route('handovers.receive', $handover->userSaving), ['received' => 1])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('handovers.receive', $handover->userSaving), ['received' => 1])->assertForbidden();
        $this->assertSame('requested', $handover->fresh()->status);
    }

    public function test_buyer_cannot_receive_an_unconfirmed_handover(): void
    {
        $handover = $this->pendingHandover();
        $this->actingAs($handover->userSaving->user)->post(route('handovers.receive', $handover->userSaving), ['received' => 1])->assertSessionHasErrors('handover');
        $this->assertNull($handover->fresh()->completed_at);
    }

    public function test_receipt_requires_explicit_confirmation_and_cannot_be_repeated(): void
    {
        $handover = $this->pendingHandover();
        app(HandoverService::class)->schedule(User::factory()->create(['role' => 'admin']), $handover, $handover->preferred_at->toDateTimeString(), $handover->location);
        $this->actingAs($handover->userSaving->user)->post(route('handovers.receive', $handover->userSaving), ['received' => 0])->assertSessionHasErrors('received');
        $this->assertSame('scheduled', $handover->fresh()->status);
        $this->post(route('handovers.receive', $handover->userSaving), ['received' => 1])->assertSessionHasNoErrors();
        $completedAt = $handover->fresh()->completed_at;
        $this->post(route('handovers.receive', $handover->userSaving), ['received' => 1])->assertSessionHasErrors('handover');
        $this->assertTrue($completedAt->equalTo($handover->fresh()->completed_at));
    }

    public function test_member_cannot_open_admin_detail_and_admin_cannot_complete_via_service(): void
    {
        $handover = $this->pendingHandover();
        $this->actingAs($handover->userSaving->user)->get(HandoverResource::getUrl('view', ['record' => $handover]))->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(HandoverService::class)->complete(User::factory()->create(['role' => 'admin']), $handover);
    }

    public function test_detail_does_not_confirm_incomplete_legacy_request(): void
    {
        $handover = $this->pendingHandover();
        $handover->update(['preferred_at' => null, 'latitude' => null]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(ViewHandover::class, ['record' => $handover->id])->callAction('confirm')->assertHasErrors('handover');
        $this->assertSame('requested', $handover->fresh()->status);
    }
}
