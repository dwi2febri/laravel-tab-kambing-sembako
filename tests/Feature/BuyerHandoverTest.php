<?php

namespace Tests\Feature;

use App\Models\Handover;
use App\Models\User;
use App\Models\UserSaving;
use App\Services\HandoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerHandoverTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['recipient_name' => 'Pembeli', 'preferred_at' => now()->addDays(2)->format('Y-m-d\TH:i'), 'location' => 'Alamat pembeli, Semarang', 'latitude' => -6.9667, 'longitude' => 110.4167];
    }

    public function test_buyer_submits_schedule_and_location_and_admin_confirms_without_changing_them(): void
    {
        $saving = UserSaving::factory()->create(['status' => 'paid', 'remaining_amount' => 0]);
        $this->actingAs($saving->user)->post(route('handovers.request', $saving), $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $handover = $saving->fresh()->handover;
        $this->assertSame('handover_requested', $saving->fresh()->status);
        $this->assertSame($this->payload()['preferred_at'], $handover->preferred_at->format('Y-m-d\TH:i'));
        $this->assertEquals(-6.9667, $handover->latitude);
        $this->get(route('savings.show', $saving))->assertOk()->assertSee('Menunggu konfirmasi admin.')->assertSee('Alamat pembeli, Semarang');
        app(HandoverService::class)->schedule(User::factory()->create(['role' => 'admin']), $handover, now()->addMonth()->toDateTimeString(), 'Lokasi berbeda');
        $this->assertTrue($handover->fresh()->scheduled_at->equalTo($handover->preferred_at));
        $this->assertSame('Alamat pembeli, Semarang', $handover->fresh()->location);
    }

    public function test_buyer_can_edit_pending_request_without_creating_another_handover(): void
    {
        $saving = UserSaving::factory()->create(['status' => 'handover_requested']);
        $handover = Handover::factory()->create(['user_saving_id' => $saving->id, 'preferred_at' => now()->addDay(), 'location' => 'Alamat lama', 'latitude' => -6.9, 'longitude' => 110.4]);
        $this->actingAs($saving->user)->get(route('savings.show', $saving))->assertOk()->assertSee('Ubah pengajuan');
        $this->get(route('savings.show', ['saving' => $saving, 'edit_handover' => 1]))->assertOk()->assertSee('Simpan perubahan')->assertSee('Alamat lama');
        $this->put(route('handovers.update', $saving), [...$this->payload(), 'note' => 'Catatan baru'])->assertSessionHasNoErrors()->assertRedirect(route('savings.show', $saving));
        $this->assertDatabaseCount('handovers', 1);
        $this->assertSame('Alamat pembeli, Semarang', $handover->fresh()->location);
        $this->assertSame('Catatan baru', $handover->fresh()->note);
        $this->assertEquals(-6.9667, $handover->fresh()->latitude);
        $this->assertSame('handover_requested', $saving->fresh()->status);
    }

    public function test_other_users_and_confirmed_requests_cannot_be_edited(): void
    {
        $saving = UserSaving::factory()->create(['status' => 'handover_scheduled']);
        $handover = Handover::factory()->create(['user_saving_id' => $saving->id, 'status' => 'scheduled', 'location' => 'Lokasi dikonfirmasi']);
        $this->actingAs(User::factory()->create())->put(route('handovers.update', $saving), $this->payload())->assertForbidden();
        $this->actingAs($saving->user)->put(route('handovers.update', $saving), $this->payload())->assertSessionHasErrors('handover');
        $this->get(route('savings.show', $saving))->assertOk()->assertDontSee('Ubah pengajuan');
        $this->assertSame('Lokasi dikonfirmasi', $handover->fresh()->location);
    }

    public function test_schedule_and_coordinates_are_required_and_must_be_valid(): void
    {
        $saving = UserSaving::factory()->create(['status' => 'paid']);
        $this->actingAs($saving->user)->post(route('handovers.request', $saving), ['recipient_name' => 'Pembeli'])->assertSessionHasErrors(['preferred_at', 'location', 'latitude', 'longitude']);
        $this->post(route('handovers.request', $saving), [...$this->payload(), 'preferred_at' => now()->subDay()->format('Y-m-d\TH:i'), 'latitude' => 100, 'longitude' => 190])->assertSessionHasErrors(['preferred_at', 'latitude', 'longitude']);
        $this->assertDatabaseCount('handovers', 0);
    }

    public function test_only_the_buyer_of_paid_savings_can_request_handover(): void
    {
        $saving = UserSaving::factory()->create(['status' => 'active']);
        $this->actingAs($saving->user)->post(route('handovers.request', $saving), $this->payload())->assertSessionHasErrors('handover');
        $this->actingAs(User::factory()->create())->post(route('handovers.request', $saving), $this->payload())->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('handovers.request', $saving), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('handovers', 0);
    }

    public function test_buyer_can_complete_the_details_of_a_legacy_pending_request(): void
    {
        $saving = UserSaving::factory()->create(['status' => 'handover_requested']);
        $handover = Handover::factory()->create(['user_saving_id' => $saving->id]);
        $this->actingAs($saving->user)->get(route('savings.show', $saving))->assertOk()->assertSee('Hari, tanggal dan jam');
        $this->post(route('handovers.request', $saving), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('handovers', 1);
        $this->assertNotNull($handover->fresh()->preferred_at);
    }
}
