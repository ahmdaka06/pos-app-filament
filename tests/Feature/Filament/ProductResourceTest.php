<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_create_product_form_has_stock_field(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Stock');
    }

    public function test_creating_product_with_stock_logs_purchase_movement(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create(['is_active' => true]);

        Livewire::actingAs($owner)
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'sku' => 'INV-TEST',
                'name' => 'Test Product',
                'price' => 10000,
                'stock' => 50,
                'track_stock' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('sku', 'INV-TEST')->first();
        $this->assertNotNull($product);
        $this->assertSame(50, $product->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 50,
            'stock_before' => 0,
            'stock_after' => 50,
        ]);
    }

    public function test_creating_product_with_zero_stock_does_not_log_movement(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create(['is_active' => true]);

        Livewire::actingAs($owner)
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'sku' => 'INV-ZERO',
                'name' => 'Zero Stock Product',
                'price' => 5000,
                'stock' => 0,
                'track_stock' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('sku', 'INV-ZERO')->first();
        $this->assertNotNull($product);
        $this->assertSame(0, $product->stock);

        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
        ]);
    }

    public function test_updating_product_stock_logs_adjustment_movement(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'track_stock' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm([
                'category_id' => $product->category_id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => 25,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(25, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 15,
            'stock_before' => 10,
            'stock_after' => 25,
        ]);
    }

    public function test_updating_product_without_stock_change_does_not_log_movement(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'track_stock' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(EditProduct::class, ['record' => $product->getKey()])
            ->fillForm([
                'category_id' => $product->category_id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => 10,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(10, $product->fresh()->stock);

        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
        ]);
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
