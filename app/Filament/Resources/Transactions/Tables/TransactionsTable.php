<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Actions\RefundTransactionAction;
use App\Actions\VoidTransactionAction;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('paymentMethod.name')
                    ->label('Payment')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('grand_total')
                    ->money('IDR')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.'))
                    ->suffix(' IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TransactionStatus::class),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                Action::make('void')
                    ->requiresConfirmation()
                    ->visible(fn (Transaction $record) => auth()->user()->can('void', $record) && $record->status === TransactionStatus::Completed)
                    ->action(fn (Transaction $record) => app(VoidTransactionAction::class)->execute($record, auth()->user())),
                Action::make('refund')
                    ->requiresConfirmation()
                    ->visible(fn (Transaction $record) => auth()->user()->can('refund', $record) && $record->status === TransactionStatus::Completed)
                    ->action(fn (Transaction $record) => app(RefundTransactionAction::class)->execute($record, auth()->user())),
            ]);
    }
}
