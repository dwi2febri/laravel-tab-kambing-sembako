<?php

namespace Tests\Feature;

use App\Filament\Resources\Payments\Pages\ViewMemberPayments;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentProofModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Storage::fake('local');
    }

    public function test_proof_opens_image_modal_without_changing_payment(): void
    {
        $payment = Payment::factory()->create();
        Storage::disk('local')->put($payment->proof_path, UploadedFile::fake()->image('proof.jpg')->getContent());
        Livewire::test(ViewMemberPayments::class, ['record' => $payment->user_id])->mountTableAction('proof', $payment)
            ->assertMountedActionModalSee('Bukti Pembayaran #'.$payment->id)
            ->assertMountedActionModalSee('payment-proof-image', false)
            ->assertMountedActionModalSee(route('payments.proof', $payment), false)
            ->assertMountedActionModalSee($payment->user->name);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->get(route('payments.proof', $payment))->assertOk();
    }

    public function test_pdf_proof_has_embedded_preview_and_full_size_link(): void
    {
        $payment = Payment::factory()->create(['proof_path' => 'payment-proofs/proof.pdf']);
        Storage::disk('local')->put($payment->proof_path, '%PDF-1.4');
        Livewire::test(ViewMemberPayments::class, ['record' => $payment->user_id])->mountTableAction('proof', $payment)
            ->assertMountedActionModalSee('application/pdf', false)->assertMountedActionModalSee('Buka berkas ukuran penuh');
    }

    public function test_missing_proof_shows_clear_message(): void
    {
        $payment = Payment::factory()->create();
        Livewire::test(ViewMemberPayments::class, ['record' => $payment->user_id])->mountTableAction('proof', $payment)
            ->assertMountedActionModalSee('Berkas bukti pembayaran tidak tersedia.');
    }
}
