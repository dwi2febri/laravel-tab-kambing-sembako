<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSaving;

class UserSavingPolicy
{
    public function view(User $user, UserSaving $saving): bool
    {
        return $user->is_active && $user->id === $saving->user_id;
    }
}
