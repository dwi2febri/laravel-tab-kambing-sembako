<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(SavingPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function savings(): HasMany
    {
        return $this->hasMany(UserSaving::class);
    }

    public function imageUrl(): string
    {
        return $this->thumbnail ? asset('storage/'.$this->thumbnail) : asset('images/'.($this->category?->type === 'goat' ? 'goat.webp' : 'grocery.webp'));
    }
}
