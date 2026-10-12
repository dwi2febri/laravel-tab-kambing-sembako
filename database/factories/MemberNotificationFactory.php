<?php

namespace Database\Factories;

use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberNotificationFactory extends Factory
{
    protected $model = MemberNotification::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'type' => 'payment', 'title' => 'Pembayaran diterima', 'message' => 'Menunggu verifikasi.'];
    }
}
