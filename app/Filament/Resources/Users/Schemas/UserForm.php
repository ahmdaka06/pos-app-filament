<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->hiddenOn('edit'),
                TextInput::make('new_password')
                    ->label('New Password')
                    ->password()
                    ->hiddenOn('create'),
                Select::make('role')
                    ->options(UserRole::class)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
