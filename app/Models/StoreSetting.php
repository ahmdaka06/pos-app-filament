<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name', 'address', 'logo_path', 'currency',
        'tax_percent', 'invoice_prefix', 'receipt_footer',
    ];

    protected function casts(): array
    {
        return ['tax_percent' => 'decimal:2'];
    }

    public const CACHE_KEY = 'store_settings.current';

    public static function current(): self
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return self::query()->first() ?? new self([
                'store_name' => 'My Store',
                'currency' => 'IDR',
                'tax_percent' => 0,
                'invoice_prefix' => 'INV',
            ]);
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }
}
