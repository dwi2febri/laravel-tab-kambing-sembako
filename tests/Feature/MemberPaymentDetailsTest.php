<?php

namespace Tests\Feature;

use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Filament\Resources\Payments\Pages\ViewMemberPayments;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSaving;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberPaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_has_one_row_per_saving_member_and_detail_has_all_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $other = User::factory()->create();
        $emptyMember = User::factory()->create();
        $first = UserSaving::factory()->create(['user_id' => $member->id, 'product_name_snapshot' => 'Kambing Pilihan']);
        $second = UserSaving::factory()->create(['user_id' => $member->id, 'product_name_snapshot' => 'Paket Sembako']);
        $noPayment = UserSaving::factory()->create(['user_id' => $member->id, 'product_name_snapshot' => 'Paket Belum Dibayar']);
        $payment = Payment::factory()->create(['installment_id' => Installment::factory()->create(['user_saving_id' => $first->id])->id]);
        $approved = Payment::factory()->create(['installment_id' => Installment::factory()->create(['user_saving_id' => $second->id])->id, 'status' => 'approved']);
        $outsider = Payment::factory()->create(['installment_id' => Installment::factory()->create(['user_saving_id' => UserSaving::factory()->create(['user_id' => $other->id])->id])->id]);
        $this->actingAs($admin);

        Livewire::test(ManagePayments::class)->assertCanSeeTableRecords([$member, $other])
            ->assertCanNotSeeTableRecords([$admin, $emptyMember])->assertCountTableRecords(2);
        $this->get(PaymentResource::getUrl('detail', ['record' => $member]))->assertOk()
            ->assertSee('Kambing Pilihan')->assertSee('Paket Sembako')->assertSee('Paket Belum Dibayar');
        Livewire::test(ViewMemberPayments::class, ['record' => $member->id])
            ->assertCanSeeTableRecords([$payment, $approved])->assertCanNotSeeTableRecords([$outsider])
            ->assertCountTableRecords(2)->callTableAction('reject', $payment, ['reason' => 'Bukti kurang jelas'])
            ->assertHasNoTableActionErrors();
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame('pending', $outsider->fresh()->status);
    }

    public function test_list_summarizes_paid_and_unpaid_savings_including_handover(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $paid = UserSaving::factory()->create(['remaining_amount' => 0, 'status' => 'handover_requested']);
        $unpaid = UserSaving::factory()->create(['remaining_amount' => 100000]);
        $mixed = User::factory()->create();
        UserSaving::factory()->create(['user_id' => $mixed->id, 'remaining_amount' => 0, 'status' => 'completed']);
        UserSaving::factory()->create(['user_id' => $mixed->id, 'remaining_amount' => 250000]);
        $allPaid = User::factory()->create();
        UserSaving::factory()->count(2)->create(['user_id' => $allPaid->id, 'remaining_amount' => 0, 'status' => 'paid']);

        Livewire::test(ManagePayments::class)
            ->assertTableColumnStateSet('saving_status', 'Lunas', $paid->user)
            ->assertTableColumnStateSet('saving_status', 'Belum lunas', $unpaid->user)
            ->assertTableColumnStateSet('saving_status', '1 lunas, 1 belum lunas', $mixed)
            ->assertTableColumnStateSet('saving_status', '2 lunas, 0 belum lunas', $allPaid);
    }

    public function test_member_cannot_access_admin_payment_details(): void
    {
        $saving = UserSaving::factory()->create();
        $this->actingAs($saving->user)->get(PaymentResource::getUrl('detail', ['record' => $saving->user_id]))->assertForbidden();
    }
}
