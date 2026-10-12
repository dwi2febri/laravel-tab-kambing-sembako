<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SavingPlan;
use App\Models\User;
use App\Models\UserSaving;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSavingFactory extends Factory
{
    protected $model = UserSaving::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'product_id' => Product::factory(), 'saving_plan_id' => fn (array $attributes) => SavingPlan::factory()->create(['product_id' => $attributes['product_id']])->id, 'product_name_snapshot' => 'Kambing Etawa', 'product_price_snapshot' => 2500000, 'tenor_snapshot' => 12, 'monthly_amount_snapshot' => 208334, 'total_paid' => 0, 'remaining_amount' => 2500000, 'start_date' => today(), 'estimated_end_date' => today()->addMonths(12), 'status' => 'active'];
    }
}
