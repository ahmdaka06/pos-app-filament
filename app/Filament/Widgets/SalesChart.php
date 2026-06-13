<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Penjualan';

    public ?string $filter = '7';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 Hari Terakhir',
            '30' => '30 Hari Terakhir',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 7);
        $start = Carbon::today()->subDays($days - 1);

        $salesByDay = Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('d M');
            $data[] = (float) ($salesByDay[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $data,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Get sales data for a given number of days (used in tests).
     *
     * @return array<string, float>
     */
    public static function getSalesByDay(int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);

        return Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}
