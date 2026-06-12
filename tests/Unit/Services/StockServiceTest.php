<?php

namespace Tests\Unit\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_negative_movement_reduces_stock_and_records_movement(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'track_stock' => true]);

        $movement = app(StockService::class)->applyMovement($product, StockMovementType::Sale, -3);

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame(10, $movement->stock_before);
        $this->assertSame(7, $movement->stock_after);
        $this->assertSame(-3, $movement->quantity);
        $this->assertSame(StockMovementType::Sale, $movement->type);
    }

    public function test_apply_positive_movement_increases_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        app(StockService::class)->applyMovement($product, StockMovementType::Refund, 2);

        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_throws_when_stock_insufficient(): void
    {
        $this->expectException(InsufficientStockException::class);

        $product = Product::factory()->create(['stock' => 1, 'track_stock' => true, 'allow_backorder' => false]);

        app(StockService::class)->applyMovement($product, StockMovementType::Sale, -5);
    }

    public function test_allows_negative_when_backorder_enabled(): void
    {
        $product = Product::factory()->create(['stock' => 1, 'allow_backorder' => true]);

        app(StockService::class)->applyMovement($product, StockMovementType::Sale, -5);

        $this->assertSame(-4, $product->fresh()->stock);
    }

    public function test_adjust_creates_adjustment_movement(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $movement = app(StockService::class)->adjust($product, 3, 'restock');

        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(StockMovementType::Adjustment, $movement->type);
        $this->assertSame('restock', $movement->note);
    }
}
