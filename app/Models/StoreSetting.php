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

    public const CACHE_KEY = 'store_settings';

    public static function current(): self
    {
        $attributes = Cache::remember(self::CACHE_KEY, 3600, function () {
            return self::query()->first()?->toArray() ?? [
                'store_name' => 'My Store',
                'currency' => 'IDR',
                'tax_percent' => 0,
                'invoice_prefix' => 'INV',
            ];
        });

        $instance = new self;
        $instance->setRawAttributes($attributes);
        $instance->exists = true;

        return $instance;
    }
}
