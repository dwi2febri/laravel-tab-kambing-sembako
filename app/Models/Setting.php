<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('app.settings'));
        static::deleted(fn () => Cache::forget('app.settings'));
    }

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        $settings = Cache::remember('app.settings', 3600, fn () => static::pluck('value', 'key')->all());

        return filled($settings[$key] ?? null) ? $settings[$key] : $default;
    }
}
