<?php

namespace Tests\Feature\Api;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        StoreSetting::create(['store_name' => 'T', 'tax_percent' => 0, 'invoice_prefix' => 'INV']);
    }

    private function payload(Product $product): array
    {
        return [
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'paid_amount' => 100000,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];
    }

    public function test_create_sale_reduces_stock(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);

        $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/v1/transactions', $this->payload($product), ['Idempotency-Key' => 'ABC'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'completed');

        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_repeated_idempotency_key_does_not_duplicate(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);

        $headers = ['Idempotency-Key' => 'SAME'];
        $first = $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/transactions', $this->payload($product), $headers);
        $second = $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/transactions', $this->payload($product), $headers);

        $first->assertCreated();
        $second->assertOk();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_insufficient_stock_returns_422(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 1, 'allow_backorder' => false]);

        $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/v1/transactions', $this->payload($product), ['Idempotency-Key' => 'X'])
            ->assertStatus(422);
    }

    public function test_cashier_cannot_void(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);
        $create = $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/transactions', $this->payload($product), ['Idempotency-Key' => 'V']);
        $id = $create->json('data.id');

        $this->actingAs($cashier, 'sanctum')
            ->postJson("/api/v1/transactions/{$id}/void")
            ->assertStatus(403);
    }

    public function test_manager_can_void_and_restock(): void
    {
        $cashier = User::factory()->cashier()->create();
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);
        $create = $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/transactions', $this->payload($product), ['Idempotency-Key' => 'V2']);
        $id = $create->json('data.id');

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/transactions/{$id}/void")
            ->assertOk()
            ->assertJsonPath('data.status', 'void');

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_mine_filter_lists_only_own_transactions(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);
        $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/transactions', $this->payload($product), ['Idempotency-Key' => 'M']);

        $this->actingAs($cashier, 'sanctum')
            ->getJson('/api/v1/transactions?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }
}
