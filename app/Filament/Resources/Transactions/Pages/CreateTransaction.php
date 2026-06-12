<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Actions\CreateSaleAction;
use App\Exceptions\InsufficientStockException;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class CreateTransaction extends Page
{
    protected static string $resource = TransactionResource::class;

    protected ?string $heading = 'Create Sale';

    protected string $view = 'filament.resources.transactions.pages.create-transaction';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->relationship('paymentMethod', 'name')
                    ->required(),
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Repeater::make('items')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn () => Product::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        TextInput::make('discount')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->minItems(1)
                    ->required()
                    ->columns(3),
                TextInput::make('discount_total')
                    ->label('Order Discount')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                TextInput::make('paid_amount')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                Textarea::make('note')
                    ->nullable(),
            ])
            ->statePath('data')
            ->model(Transaction::class);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            $transaction = app(CreateSaleAction::class)->execute(
                $data,
                auth()->user(),
                null,
            );

            Notification::make()
                ->title('Sale created successfully')
                ->success()
                ->send();

            $this->redirect(TransactionResource::getUrl('view', ['record' => $transaction]));
        } catch (InsufficientStockException $e) {
            Notification::make()
                ->title('Insufficient stock')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Create Sale')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
