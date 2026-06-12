<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\SalesStatsOverview;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_sales_counts_only_completed(): void
    {
        $user = User::factory()->create();
        $pm = PaymentMethod::factory()->create();
        Transaction::create(['invoice_number' => 'A', 'user_id' => $user->id, 'payment_method_id' => $pm->id, 'status' => 'completed', 'grand_total' => 15000]);
        Transaction::create(['invoice_number' => 'B', 'user_id' => $user->id, 'payment_method_id' => $pm->id, 'status' => 'refunded', 'grand_total' => 99999]);

        $this->assertSame(15000.0, SalesStatsOverview::todaySalesTotal());
        $this->assertSame(1, SalesStatsOverview::todayTransactionCount());
    }
}
