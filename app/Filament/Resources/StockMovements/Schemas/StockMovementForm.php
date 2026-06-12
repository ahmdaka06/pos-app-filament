<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Enums\StockMovementType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('type')
                    ->options(StockMovementType::class)
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('stock_before')
                    ->required()
                    ->numeric(),
                TextInput::make('stock_after')
                    ->required()
                    ->numeric(),
                TextInput::make('reference_type'),
                TextInput::make('reference_id')
                    ->numeric(),
                TextInput::make('note'),
            ]);
    }
}
