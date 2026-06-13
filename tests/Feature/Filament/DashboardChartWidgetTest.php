<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\SalesChart;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardChartWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_chart_only_counts_completed_transactions(): void
    {
        $user = User::factory()->create();
        $pm = PaymentMethod::factory()->create();

        Transaction::create([
            'invoice_number' => 'INV-A',
            'user_id' => $user->id,
            'payment_method_id' => $pm->id,
            'status' => 'completed',
            'grand_total' => 50000,
        ]);

        Transaction::create([
            'invoice_number' => 'INV-B',
            'user_id' => $user->id,
            'payment_method_id' => $pm->id,
            'status' => 'void',
            'grand_total' => 99999,
        ]);

        $data = SalesChart::getSalesByDay(7);
        $today = now()->toDateString();

        $this->assertSame(50000.0, $data[$today] ?? 0.0);
    }

    public function test_sales_chart_returns_zero_for_days_with_no_sales(): void
    {
        $data = SalesChart::getSalesByDay(7);

        $this->assertEmpty($data);
    }

    public function test_top_selling_products_widget_renders(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get('/admin')
            ->assertOk();
    }

    public function test_top_selling_products_orders_by_total_sold(): void
    {
        $user = User::factory()->create();
        $pm = PaymentMethod::factory()->create();

        $productA = Product::factory()->create(['name' => 'Produk A', 'stock' => 100]);
        $productB = Product::factory()->create(['name' => 'Produk B', 'stock' => 100]);

        $tx = Transaction::create([
            'invoice_number' => 'INV-T1',
            'user_id' => $user->id,
            'payment_method_id' => $pm->id,
            'status' => 'completed',
            'grand_total' => 30000,
        ]);

        TransactionItem::create([
            'transaction_id' => $tx->id,
            'product_id' => $productA->id,
            'product_name' => $productA->name,
            'sku' => $productA->sku,
            'price' => $productA->price,
            'quantity' => 5,
            'discount' => 0,
            'line_total' => 5 * (float) $productA->price,
        ]);

        TransactionItem::create([
            'transaction_id' => $tx->id,
            'product_id' => $productB->id,
            'product_name' => $productB->name,
            'sku' => $productB->sku,
            'price' => $productB->price,
            'quantity' => 2,
            'discount' => 0,
            'line_total' => 2 * (float) $productB->price,
        ]);

        $results = Product::query()
            ->select('products.*')
            ->selectRaw('COALESCE(SUM(CASE WHEN t.status = ? THEN ti.quantity ELSE 0 END), 0) as total_sold', [
                'completed',
            ])
            ->leftJoin('transaction_items as ti', 'ti.product_id', '=', 'products.id')
            ->leftJoin('transactions as t', 'ti.transaction_id', '=', 't.id')
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        $this->assertSame($productA->id, $results->first()->id);
        $this->assertSame(5, (int) $results->first()->total_sold);
    }
}
