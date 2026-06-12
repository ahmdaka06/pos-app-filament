<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateSaleAction;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSaleActionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'customer_id' => null,
            'discount_total' => 0,
            'paid_amount' => 100000,
            'note' => null,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_creates_sale_reduces_stock_and_records_sale_movement(): void
    {
        StoreSetting::create(['store_name' => 'T', 'tax_percent' => 0, 'invoice_prefix' => 'INV']);
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);

        $tx = app(CreateSaleAction::class)->execute($this->payload($product), $cashier, null);

        $this->assertSame(8, $product->fresh()->stock);
        $this->assertEquals(20000, (float) $tx->grand_total);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Sale->value,
            'quantity' => -2,
        ]);
        $this->assertStringStartsWith('INV-', $tx->invoice_number);
    }

    public function test_idempotency_key_returns_same_transaction(): void
    {
        StoreSetting::create(['store_name' => 'T', 'tax_percent' => 0, 'invoice_prefix' => 'INV']);
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);

        $first = app(CreateSaleAction::class)->execute($this->payload($product), $cashier, 'KEY-1');
        $second = app(CreateSaleAction::class)->execute($this->payload($product), $cashier, 'KEY-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_throws_when_insufficient_stock(): void
    {
        StoreSetting::create(['store_name' => 'T', 'tax_percent' => 0, 'invoice_prefix' => 'INV']);
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 1, 'allow_backorder' => false]);

        $this->expectException(InsufficientStockException::class);

        app(CreateSaleAction::class)->execute($this->payload($product), $cashier, null);
    }
}
