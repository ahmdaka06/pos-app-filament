<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function applyMovement(
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?Model $reference = null,
        ?string $note = null,
        ?User $user = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $reference, $note, $user) {
            $locked = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            $before = $locked->stock;
            $after = $before + $quantity;

            if ($after < 0 && $locked->track_stock && ! $locked->allow_backorder) {
                throw new InsufficientStockException($locked, abs($quantity));
            }

            $movement = new StockMovement([
                'type' => $type,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'note' => $note,
            ]);
            $movement->product()->associate($locked);
            if ($user) {
                $movement->user()->associate($user);
            }
            if ($reference) {
                $movement->reference()->associate($reference);
            }
            $movement->save();

            $locked->stock = $after;
            $locked->save();

            $product->setAttribute('stock', $after);

            return $movement;
        });
    }

    public function adjust(Product $product, int $quantity, string $reason, ?User $user = null): StockMovement
    {
        return $this->applyMovement($product, StockMovementType::Adjustment, $quantity, null, $reason, $user);
    }
}
