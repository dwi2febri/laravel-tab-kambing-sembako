<?php

namespace App\Services;

use App\Models\Handover;
use App\Models\User;
use App\Models\UserSaving;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HandoverService
{
    /** @param array{preferred_at?: string, location?: string, latitude?: numeric-string, longitude?: numeric-string} $details */
    public function request(User $user, UserSaving $saving, string $recipient, ?string $note, array $details = []): Handover
    {
        return DB::transaction(function () use ($user, $saving, $recipient, $note, $details) {
            $saving = UserSaving::lockForUpdate()->findOrFail($saving->id);
            Gate::forUser($user)->authorize('view', $saving);
            abort_unless($user->role === 'user', 403);
            if ($saving->status !== 'paid' && ! ($saving->status === 'handover_requested' && $saving->handover?->status === 'requested' && ! $saving->handover->preferred_at && isset($details['preferred_at']))) {
                throw ValidationException::withMessages(['handover' => 'Tabungan harus lunas sebelum mengajukan penyerahan.']);
            }
            $handover = Handover::updateOrCreate(['user_saving_id' => $saving->id], ['recipient_name' => $recipient, 'note' => $note, 'requested_at' => now(), 'status' => 'requested', 'preferred_at' => $details['preferred_at'] ?? null, 'location' => $details['location'] ?? null, 'latitude' => $details['latitude'] ?? null, 'longitude' => $details['longitude'] ?? null]);
            $saving->update(['status' => 'handover_requested']);
            SavingService::notifyAdmins('handover', 'Pengajuan serah terima baru', $user->name.' mengajukan penyerahan '.$saving->product_name_snapshot.'.');
            SavingService::notify($user->id, 'handover', 'Pengajuan serah terima diterima', 'Pengajuan '.$saving->product_name_snapshot.' menunggu konfirmasi admin.');
            SavingService::log($user, 'handover.requested', 'Penyerahan tabungan #'.$saving->id);

            return $handover;
        });
    }

    /** @param array{recipient_name: string, preferred_at: string, location: string, latitude: numeric-string, longitude: numeric-string, note?: string|null} $data */
    public function update(User $user, UserSaving $saving, array $data): Handover
    {
        return DB::transaction(function () use ($user, $saving, $data): Handover {
            $saving = UserSaving::lockForUpdate()->findOrFail($saving->id);
            Gate::forUser($user)->authorize('view', $saving);
            abort_unless($user->role === 'user', 403);
            $handover = $saving->handover()->lockForUpdate()->firstOrFail();
            if ($saving->status !== 'handover_requested' || $handover->status !== 'requested') {
                throw ValidationException::withMessages(['handover' => 'Pengajuan yang sudah dikonfirmasi tidak dapat diubah.']);
            }
            $handover->update([
                'recipient_name' => $data['recipient_name'], 'preferred_at' => $data['preferred_at'],
                'location' => $data['location'], 'latitude' => $data['latitude'], 'longitude' => $data['longitude'],
                'note' => $data['note'] ?? null,
            ]);
            SavingService::notifyAdmins('handover', 'Pengajuan serah terima diperbarui', $user->name.' memperbarui jadwal atau lokasi penyerahan '.$saving->product_name_snapshot.'.');
            SavingService::log($user, 'handover.updated', 'Perubahan pengajuan penyerahan #'.$handover->id);

            return $handover;
        });
    }

    public function schedule(User $admin, Handover $handover, string $date, string $location): void
    {
        Gate::forUser($admin)->authorize('admin');
        DB::transaction(function () use ($admin, $handover, $date, $location) {
            $saving = UserSaving::lockForUpdate()->findOrFail($handover->user_saving_id);
            $handover = Handover::lockForUpdate()->findOrFail($handover->id);
            if ($handover->status !== 'requested' || $saving->status !== 'handover_requested') {
                throw ValidationException::withMessages(['handover' => 'Penyerahan sudah dijadwalkan.']);
            }
            if ($handover->preferred_at) {
                $date = $handover->preferred_at->format('Y-m-d H:i:s');
                $location = $handover->location;
            }
            $handover->update(['status' => 'scheduled', 'scheduled_at' => $date, 'location' => $location, 'handled_by' => $admin->id]);
            $handover->userSaving()->update(['status' => 'handover_scheduled']);
            SavingService::notify($handover->userSaving->user_id, 'handover', 'Serah terima dijadwalkan', 'Pengajuan dikonfirmasi. Produk dalam proses penyerahan pada '.$date.' di '.$location.'. Tekan Terima setelah produk benar-benar diterima.');
            SavingService::notifyAdmins('handover', 'Serah terima dijadwalkan', $handover->userSaving->user->name.' - '.$handover->userSaving->product_name_snapshot.' pada '.$date.' di '.$location);
            SavingService::log($admin, 'handover.scheduled', 'Penyerahan #'.$handover->id);
        });
    }

    public function complete(User $buyer, Handover $handover): void
    {
        DB::transaction(function () use ($buyer, $handover): void {
            $saving = UserSaving::lockForUpdate()->findOrFail($handover->user_saving_id);
            Gate::forUser($buyer)->authorize('view', $saving);
            abort_unless($buyer->role === 'user', 403);
            $handover = Handover::lockForUpdate()->findOrFail($handover->id);
            if ($handover->status !== 'scheduled' || $saving->status !== 'handover_scheduled') {
                throw ValidationException::withMessages(['handover' => 'Produk hanya dapat diterima setelah pengajuan dikonfirmasi admin.']);
            }
            $handover->update(['status' => 'completed', 'completed_at' => now()]);
            $saving->update(['status' => 'completed']);
            SavingService::notify($buyer->id, 'handover', 'Serah terima selesai', 'Penerimaan produk telah Anda konfirmasi. Terima kasih telah menabung bersama kami.');
            SavingService::notifyAdmins('handover', 'Serah terima selesai', $buyer->name.' mengonfirmasi penerimaan '.$saving->product_name_snapshot.'.');
            SavingService::log($buyer, 'handover.completed', 'Pembeli mengonfirmasi penerimaan produk pada penyerahan #'.$handover->id);
        });
    }
}
