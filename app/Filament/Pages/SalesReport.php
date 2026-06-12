<?php

namespace App\Filament\Pages;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Sales Report';

    protected static ?string $title = 'Sales Report';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.sales-report';

    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function getTransactionsProperty(): Collection
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        return Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTotalRevenueProperty(): float
    {
        return $this->transactions->sum('grand_total');
    }

    public function getTotalTransactionsProperty(): int
    {
        return $this->transactions->count();
    }

    public function getAverageTransactionProperty(): float
    {
        return $this->totalTransactions > 0
            ? $this->totalRevenue / $this->totalTransactions
            : 0;
    }

    public static function buildCsv(CarbonInterface $from, CarbonInterface $to): string
    {
        $rows = Transaction::query()
            ->where('status', TransactionStatus::Completed)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get(['invoice_number', 'grand_total', 'created_at']);

        $lines = ['invoice_number,grand_total,created_at'];
        foreach ($rows as $row) {
            $lines[] = "{$row->invoice_number},{$row->grand_total},{$row->created_at->toDateTimeString()}";
        }

        return implode("\n", $lines)."\n";
    }

    public function exportCsv(): StreamedResponse
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();
        $csv = self::buildCsv($from, $to);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'sales-report.csv', ['Content-Type' => 'text/csv']);
    }
}
