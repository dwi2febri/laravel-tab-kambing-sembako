<?php

namespace Tests\Feature;

use App\Filament\Resources\PaymentMethods\Pages\ManagePaymentMethods;
use App\Filament\Resources\Payments\Pages\ViewMemberPayments;
use App\Models\Installment;
use App\Models\MemberNotification;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SavingPlan;
use App\Models\User;
use App\Models\UserSaving;
use App\Services\HandoverService;
use App\Services\PaymentService;
use App\Services\SavingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SavingsFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $admin;

    private Product $product;

    private SavingPlan $plan;

    private PaymentMethod $method;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->member = User::factory()->create(['phone' => '08123456']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->product = Product::factory()->create();
        $this->plan = SavingPlan::factory()->create(['product_id' => $this->product->id, 'months' => 3]);
        $this->method = PaymentMethod::factory()->create();
    }

    private function start(): UserSaving
    {
        return app(SavingService::class)->start($this->member, $this->product, $this->plan);
    }

    private function submit(Installment $installment): Payment
    {
        return app(PaymentService::class)->submit($this->member, $installment, $this->method->id, UploadedFile::fake()->image('bukti.png'), null);
    }

    public function test_public_pages_and_product_filters_render(): void
    {
        foreach (['home', 'catalog', 'login', 'register', 'password.request', 'about', 'terms'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('products.show', $this->product))->assertOk()->assertSee('Mulai Menabung');
        $this->get(route('catalog', ['q' => 'Etawa', 'type' => 'goat']))->assertOk()->assertSee($this->product->name);
        $this->get(route('catalog', ['q' => 'tidakada']))->assertOk()->assertSee('Produk belum ditemukan');
    }

    public function test_registration_cannot_assign_admin_role(): void
    {
        $this->post(route('register.submit'), ['name' => 'Anggota Baru', 'email' => 'baru@example.test', 'phone' => '0811', 'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'terms' => 1, 'role' => 'admin', 'is_active' => false])->assertRedirect(route('dashboard'));
        $user = User::where('email', 'baru@example.test')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_inactive_member_cannot_login_or_view_dashboard(): void
    {
        $this->member->update(['is_active' => false]);
        $this->post(route('login.submit'), ['email' => $this->member->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($this->member)->get(route('dashboard'))->assertForbidden();
    }

    public function test_saving_snapshots_price_and_generates_exact_total(): void
    {
        $saving = $this->start();
        $this->assertCount(3, $saving->installments);
        $this->assertSame(2500000, (int) $saving->installments->sum('amount'));
        $this->assertSame(19, $this->product->fresh()->stock);
        $this->product->update(['price' => 9999999]);
        $this->plan->update(['months' => 12]);
        $this->assertSame(2500000, (int) $saving->fresh()->product_price_snapshot);
        $this->assertSame(3, $saving->fresh()->tenor_snapshot);
    }

    public function test_invalid_or_unavailable_plan_cannot_create_saving(): void
    {
        $wrong = SavingPlan::factory()->create();
        $this->actingAs($this->member)->post(route('savings.start', $this->product), ['saving_plan_id' => $wrong->id])->assertSessionHasErrors('saving_plan_id');
        $this->product->update(['stock' => 0]);
        $this->post(route('savings.start', $this->product), ['saving_plan_id' => $this->plan->id])->assertSessionHasErrors('saving_plan_id');
        $this->assertDatabaseCount('user_savings', 0);
    }

    public function test_upload_ignores_forged_amount_and_does_not_increase_balance(): void
    {
        $saving = $this->start();
        $installment = $saving->installments->first();
        $this->actingAs($this->member)->post(route('payments.store', $installment), ['payment_method_id' => $this->method->id, 'amount' => 1, 'status' => 'approved', 'proof' => UploadedFile::fake()->image('bukti.png')])->assertRedirect(route('savings.show', $saving));
        $payment = Payment::firstOrFail();
        $this->assertSame((int) $installment->amount, (int) $payment->amount);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(0, $saving->fresh()->total_paid);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->post(route('payments.store', $installment), ['payment_method_id' => $this->method->id, 'proof' => UploadedFile::fake()->image('bukti.png')])->assertSessionHasErrors('proof');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_upload_rejects_wrong_mime_and_oversize(): void
    {
        $i = $this->start()->installments->first();
        foreach ([UploadedFile::fake()->create('evil.html', 20, 'text/html'), UploadedFile::fake()->create('big.pdf', 5121, 'application/pdf')] as $proof) {
            $this->actingAs($this->member)->post(route('payments.store', $i), ['payment_method_id' => $this->method->id, 'proof' => $proof])->assertSessionHasErrors('proof');
        }
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_members_cannot_read_other_savings_or_proofs(): void
    {
        $saving = $this->start();
        $payment = $this->submit($saving->installments->first());
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('savings.show', $saving))->assertForbidden();
        $this->get(route('payments.proof', $payment))->assertForbidden();
        $this->get(route('payments.create', $payment->installment))->assertForbidden();
        $this->actingAs($this->member)->get(route('payments.proof', $payment))->assertOk();
        $this->actingAs($this->admin)->get(route('payments.proof', $payment))->assertOk();
    }

    public function test_admin_approval_updates_balance_once_and_prevents_retry(): void
    {
        $saving = $this->start();
        $p = $this->submit($saving->installments->first());
        app(PaymentService::class)->review($this->admin, $p, true);
        $this->assertSame('approved', $p->fresh()->status);
        $this->assertSame('paid', $p->installment->fresh()->status);
        $this->assertSame((int) $p->amount, (int) $saving->fresh()->total_paid);
        try {
            app(PaymentService::class)->review($this->admin, $p, true);
            $this->fail('Double approval accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment', $e->errors());
        }
        $this->assertSame((int) $p->amount, (int) $saving->fresh()->total_paid);
    }

    public function test_member_cannot_approve_payment(): void
    {
        $p = $this->submit($this->start()->installments->first());
        $this->expectException(AuthorizationException::class);
        app(PaymentService::class)->review($this->member, $p, true);
    }

    public function test_rejection_needs_reason_and_allows_new_attempt(): void
    {
        $saving = $this->start();
        $i = $saving->installments->first();
        $p = $this->submit($i);
        try {
            app(PaymentService::class)->review($this->admin, $p, false, '');
            $this->fail('Missing reason accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }
        app(PaymentService::class)->review($this->admin, $p, false, 'Bukti kurang jelas');
        $this->assertSame('rejected', $p->fresh()->status);
        $this->assertSame(0, $saving->fresh()->total_paid);
        $this->assertSame('unpaid', $i->fresh()->status);
        $retry = $this->submit($i->fresh());
        app(PaymentService::class)->review($this->admin, $retry, true);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame((int) $i->amount, (int) $saving->fresh()->total_paid);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->member->id, 'message' => 'Bukti kurang jelas']);
    }

    public function test_fully_paid_savings_can_complete_handover(): void
    {
        $saving = $this->start();
        $service = app(HandoverService::class);
        $this->actingAs($this->member)->post(route('handovers.request', $saving), ['recipient_name' => 'Andi'])->assertSessionHasErrors('handover');
        foreach ($saving->installments as $i) {
            app(PaymentService::class)->review($this->admin, $this->submit($i), true);
        }
        $saving->refresh();
        $this->assertSame('paid', $saving->status);
        $this->assertSame(0, $saving->remaining_amount);
        $h = $service->request($this->member, $saving, 'Andi', null);
        $this->assertSame('handover_requested', $saving->fresh()->status);
        $service->schedule($this->admin, $h, now()->addDay()->toDateTimeString(), 'Kantor Semarang');
        $this->assertSame('handover_scheduled', $saving->fresh()->status);
        $service->complete($this->admin, $h);
        $this->assertSame('completed', $saving->fresh()->status);
        $this->assertNotNull($h->fresh()->completed_at);
        foreach (['Tabungan baru', 'Pembayaran menunggu verifikasi', 'Tabungan anggota lunas', 'Pengajuan serah terima baru', 'Serah terima dijadwalkan', 'Serah terima selesai'] as $title) {
            $this->assertDatabaseHas('notifications', ['user_id' => $this->admin->id, 'title' => $title]);
        }
        $this->assertDatabaseHas('notifications', ['user_id' => $this->member->id, 'title' => 'Pengajuan serah terima diterima']);

    }

    public function test_down_payment_and_admin_fee_do_not_inflate_progress(): void
    {
        $this->plan->update(['down_payment' => 100000, 'admin_fee' => 15000]);
        $saving = $this->start();
        $this->assertCount(4, $saving->installments);
        $this->assertSame(2515000, (int) $saving->installments->sum('amount'));
        $dp = $saving->installments->first();
        app(PaymentService::class)->review($this->admin, $this->submit($dp), true);
        $this->assertSame(100000, (int) $saving->fresh()->total_paid);
        foreach ($saving->installments->skip(1) as $i) {
            app(PaymentService::class)->review($this->admin, $this->submit($i), true);
        }
        $this->assertSame('paid', $saving->fresh()->status);
        $this->assertSame(2500000, (int) $saving->fresh()->total_paid);
    }

    public function test_authenticated_member_pages_render(): void
    {
        $saving = $this->start();
        $this->actingAs($this->member);
        foreach (['dashboard', 'savings.index', 'payments.index', 'notifications', 'profile'] as $r) {
            $this->get(route($r))->assertOk();
        }
        $this->get(route('savings.show', $saving))->assertOk();
        $this->get(route('payments.create', $saving->installments->first()))->assertOk();
    }

    public function test_admin_panel_and_master_pages_render_and_deny_members(): void
    {
        $this->actingAs($this->admin);
        foreach (['', 'products', 'categories', 'saving-plans', 'product-items', 'product-images', 'payment-methods', 'payments', 'user-savings', 'handovers', 'users', 'settings', 'activity-logs', 'reports'] as $path) {
            $this->get('/admin'.($path ? '/'.$path : ''))->assertOk();
        }
        $this->actingAs($this->member);
        foreach (['', 'products', 'payment-methods', 'payments', 'reports'] as $path) {
            $this->get('/admin'.($path ? '/'.$path : ''))->assertForbidden();
        }
        $this->get(route('reports.preview'))->assertForbidden();
        $this->get(route('reports.export', ['format' => 'xlsx']))->assertForbidden();
    }

    public function test_admin_can_approve_from_filament_table(): void
    {
        $p = $this->submit($this->start()->installments->first());
        $this->actingAs($this->admin);
        Livewire::test(ViewMemberPayments::class, ['record' => $p->user_id])->callTableAction('approve', $p)->assertHasNoTableActionErrors();
        $this->assertSame('approved', $p->fresh()->status);
    }

    public function test_admin_can_manage_accounts_displayed_to_members(): void
    {
        $installment = $this->start()->installments->first();
        $this->actingAs($this->admin);
        $data = ['name' => 'Rekening utama', 'bank_name' => 'Bank Pengujian', 'account_number' => '001234567890', 'account_holder' => 'Pengelola Tabungan', 'is_active' => true];
        Livewire::test(ManagePaymentMethods::class)->callAction('create', data: $data)->assertHasNoActionErrors();
        $account = PaymentMethod::where('name', 'Rekening utama')->firstOrFail();
        $this->assertSame('001234567890', $account->account_number);
        $this->actingAs($this->member)->get(route('payments.create', $installment))->assertOk()->assertSee('Bank Pengujian')->assertSee('001234567890')->assertSee('Pengelola Tabungan');

        $this->actingAs($this->admin);
        $data['account_number'] = '009876543210';
        Livewire::test(ManagePaymentMethods::class)->callTableAction('edit', $account, data: $data)->assertHasNoTableActionErrors();
        $this->actingAs($this->member)->get(route('payments.create', $installment))->assertOk()->assertSee('009876543210')->assertDontSee('001234567890');

        $this->actingAs($this->admin);
        $data['is_active'] = false;
        Livewire::test(ManagePaymentMethods::class)->callTableAction('edit', $account, data: $data)->assertHasNoTableActionErrors();
        $this->actingAs($this->member)->get(route('payments.create', $installment))->assertOk()->assertDontSee('009876543210');
        $this->post(route('payments.store', $installment), ['payment_method_id' => $account->id, 'proof' => UploadedFile::fake()->image('bukti.png')])->assertSessionHasErrors('payment_method_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_reports_export_real_pdf_and_excel_with_filters(): void
    {
        $p = $this->submit($this->start()->installments->first());
        $this->actingAs($this->admin);
        $this->get(route('reports.preview', ['status' => 'approved']))->assertOk()->assertSee('0 catatan');
        $pdf = $this->get(route('reports.export', ['format' => 'pdf', 'status' => 'pending']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $xlsx = $this->get(route('reports.export', ['format' => 'xlsx', 'status' => 'pending']))->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());
        foreach (['savings', 'products', 'handovers', 'users'] as $type) {
            $this->get(route('reports.preview', ['type' => $type]))->assertOk();
        }
    }

    public function test_reminders_are_idempotent_and_backup_is_private(): void
    {
        $saving = $this->start();
        $saving->installments->first()->update(['due_date' => today()->subDay()]);
        $this->artisan('savings:remind')->assertSuccessful();
        $count = MemberNotification::count();
        $this->artisan('savings:remind')->assertSuccessful();
        $this->assertSame($count, MemberNotification::count());
        $this->assertSame('late', $saving->installments->first()->fresh()->status);
        $this->artisan('app:backup')->assertSuccessful();
        $this->assertCount(1, Storage::disk('local')->files('backups'));
    }

    public function test_profile_cannot_change_role_or_active_state(): void
    {
        $this->actingAs($this->member)->put(route('profile.update'), ['name' => 'Andi', 'phone' => '0812', 'role' => 'admin', 'is_active' => false])->assertSessionHasNoErrors();
        $this->assertSame('user', $this->member->fresh()->role);
        $this->assertTrue($this->member->fresh()->is_active);
    }
}
