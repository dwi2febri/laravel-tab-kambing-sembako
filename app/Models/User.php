<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone', 'address', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => 'user', 'is_active' => true];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' && $this->is_active;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function savings(): HasMany
    {
        return $this->hasMany(UserSaving::class);
    }

    public function memberNotifications(): HasMany
    {
        return $this->hasMany(MemberNotification::class);
    }
}
