<?php

namespace Database\Factories;

use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\UserSaving;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return ['installment_id' => Installment::factory(), 'user_saving_id' => fn (array $a) => Installment::findOrFail($a['installment_id'])->user_saving_id, 'user_id' => fn (array $a) => UserSaving::findOrFail($a['user_saving_id'])->user_id, 'payment_method_id' => PaymentMethod::factory(), 'amount' => fn (array $a) => Installment::findOrFail($a['installment_id'])->amount, 'proof_path' => 'payment-proofs/example.jpg', 'status' => 'pending'];
    }
}
