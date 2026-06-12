<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Date Range
        </x-slot>
        <x-slot name="description">
            Filter transactions by period
        </x-slot>

        <div class="flex flex-wrap gap-4 items-end">
            <div class="w-48">
                <x-filament::input.wrapper>
                    <x-filament::input.index type="date" wire:model.live="from" />
                </x-filament::input.wrapper>
            </div>
            <div class="w-48">
                <x-filament::input.wrapper>
                    <x-filament::input.index type="date" wire:model.live="to" />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray" color="gray">
                Export CSV
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- Summary Cards --}}
    <div class="grid gap-6 md:grid-cols-3">
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-document-text" class="size-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Transactions</p>
                    <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ number_format($this->totalTransactions) }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-lg bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-400">
                    <x-filament::icon icon="heroicon-o-currency-dollar" class="size-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                    <p class="text-2xl font-bold tracking-tight text-success-600 dark:text-success-400">
                        Rp {{ number_format($this->totalRevenue, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="flex size-12 items-center justify-center rounded-lg bg-info-100 text-info-600 dark:bg-info-900/30 dark:text-info-400">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="size-6" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Average Transaction</p>
                    <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Rp {{ number_format($this->averageTransaction, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Transactions Table --}}
    <x-filament::section>
        <x-slot name="heading">
            Transactions
        </x-slot>
        <x-slot name="description">
            {{ $this->totalTransactions }} transaction(s) found
        </x-slot>

        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900">
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Invoice</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Amount</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->transactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $transaction->invoice_number }}
                            </td>
                            <td class="px-4 py-3 text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $transaction->created_at->format('d M Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-inbox" class="size-8 text-gray-400" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        No transactions found in this period.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
