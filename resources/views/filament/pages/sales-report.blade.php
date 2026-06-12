<x-filament-panels::page>
    <div class="flex gap-4 items-end">
        <div>
            <label>From</label>
            <input type="date" wire:model="from" class="fi-input block w-full rounded-lg border" />
        </div>
        <div>
            <label>To</label>
            <input type="date" wire:model="to" class="fi-input block w-full rounded-lg border" />
        </div>
        <x-filament::button wire:click="exportCsv">Export CSV</x-filament::button>
    </div>
</x-filament-panels::page>
