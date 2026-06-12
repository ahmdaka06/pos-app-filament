<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('barcode'),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->required(),
                TextInput::make('cost_price')
                    ->numeric(),
                TextInput::make('unit')
                    ->default('pcs'),
                TextInput::make('reorder_level')
                    ->numeric()
                    ->default(0),
                Toggle::make('track_stock')
                    ->default(true),
                Toggle::make('allow_backorder')
                    ->default(false),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('products'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
