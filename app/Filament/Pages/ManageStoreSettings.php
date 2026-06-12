<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageStoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.manage-store-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = StoreSetting::query()->firstOrCreate([]);
        $this->form->fill($setting->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('store_name')->required(),
                Textarea::make('address'),
                FileUpload::make('logo_path')->image()->disk('public')->directory('store'),
                TextInput::make('currency')->default('IDR')->required(),
                TextInput::make('tax_percent')->numeric()->default(0),
                TextInput::make('invoice_prefix')->default('INV')->required(),
                Textarea::make('receipt_footer'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $setting = StoreSetting::query()->firstOrCreate([]);
        $setting->update($data);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([$this->getSaveAction()])
                            ->key('form-actions'),
                    ]),
            ]);
    }

    public function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('Save'))
            ->submit('save');
    }
}
