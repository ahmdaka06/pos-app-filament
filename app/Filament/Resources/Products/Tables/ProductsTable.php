<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price')
                    ->money('IDR')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->suffix(' IDR')
                    ->sortable(),
                TextColumn::make('cost_price')
                    ->money('IDR')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->suffix(' IDR')
                    ->sortable(),
                TextColumn::make('unit')
                    ->searchable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->is_low_stock ? 'danger' : null),
                TextColumn::make('reorder_level')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('track_stock')
                    ->boolean(),
                IconColumn::make('allow_backorder')
                    ->boolean(),
                ImageColumn::make('image_path'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                Filter::make('low_stock')
                    ->query(fn ($q) => $q->lowStock()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('adjustStock')
                    ->form([
                        TextInput::make('quantity')
                            ->numeric()
                            ->required(),
                        TextInput::make('reason')
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        app(StockService::class)->adjust(
                            $record,
                            (int) $data['quantity'],
                            $data['reason'],
                            auth()->user(),
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
