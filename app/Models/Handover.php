<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Handover extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function userSaving(): BelongsTo
    {
        return $this->belongsTo(UserSaving::class, 'user_saving_id');
    }

    protected function casts(): array
    {
        return ['preferred_at' => 'datetime', 'requested_at' => 'datetime', 'scheduled_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
