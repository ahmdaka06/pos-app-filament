<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateSaleFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        StoreSetting::create(['store_name' => 'Test', 'tax_percent' => 0, 'invoice_prefix' => 'INV']);
    }

    public function test_cashier_can_access_create_sale_page(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get('/admin/transactions/create')
            ->assertOk();
    }

    public function test_manager_can_access_create_sale_page(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/admin/transactions/create')
            ->assertOk();
    }

    public function test_create_sale_via_form_submission(): void
    {
        $cashier = User::factory()->cashier()->create();
        $pm = PaymentMethod::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 10000]);

        $this->actingAs($cashier);

        Livewire::test(CreateTransaction::class)
            ->fillForm([
                'payment_method_id' => $pm->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'discount' => 0],
                ],
                'paid_amount' => 50000,
                'discount_total' => 0,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(8, $product->fresh()->stock);
    }
}
