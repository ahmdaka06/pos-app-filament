<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\StockService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected int $originalStock = 0;

    protected ?int $newStockValue = null;

    protected function fillForm(): void
    {
        parent::fillForm();
        $this->originalStock = (int) ($this->getRecord()->stock ?? 0);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->newStockValue = (int) ($data['stock'] ?? $this->originalStock);
        unset($data['stock']);

        return $data;
    }

    protected function afterSave(): void
    {
        $diff = $this->newStockValue - $this->originalStock;

        if ($diff === 0 || ! $this->getRecord()->track_stock) {
            return;
        }

        app(StockService::class)->adjust(
            product: $this->getRecord(),
            quantity: $diff,
            reason: 'Manual adjustment via product edit',
            user: auth()->user(),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
