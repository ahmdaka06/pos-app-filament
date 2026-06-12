<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\StockMovementType;
use App\Filament\Resources\Products\ProductResource;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $product = $this->getRecord();
        $stock = (int) ($this->data['stock'] ?? 0);

        if ($stock <= 0 || ! $product->track_stock) {
            return;
        }

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => StockMovementType::Purchase,
            'quantity' => $stock,
            'stock_before' => 0,
            'stock_after' => $stock,
            'note' => 'Initial stock on product creation',
        ]);
    }
}
