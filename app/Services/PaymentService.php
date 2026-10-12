<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserSaving;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function submit(User $user, Installment $installment, int $methodId, UploadedFile $proof, ?string $note): Payment
    {
        $path = $proof->store('payment-proofs', 'local');
        try {
            return DB::transaction(function () use ($user, $installment, $methodId, $path, $note) {
                $saving = UserSaving::lockForUpdate()->findOrFail($installment->user_saving_id);
                Gate::forUser($user)->authorize('view', $saving);
                $installment = Installment::lockForUpdate()->findOrFail($installment->id);
                if ($saving->status !== 'active' || ! in_array($installment->status, ['unpaid', 'late'], true) || $installment->payments()->whereIn('status', ['pending', 'approved'])->exists()) {
                    throw ValidationException::withMessages(['proof' => 'Cicilan sudah dibayar atau sedang diverifikasi.']);
                }
                PaymentMethod::where('is_active', true)->findOrFail($methodId);
                $payment = Payment::create(['user_id' => $user->id, 'user_saving_id' => $saving->id, 'installment_id' => $installment->id, 'payment_method_id' => $methodId, 'amount' => $installment->amount, 'proof_path' => $path, 'note' => $note, 'status' => 'pending']);
                $installment->update(['status' => 'pending']);
                SavingService::notify($user->id, 'payment', 'Bukti pembayaran diterima', 'Pembayaran sedang menunggu verifikasi admin.');
                SavingService::notifyAdmins('payment', 'Pembayaran menunggu verifikasi', $user->name.' mengirim pembayaran untuk '.$saving->product_name_snapshot.' sebesar Rp '.number_format($payment->amount, 0, ',', '.').'.');
                SavingService::log($user, 'payment.submitted', 'Bukti untuk pembayaran #'.$payment->id);

                return $payment;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function review(User $admin, Payment $payment, bool $approved, ?string $reason = null): Payment
    {
        Gate::forUser($admin)->authorize('review', $payment);
        if (! $approved && ! trim($reason ?? '')) {
            throw ValidationException::withMessages(['reason' => 'Alasan penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($admin, $payment, $approved, $reason) {
            $saving = UserSaving::lockForUpdate()->findOrFail($payment->user_saving_id);
            $installment = Installment::lockForUpdate()->findOrFail($payment->installment_id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah diproses.']);
            }
            if ($approved) {
                if ($installment->status === 'paid' || $installment->payments()->where('status', 'approved')->exists()) {
                    throw ValidationException::withMessages(['payment' => 'Cicilan sudah lunas.']);
                }
                if ((int) $payment->amount !== (int) $installment->amount) {
                    throw ValidationException::withMessages(['payment' => 'Nominal pembayaran tidak cocok dengan cicilan.']);
                }
                $payment->update(['status' => 'approved', 'approved_by' => $admin->id, 'approved_at' => now()]);
                $installment->update(['status' => 'paid', 'paid_amount' => $installment->amount, 'paid_at' => now()]);
                $total = (int) $saving->payments()->where('status', 'approved')->sum('amount');
                $productPaid = max(0, $total - ($saving->installments()->where('installment_number', 0)->where('status', 'paid')->exists() ? $saving->admin_fee_snapshot : 0));
                $lunas = $productPaid >= $saving->product_price_snapshot;
                $saving->update(['total_paid' => $productPaid, 'remaining_amount' => max(0, $saving->product_price_snapshot - $productPaid), 'status' => $lunas ? 'paid' : 'active']);
                SavingService::notify($saving->user_id, 'payment', 'Pembayaran disetujui', 'Pembayaran cicilan #'.$installment->installment_number.' telah masuk ke tabungan.');
                if ($lunas) {
                    SavingService::notifyAdmins('userSaving', 'Tabungan anggota lunas', $saving->user->name.' telah melunasi '.$saving->product_name_snapshot.'.');
                    SavingService::log($admin, 'saving.paid', 'Tabungan #'.$saving->id.' lunas.');
                    SavingService::notify($saving->user_id, 'userSaving', 'Selamat, tabungan lunas!', 'Ajukan serah terima '.$saving->product_name_snapshot.'.');
                }
            } else {
                $payment->update(['status' => 'rejected', 'rejected_by' => $admin->id, 'rejected_at' => now(), 'rejection_reason' => $reason]);
                $installment->update(['status' => $installment->due_date->lt(today()) ? 'late' : 'unpaid']);
                SavingService::notify($saving->user_id, 'payment', 'Pembayaran perlu diperbaiki', $reason);
            }
            SavingService::log($admin, 'payment.'.($approved ? 'approved' : 'rejected'), 'Pembayaran #'.$payment->id.($reason ? ': '.$reason : ''));

            return $payment->refresh();
        });
    }
}
