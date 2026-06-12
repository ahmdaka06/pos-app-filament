<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsOverview extends BaseWidget
{
    public static function todaySalesTotal(): float
    {
        return (float) Transaction::where('status', TransactionStatus::Completed)
            ->whereDate('created_at', now()->toDateString())
            ->sum('grand_total');
    }

    public static function todayTransactionCount(): int
    {
        return Transaction::where('status', TransactionStatus::Completed)
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    protected function getStats(): array
    {
        $count = self::todayTransactionCount();
        $total = self::todaySalesTotal();
        $aov = $count > 0 ? $total / $count : 0;
        $lowStock = Product::lowStock()->count();

        return [
            Stat::make('Penjualan Hari Ini', 'Rp '.number_format($total, 0, ',', '.')),
            Stat::make('Transaksi Hari Ini', (string) $count),
            Stat::make('Rata-rata Transaksi', 'Rp '.number_format($aov, 0, ',', '.')),
            Stat::make('Produk Stok Rendah', (string) $lowStock)
                ->color($lowStock > 0 ? 'danger' : 'success'),
        ];
    }
}
