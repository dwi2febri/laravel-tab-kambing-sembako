<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function review(User $user, Payment $payment): bool
    {
        return $user->is_active && $user->role === 'admin';
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->is_active && ($user->role === 'admin' || $payment->user_id === $user->id);
    }
}
