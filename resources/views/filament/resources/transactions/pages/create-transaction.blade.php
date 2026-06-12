<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="flex gap-4">
            <x-filament::button type="submit">
                Create Sale
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
