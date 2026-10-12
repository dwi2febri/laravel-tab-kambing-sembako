<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\HandoverService;
use App\Services\PaymentService;
use App\Services\SavingService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PaidGoatDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Data demo hanya boleh dibuat di lingkungan lokal atau pengujian.');
        }

        if (User::where('email', 'budi.lunas@tabungan.test')->exists()) {
            $this->command?->info('Anggota demo sudah tersedia; tidak membuat data ganda.');

            return;
        }

        DB::transaction(function (): void {
            $admin = User::where('role', 'admin')->where('is_active', true)->firstOrFail();
            $product = Product::where('is_active', true)->where('stock', '>', 0)
                ->whereHas('category', fn ($query) => $query->where('type', 'goat'))
                ->whereHas('plans', fn ($query) => $query->where('is_active', true))->orderBy('id')->firstOrFail();
            $plan = $product->plans()->where('is_active', true)->orderBy('months')->firstOrFail();
            $method = PaymentMethod::where('is_active', true)->firstOrFail();
            $user = User::create([
                'name' => 'Budi Santoso (Demo)',
                'email' => 'budi.lunas@tabungan.test',
                'password' => 'BudiDemo123!',
                'phone' => '080000000002',
                'address' => 'Alamat contoh untuk pengujian serah terima',
                'role' => 'user',
                'is_active' => true,
            ]);
            $saving = app(SavingService::class)->start($user, $product, $plan);
            $startDate = today()->subMonthsNoOverflow($plan->months);
            $saving->update(['start_date' => $startDate, 'estimated_end_date' => today()]);
            foreach ($saving->installments as $installment) {
                $installment->update(['due_date' => $startDate->copy()->addMonthsNoOverflow($installment->installment_number)]);
                $proof = new UploadedFile(public_path('images/demo-proof.svg'), 'bukti-demo.svg', 'image/svg+xml', null, true);
                $payment = app(PaymentService::class)->submit($user, $installment, $method->id, $proof, 'DATA DUMMY: simulasi pelunasan kambing, bukan transfer nyata.');
                app(PaymentService::class)->review($admin, $payment, true);
            }
            $saving->refresh();
            if ($saving->status !== 'paid' || (int) $saving->remaining_amount !== 0) {
                throw new \RuntimeException('Tabungan demo belum lunas.');
            }
            $handover = app(HandoverService::class)->request($user, $saving, $user->name, 'DATA DUMMY: kambing lunas dan siap dijadwalkan serah terima.');
            $this->command?->info('Anggota: '.$user->name.' | Produk: '.$product->name.' | Lunas: Rp '.number_format($saving->total_paid, 0, ',', '.').' | Sisa: Rp 0 | Serah terima #'.$handover->id.': '.$handover->status);
        });
    }
}
