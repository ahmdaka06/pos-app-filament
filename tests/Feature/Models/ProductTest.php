<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_scope_returns_products_at_or_below_reorder_level(): void
    {
        Product::factory()->create(['stock' => 2, 'reorder_level' => 5]);
        Product::factory()->create(['stock' => 10, 'reorder_level' => 5]);

        $this->assertCount(1, Product::lowStock()->get());
    }

    public function test_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertTrue($product->category->is($category));
    }

    public function test_active_scope_excludes_inactive(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $this->assertCount(1, Product::active()->get());
    }
}
