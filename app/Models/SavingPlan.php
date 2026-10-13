<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingPlan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function monthlyAmount(): int
    {
        if ($this->financed_admin_fee) {
            return (int) ceil(($this->product->price + $this->admin_fee) / max(1, $this->months));
        }

        return $this->monthly_amount ?? (int) ceil(($this->product->price - $this->down_payment) / $this->months);
    }
}
