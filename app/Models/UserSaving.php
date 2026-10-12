<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserSaving extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SavingPlan::class, 'saving_plan_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('installment_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function handover(): HasOne
    {
        return $this->hasOne(Handover::class);
    }

    public function progress(): int
    {
        return min(100, (int) round($this->total_paid / max(1, $this->product_price_snapshot) * 100));
    }

    protected function casts(): array
    {
        return ['start_date' => 'date', 'estimated_end_date' => 'date'];
    }
}
