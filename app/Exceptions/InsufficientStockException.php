<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly Product $product, public readonly int $requested)
    {
        parent::__construct("Stok tidak mencukupi untuk {$product->sku}. Tersedia: {$product->stock}");
    }
}
