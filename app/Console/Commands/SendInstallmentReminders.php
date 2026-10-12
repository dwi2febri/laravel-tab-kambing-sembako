<?php

namespace App\Console\Commands;

use App\Models\Installment;
use App\Models\MemberNotification;
use App\Models\Setting;
use App\Services\SavingService;
use Illuminate\Console\Command;

class SendInstallmentReminders extends Command
{
    protected $signature = 'savings:remind';

    protected $description = 'Update overdue installments and send one due-date reminder per installment per day';

    public function handle(): int
    {
        $days = max(0, (int) (Setting::where('key', 'due_reminder_days')->value('value') ?? 3));
        $grace = max(0, (int) (Setting::where('key', 'grace_period')->value('value') ?? 0));
        Installment::where('status', 'unpaid')->whereDate('due_date', '<', today()->subDays($grace))->update(['status' => 'late']);
        Installment::with('userSaving')->whereIn('status', ['unpaid', 'late'])->whereDate('due_date', '<=', today()->addDays($days))->whereHas('userSaving', fn ($q) => $q->where('status', 'active'))->chunkById(100, function ($installments) {
            foreach ($installments as $i) {
                $key = 'due-'.$i->id.'-'.today()->format('Y-m-d');
                if (MemberNotification::where('type', $key)->exists()) {
                    continue;
                }SavingService::notify($i->userSaving->user_id, $key, 'Pengingat cicilan', 'Cicilan '.$i->userSaving->product_name_snapshot.' jatuh tempo '.$i->due_date->translatedFormat('d M Y').'.');
            }
        });
        $this->info('Pengingat jatuh tempo selesai.');

        return self::SUCCESS;
    }
}
