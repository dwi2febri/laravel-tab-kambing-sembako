<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\MemberNotification;
use App\Models\Product;
use App\Models\SavingPlan;
use App\Models\User;
use App\Models\UserSaving;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingService
{
    public function start(User $user, Product $product, SavingPlan $plan): UserSaving
    {
        return DB::transaction(function () use ($user, $product, $plan) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $plan = SavingPlan::lockForUpdate()->findOrFail($plan->id);
            if (! $user->is_active || ! $product->is_active || ! $plan->is_active || (int) $plan->product_id !== (int) $product->id || $product->stock < 1) {
                throw ValidationException::withMessages(['saving_plan_id' => 'Produk atau tenor tidak tersedia.']);
            }
            if ($plan->months < 1 || $plan->down_payment >= $product->price) {
                throw ValidationException::withMessages(['saving_plan_id' => 'Konfigurasi tenor tidak valid.']);
            }
            $monthly = $plan->monthlyAmount();
            $remaining = $product->price - $plan->down_payment;
            if ($monthly < 1 || $monthly * ($plan->months - 1) >= $remaining) {
                throw ValidationException::withMessages(['saving_plan_id' => 'Nominal cicilan tidak sesuai harga dan tenor.']);
            }
            $saving = UserSaving::create(['user_id' => $user->id, 'product_id' => $product->id, 'saving_plan_id' => $plan->id, 'product_name_snapshot' => $product->name, 'product_price_snapshot' => $product->price, 'tenor_snapshot' => $plan->months, 'monthly_amount_snapshot' => $monthly, 'down_payment_snapshot' => $plan->down_payment, 'admin_fee_snapshot' => $plan->admin_fee, 'total_paid' => 0, 'remaining_amount' => $product->price, 'start_date' => today(), 'estimated_end_date' => today()->addMonthsNoOverflow($plan->months), 'status' => 'active']);
            if ($plan->down_payment + $plan->admin_fee > 0) {
                $saving->installments()->create(['installment_number' => 0, 'due_date' => today(), 'amount' => $plan->down_payment + $plan->admin_fee]);
            }
            for ($i = 1; $i <= $plan->months; $i++) {
                $amount = $i === $plan->months ? $remaining : $monthly;
                $saving->installments()->create(['installment_number' => $i, 'due_date' => today()->addMonthsNoOverflow($i), 'amount' => $amount]);
                $remaining -= $amount;
            }
            $product->decrement('stock');
            self::notifyAdmins('userSaving', 'Tabungan baru', $user->name.' mulai menabung '.$product->name.'.');
            self::log($user, 'saving.created', 'Tabungan #'.$saving->id.' dimulai.');
            self::notify($user->id, 'userSaving', 'Tabungan berhasil dibuat', 'Mulai langkah kecil untuk '.$product->name.'.');

            return $saving;
        });
    }

    public static function log(User $user, string $action, string $description): void
    {
        ActivityLog::create(['user_id' => $user->id, 'action' => $action, 'description' => $description, 'ip_address' => request()?->ip(), 'user_agent' => mb_substr(request()?->userAgent() ?? '', 0, 1000)]);
    }

    public static function notifyAdmins(string $type, string $title, string $message): void
    {
        User::where('role', 'admin')->where('is_active', true)->each(function (User $admin) use ($type, $title, $message): void {
            self::notify($admin->id, $type, $title, $message);
        });
    }

    public static function notify(int $userId, string $type, string $title, string $message): void
    {
        MemberNotification::create(['user_id' => $userId, 'type' => $type, 'title' => $title, 'message' => $message]);
    }
}
