<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSellingProducts extends TableWidget
{
    protected static ?string $heading = 'Produk Terlaris';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Product::query()
                    ->select('products.*')
                    ->selectRaw('COALESCE(SUM(CASE WHEN t.status = ? THEN ti.quantity ELSE 0 END), 0) as total_sold', [
                        TransactionStatus::Completed->value,
                    ])
                    ->leftJoin('transaction_items as ti', 'ti.product_id', '=', 'products.id')
                    ->leftJoin('transactions as t', 'ti.transaction_id', '=', 't.id')
                    ->groupBy('products.id')
                    ->orderByDesc('total_sold')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->color('gray'),
                TextColumn::make('total_sold')
                    ->label('Terjual')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->color(fn (Product $record): string => $record->is_low_stock ? 'danger' : 'gray'),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
