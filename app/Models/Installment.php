<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function userSaving(): BelongsTo
    {
        return $this->belongsTo(UserSaving::class, 'user_saving_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return ['due_date' => 'date', 'paid_at' => 'datetime'];
    }
}
