<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SavingPlan;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SavingService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $goat = Category::firstOrCreate(['slug' => 'kambing'], ['name' => 'Kambing', 'type' => 'goat']);
        $grocery = Category::firstOrCreate(['slug' => 'sembako'], ['name' => 'Sembako', 'type' => 'grocery']);
        $products = [
            ['Kambing Etawa Jantan', 'kambing-etawa-jantan', 2500000, 'goat', true],
            ['Paket Sembako Keluarga', 'paket-sembako-keluarga', 250000, 'grocery', true],
            ['Kambing Etawa Betina', 'kambing-etawa-betina', 2000000, 'goat', true],
            ['Paket Sembako Lengkap', 'paket-sembako-lengkap', 350000, 'grocery', true],
            ['Kambing Kacang', 'kambing-kacang', 1500000, 'goat', false],
            ['Paket Sembako Premium', 'paket-sembako-premium', 500000, 'grocery', false],
        ];
        foreach ($products as [$name,$slug,$price,$type,$featured]) {
            $product = Product::firstOrCreate(['slug' => $slug], ['name' => $name, 'category_id' => $type === 'goat' ? $goat->id : $grocery->id, 'description' => $type === 'goat' ? 'Kambing sehat dengan perawatan teratur dan pakan berkualitas. Cocok untuk kebutuhan keluarga, kurban, maupun ternak. Foto merupakan ilustrasi; kondisi aktual dikonfirmasi saat serah terima.' : 'Paket kebutuhan dapur keluarga yang disusun dengan bahan pilihan. Isi paket tercantum di bawah; foto merupakan ilustrasi.', 'price' => $price, 'stock' => 25, 'breed' => $type === 'goat' ? ($slug === 'kambing-kacang' ? 'Kacang' : 'Etawa') : null, 'gender' => $type === 'goat' ? (str_contains($slug, 'betina') ? 'Betina' : 'Jantan') : null, 'age_month' => $type === 'goat' ? 12 : null, 'weight_min' => $type === 'goat' ? 25 : null, 'weight_max' => $type === 'goat' ? 35 : null, 'is_active' => true, 'is_featured' => $featured]);
            foreach ($type === 'goat' ? [12, 18, 24] : [3, 6, 12] as $months) {
                SavingPlan::firstOrCreate(['product_id' => $product->id, 'months' => $months], ['name' => $months.' bulan', 'monthly_amount' => null, 'is_active' => true]);
            }
            if ($type === 'grocery') {
                foreach ([['Beras', 5, 'kg'], ['Minyak Goreng', 1, 'liter'], ['Gula Pasir', 1, 'kg'], ['Tepung Terigu', 1, 'kg'], ['Mi Instan', 5, 'pcs'], ['Telur', 1, 'kg']] as [$item,$qty,$unit]) {
                    $product->items()->firstOrCreate(['item_name' => $item], ['qty' => $qty, 'unit' => $unit]);
                }
            }
        }
        PaymentMethod::firstOrCreate(['account_number' => '0000000000'], ['name' => 'Transfer bank (contoh)', 'bank_name' => 'Bank Contoh — rekening demo', 'account_holder' => 'Tabungan Kambing & Sembako (Demo)', 'is_active' => true]);
        foreach (['brand_name' => 'Tabungan Kambing & Sembako', 'whatsapp' => '', 'contact_email' => '', 'due_reminder_days' => '3', 'grace_period' => '0', 'late_fee' => '0', 'terms' => ''] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        if (app()->environment('production')) {
            return;
        }
        $admin = User::firstOrCreate(['email' => 'admin@tabungan.test'], ['name' => 'Admin Tabungan', 'password' => 'Admin123!', 'phone' => '081234567890']);
        $admin->forceFill(['role' => 'admin'])->save();
        $user = User::firstOrCreate(['email' => 'andi@tabungan.test'], ['name' => 'Andi Saputra', 'password' => 'Andi123!', 'phone' => '081234567891', 'address' => 'Semarang, Jawa Tengah']);
        if ($user->savings()->exists()) {
            return;
        }
        foreach ([['kambing-etawa-jantan', 4], ['paket-sembako-keluarga', 3]] as [$slug,$paidMonths]) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $plan = $product->plans()->where('months', $slug === 'kambing-etawa-jantan' ? 12 : 6)->firstOrFail();
            $saving = app(SavingService::class)->start($user, $product, $plan);
            $saving->update(['start_date' => today()->subMonths(4), 'estimated_end_date' => today()->addMonths($plan->months - 4)]);
            foreach ($saving->installments as $installment) {
                $installment->update(['due_date' => today()->subMonths(4)->addMonthsNoOverflow($installment->installment_number)]);
                if ($installment->installment_number <= $paidMonths) {
                    $proof = new UploadedFile(public_path('images/demo-proof.svg'), 'bukti-demo.svg', 'image/svg+xml', null, true);
                    $payment = app(PaymentService::class)->submit($user, $installment, PaymentMethod::first()->id, $proof, 'Data contoh untuk pengembangan lokal');
                    app(PaymentService::class)->review($admin, $payment, true);
                } elseif ($installment->installment_number === $paidMonths + 1) {
                    $proof = new UploadedFile(public_path('images/demo-proof.svg'), 'bukti-demo.svg', 'image/svg+xml', null, true);
                    app(PaymentService::class)->submit($user, $installment, PaymentMethod::first()->id, $proof, 'Contoh pembayaran menunggu verifikasi');
                }
            }
        }
    }
}
