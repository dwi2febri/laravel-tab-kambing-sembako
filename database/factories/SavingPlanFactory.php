<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SavingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavingPlanFactory extends Factory
{
    protected $model = SavingPlan::class;

    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'name' => '12 bulan', 'months' => 12, 'monthly_amount' => null, 'down_payment' => 0, 'admin_fee' => 0, 'is_active' => true];
    }
}
