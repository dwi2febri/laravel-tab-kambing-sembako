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
    public function request(User $user, UserSaving $saving, string $recipient, ?string $note): Handover
    {
        return DB::transaction(function () use ($user, $saving, $recipient, $note) {
            $saving = UserSaving::lockForUpdate()->findOrFail($saving->id);
            Gate::forUser($user)->authorize('view', $saving);
            if ($saving->status !== 'paid') {
                throw ValidationException::withMessages(['handover' => 'Tabungan harus lunas sebelum mengajukan penyerahan.']);
            }
            $handover = Handover::create(['user_saving_id' => $saving->id, 'recipient_name' => $recipient, 'note' => $note, 'requested_at' => now(), 'status' => 'requested']);
            $saving->update(['status' => 'handover_requested']);
            SavingService::notifyAdmins('handover', 'Pengajuan serah terima baru', $user->name.' mengajukan penyerahan '.$saving->product_name_snapshot.'.');
            SavingService::notify($user->id, 'handover', 'Pengajuan serah terima diterima', 'Pengajuan '.$saving->product_name_snapshot.' menunggu jadwal admin.');
            SavingService::log($user, 'handover.requested', 'Penyerahan tabungan #'.$saving->id);

            return $handover;
        });
    }

    public function schedule(User $admin, Handover $handover, string $date, string $location): void
    {
        Gate::forUser($admin)->authorize('admin');
        DB::transaction(function () use ($admin, $handover, $date, $location) {
            $handover = Handover::lockForUpdate()->findOrFail($handover->id);
            if ($handover->status !== 'requested') {
                throw ValidationException::withMessages(['handover' => 'Penyerahan sudah dijadwalkan.']);
            }
            $handover->update(['status' => 'scheduled', 'scheduled_at' => $date, 'location' => $location, 'handled_by' => $admin->id]);
            $handover->userSaving()->update(['status' => 'handover_scheduled']);
            SavingService::notify($handover->userSaving->user_id, 'handover', 'Serah terima dijadwalkan', $date.' di '.$location);
            SavingService::notifyAdmins('handover', 'Serah terima dijadwalkan', $handover->userSaving->user->name.' - '.$handover->userSaving->product_name_snapshot.' pada '.$date.' di '.$location);
            SavingService::log($admin, 'handover.scheduled', 'Penyerahan #'.$handover->id);
        });
    }

    public function complete(User $admin, Handover $handover): void
    {
        Gate::forUser($admin)->authorize('admin');
        DB::transaction(function () use ($admin, $handover) {
            $handover = Handover::lockForUpdate()->findOrFail($handover->id);
            if ($handover->status !== 'scheduled') {
                throw ValidationException::withMessages(['handover' => 'Penyerahan harus dijadwalkan terlebih dahulu.']);
            }
            $handover->update(['status' => 'completed', 'completed_at' => now(), 'handled_by' => $admin->id]);
            $handover->userSaving()->update(['status' => 'completed']);
            SavingService::notify($handover->userSaving->user_id, 'handover', 'Serah terima selesai', 'Terima kasih telah menabung bersama kami.');
            SavingService::notifyAdmins('handover', 'Serah terima selesai', $handover->recipient_name.' menerima '.$handover->userSaving->product_name_snapshot.'.');
            SavingService::log($admin, 'handover.completed', 'Penyerahan #'.$handover->id);
        });
    }
}
