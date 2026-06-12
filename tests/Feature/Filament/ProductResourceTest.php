<?php

namespace Tests\Feature\Filament;

use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_products_list(): void
    {
        $owner = User::factory()->owner()->create();
        Product::factory()->count(2)->create();

        $this->actingAs($owner)->get('/admin/products')->assertOk();
    }

    public function test_adjust_stock_via_service_updates_stock_and_logs_movement(): void
    {
        $product = Product::factory()->create(['stock' => 4]);

        app(StockService::class)->adjust($product, 6, 'restock');

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 6,
        ]);
    }
}
