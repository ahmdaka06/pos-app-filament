<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateSaleAction;
use App\Actions\RefundTransactionAction;
use App\Actions\VoidTransactionAction;
use App\Enums\TransactionStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoidRefundActionTest extends TestCase
{
    use RefreshDatabase;

    private function makeSale(Product $product, User $cashier)
    {
        return app(CreateSaleAction::class)->execute([
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'customer_id' => null,
            'discount_total' => 0,
            'paid_amount' => 100000,
            'note' => null,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'discount' => 0]],
        ], $cashier, null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        StoreSetting::create(['store_name' => 'T', 'tax_percent' => 0, 'invoice_prefix' => 'INV']);
    }

    public function test_void_restocks_and_sets_status(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $tx = $this->makeSale($product, $cashier);
        $this->assertSame(8, $product->fresh()->stock);

        app(VoidTransactionAction::class)->execute($tx, $manager);

        $this->assertSame(TransactionStatus::Void, $tx->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertNotNull($tx->fresh()->voided_at);
    }

    public function test_refund_restocks_and_sets_status(): void
    {
        $manager = User::factory()->manager()->create();
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $tx = $this->makeSale($product, $cashier);

        app(RefundTransactionAction::class)->execute($tx, $manager);

        $this->assertSame(TransactionStatus::Refunded, $tx->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
    }
}
