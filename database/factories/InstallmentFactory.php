<?php

namespace Database\Factories;

use App\Models\Installment;
use App\Models\UserSaving;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstallmentFactory extends Factory
{
    protected $model = Installment::class;

    public function definition(): array
    {
        return ['user_saving_id' => UserSaving::factory(), 'installment_number' => 1, 'due_date' => today()->addMonth(), 'amount' => 208334, 'paid_amount' => 0, 'status' => 'unpaid'];
    }
}
