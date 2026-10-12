<?php

namespace Database\Factories;

use App\Models\Handover;
use App\Models\UserSaving;
use Illuminate\Database\Eloquent\Factories\Factory;

class HandoverFactory extends Factory
{
    protected $model = Handover::class;

    public function definition(): array
    {
        return ['user_saving_id' => UserSaving::factory()->state(['status' => 'paid', 'total_paid' => 2500000, 'remaining_amount' => 0]), 'recipient_name' => fake()->name(), 'requested_at' => now(), 'status' => 'requested'];
    }
}
