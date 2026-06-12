<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SalesReport;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_contains_only_completed_rows(): void
    {
        $user = User::factory()->create();
        $pm = PaymentMethod::factory()->create();
        Transaction::create(['invoice_number' => 'INV-OK', 'user_id' => $user->id, 'payment_method_id' => $pm->id, 'status' => 'completed', 'grand_total' => 12000]);
        Transaction::create(['invoice_number' => 'INV-VOID', 'user_id' => $user->id, 'payment_method_id' => $pm->id, 'status' => 'void', 'grand_total' => 5000]);

        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $csv = SalesReport::buildCsv($from, $to);

        $this->assertStringContainsString('INV-OK', $csv);
        $this->assertStringNotContainsString('INV-VOID', $csv);
    }
}
