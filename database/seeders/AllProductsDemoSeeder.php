<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Services\SavingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllProductsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Data demo hanya boleh dibuat di lingkungan lokal atau pengujian.');
        }

        if (User::where('email', 'rina.semua@tabungan.test')->exists()) {
            $this->command?->info('Anggota demo sudah tersedia; tidak membuat data ganda.');

            return;
        }

        DB::transaction(function (): void {
            $products = Product::where('is_active', true)->orderBy('id')->get();
            if ($products->isEmpty()) {
                throw new \RuntimeException('Belum ada produk aktif.');
            }

            $user = User::create([
                'name' => 'Rina Pratiwi (Demo)',
                'email' => 'rina.semua@tabungan.test',
                'password' => 'RinaDemo123!',
                'phone' => '080000000003',
                'address' => 'Alamat contoh untuk pengujian semua produk',
                'role' => 'user',
                'is_active' => true,
            ]);

            foreach ($products as $product) {
                $plan = $product->plans()->where('is_active', true)->orderBy('months')->firstOrFail();
                app(SavingService::class)->start($user, $product, $plan);
            }

            if ($user->savings()->count() !== $products->count()) {
                throw new \RuntimeException('Jumlah tabungan belum sesuai jumlah produk.');
            }

            $this->command?->info('Anggota: '.$user->name.' | Tabungan aktif: '.$user->savings()->count());
            foreach ($user->savings()->get() as $saving) {
                $this->command?->info($saving->product_name_snapshot.' | '.$saving->tenor_snapshot.' bulan | Rp '.number_format($saving->product_price_snapshot, 0, ',', '.'));
            }
        });
    }
}
