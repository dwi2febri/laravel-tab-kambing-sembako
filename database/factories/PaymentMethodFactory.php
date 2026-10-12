<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return ['name' => 'Transfer', 'bank_name' => 'Bank Demo', 'account_number' => fake()->numerify('##########'), 'account_holder' => 'Pengelola', 'is_active' => true];
    }
}
