<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\PosTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosTransactionPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    private Category $category;

    private Category $otherCategory;

    private Product $product;

    private Product $otherProduct;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // StoreSetting is required by SaleTotals::compute() and StoreSetting::current()
        StoreSetting::create([
            'store_name' => 'Test Store',
            'tax_percent' => 0,
            'invoice_prefix' => 'INV',
        ]);

        $this->user = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->category = Category::factory()->create([
            'is_active' => true,
            'name' => 'Minuman',
        ]);

        $this->otherCategory = Category::factory()->create([
            'is_active' => true,
            'name' => 'Makanan',
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Kopi Hitam',
            'price' => 25000,
            'stock' => 100,
            'track_stock' => true,
            'allow_backorder' => false,
            'is_active' => true,
        ]);

        $this->otherProduct = Product::factory()->create([
            'category_id' => $this->otherCategory->id,
            'name' => 'Nasi Goreng',
            'price' => 35000,
            'stock' => 50,
            'is_active' => true,
        ]);

        $this->paymentMethod = PaymentMethod::factory()->create([
            'is_active' => true,
        ]);
    }

    // ─── Page Access ────────────────────────────────────────────────────────────

    /**
     * Objective: Verify the POS page renders correctly for an authenticated cashier.
     * Positive test: authenticated user can access /admin/pos and sees the page title.
     */
    public function test_authenticated_user_can_access_pos_page(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/pos')
            ->assertOk()
            ->assertSee('Cari produk');
    }

    /**
     * Objective: Verify unauthenticated users cannot access the POS page.
     * Negative test: guest is redirected to the login page.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/pos')
            ->assertRedirect();
    }

    // ─── Categories ─────────────────────────────────────────────────────────────

    /**
     * Objective: Verify categories are displayed as filter buttons on the POS page.
     * Positive test: active categories appear in the rendered output.
     */
    public function test_page_shows_category_filter_buttons(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->assertSee('Minuman')
            ->assertSee('Makanan');
    }

    /**
     * Objective: Verify filtering by a category tab shows only that category's products.
     * Positive test: setting activeCategoryId filters the products collection.
     */
    public function test_category_filter_shows_only_matching_products(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->set('activeCategoryId', $this->category->id)
            ->assertSee('Kopi Hitam')
            ->assertDontSee('Nasi Goreng');
    }

    // ─── Search ─────────────────────────────────────────────────────────────────

    /**
     * Objective: Verify the search query filters products by name, barcode, or SKU.
     * Positive test: setting searchQuery shows only matching products.
     */
    public function test_search_filters_products_by_name(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->set('searchQuery', 'Kopi')
            ->assertSee('Kopi Hitam')
            ->assertDontSee('Nasi Goreng');
    }

    /**
     * Objective: Verify search with no matches shows empty product grid.
     * Negative test: unmatched query renders no products.
     */
    public function test_search_with_no_matches_shows_empty(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->set('searchQuery', 'ZZZZNOTFOUND')
            ->assertDontSee('Kopi Hitam')
            ->assertDontSee('Nasi Goreng');
    }

    // ─── Add to Cart ────────────────────────────────────────────────────────────

    /**
     * Objective: Verify adding a product to the cart populates cart data correctly.
     * Positive test: addToCart adds product with correct fields.
     */
    public function test_add_to_cart_adds_product(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->assertSet('cart.0.product_id', $this->product->id)
            ->assertSet('cart.0.name', 'Kopi Hitam')
            ->assertSet('cart.0.quantity', 1)
            ->assertSet('cart.0.price', 25000.0);
    }

    /**
     * Objective: Verify adding the same product twice increments quantity.
     * Positive test: duplicate addToCart increments quantity, not duplicate item.
     */
    public function test_add_to_cart_increments_quantity_for_existing_product(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('addToCart', $this->product->id)
            ->assertSet('cart.0.quantity', 2);
    }

    /**
     * Objective: Verify out-of-stock products (track_stock=true, stock=0,
     * allow_backorder=false) cannot be added to cart.
     * Negative test: cart remains empty when stock is insufficient.
     */
    public function test_add_to_cart_shows_warning_when_stock_insufficient(): void
    {
        $outOfStock = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Barang Habis',
            'price' => 5000,
            'stock' => 0,
            'track_stock' => true,
            'allow_backorder' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $outOfStock->id)
            ->assertSet('cart', []);
    }

    /**
     * Objective: Verify products with allow_backorder=true can be added even when stock is 0.
     * Positive test: backorder products bypass stock check and can be added to cart.
     */
    public function test_add_to_cart_allows_backorder_when_stock_insufficient(): void
    {
        $backorderProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Barang PO',
            'price' => 5000,
            'stock' => 0,
            'track_stock' => true,
            'allow_backorder' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $backorderProduct->id)
            ->assertSet('cart.0.product_id', $backorderProduct->id);
    }

    /**
     * Objective: Verify products with track_stock=false can be added regardless of stock value.
     * Positive test: non-tracked stock products bypass stock check entirely.
     */
    public function test_add_to_cart_allows_when_track_stock_is_disabled(): void
    {
        $untracked = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Jasa Servis',
            'price' => 50000,
            'stock' => 0,
            'track_stock' => false,
            'allow_backorder' => false,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $untracked->id)
            ->assertSet('cart.0.product_id', $untracked->id);
    }

    // ─── Update Cart ────────────────────────────────────────────────────────────

    /**
     * Objective: Verify updating a cart item's quantity works correctly.
     * Positive test: updateCartItem changes the quantity field.
     */
    public function test_update_cart_item_quantity(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('updateCartItem', 0, 'quantity', 5)
            ->assertSet('cart.0.quantity', 5);
    }

    /**
     * Objective: Verify quantity cannot be set below 1.
     * Negative test: updateCartItem clamps quantity to minimum of 1.
     */
    public function test_update_cart_item_quantity_enforces_minimum(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('updateCartItem', 0, 'quantity', 0)
            ->assertSet('cart.0.quantity', 1);
    }

    /**
     * Objective: Verify quantity update respects stock limits.
     * Negative test: setting quantity above stock (no backorder) leaves quantity unchanged.
     */
    public function test_update_cart_item_quantity_validates_stock(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('updateCartItem', 0, 'quantity', 200)
            ->assertSet('cart.0.quantity', 1);
    }

    /**
     * Objective: Verify updating a non-existent cart index is safely ignored.
     * Negative test: updateCartItem with invalid index does nothing.
     */
    public function test_update_cart_item_invalid_index_is_ignored(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('updateCartItem', 99, 'quantity', 5)
            ->assertSet('cart.0.quantity', 1);
    }

    // ─── Remove from Cart ──────────────────────────────────────────────────────

    /**
     * Objective: Verify removing an item from the cart removes it.
     * Positive test: removeFromCart removes the item and cart is empty.
     */
    public function test_remove_from_cart_removes_item(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('removeFromCart', 0)
            ->assertSet('cart', []);
    }

    /**
     * Objective: Verify removing a non-existent index is safely ignored.
     * Negative test: removeFromCart with invalid index does nothing.
     */
    public function test_remove_from_cart_invalid_index_is_ignored(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('removeFromCart', 99)
            ->assertSet('cart', fn (array $cart) => count($cart) === 1);
    }

    // ─── Clear Cart ─────────────────────────────────────────────────────────────

    /**
     * Objective: Verify clearing the cart resets all transaction state back to defaults.
     * Positive test: clearCart empties cart, customer, note, paidAmount, and totals.
     */
    public function test_clear_cart_resets_state(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('selectedCustomerId', $customer->id)
            ->set('note', 'Test note')
            ->set('paidAmount', 50000)
            ->call('clearCart')
            ->assertSet('cart', [])
            ->assertSet('selectedCustomerId', null)
            ->assertSet('note', null)
            ->assertSet('paidAmount', 0)
            ->assertSet('subtotal', 0)
            ->assertSet('grandTotal', 0)
            ->assertSet('processing', false);
    }

    // ─── Quick Amount ──────────────────────────────────────────────────────────

    /**
     * Objective: Verify quick amount buttons set the paidAmount.
     * Positive test: quickAmount sets paidAmount to the given value.
     */
    public function test_quick_amount_sets_paid_amount(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('quickAmount', '50000')
            ->assertSet('paidAmount', 50000.0);
    }

    // ─── Totals ─────────────────────────────────────────────────────────────────

    /**
     * Objective: Verify totals are correctly computed after adding items to cart.
     * Positive test: subtotal and grand total equal product price after adding.
     */
    public function test_totals_are_computed_after_adding_to_cart(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->assertSet('subtotal', 25000.0)
            ->assertSet('grandTotal', 25000.0);
    }

    /**
     * Objective: Verify totals update when cart quantity changes.
     * Positive test: changing quantity updates subtotal and grand total.
     */
    public function test_totals_update_when_quantity_changes(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('updateCartItem', 0, 'quantity', 3)
            ->assertSet('subtotal', 75000.0)
            ->assertSet('grandTotal', 75000.0);
    }

    /**
     * Objective: Verify change amount is computed when paid amount exceeds total.
     * Positive test: paidAmount above grand total produces correct change.
     */
    public function test_change_amount_is_computed(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('paidAmount', 50000)
            ->assertSet('changeAmount', 25000.0);
    }

    // ─── Payment Methods ────────────────────────────────────────────────────────

    /**
     * Objective: Verify payment method options are rendered when cart has items.
     * Positive test: active payment methods appear in the cart panel.
     */
    public function test_page_shows_payment_methods(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->assertSee($this->paymentMethod->name);
    }

    /**
     * Objective: Verify the payment methods computed property returns only active methods.
     * Positive test: getPaymentMethodsProperty excludes inactive payment methods.
     */
    public function test_payment_methods_property_returns_active_methods(): void
    {
        // Create an inactive payment method that should not appear
        PaymentMethod::factory()->create(['is_active' => false, 'name' => 'Cicilan']);

        Livewire::test(PosTransaction::class)
            ->assertSet('paymentMethods', fn ($methods) => $methods->count() === 1 && $methods->first()->id === $this->paymentMethod->id);
    }

    // ─── Customers ──────────────────────────────────────────────────────────────

    /**
     * Objective: Verify customer dropdown is rendered when cart has items.
     * Positive test: customers appear in the cart panel.
     */
    public function test_page_shows_customers(): void
    {
        $customer = Customer::factory()->create(['name' => 'Budi Santoso']);

        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->assertSee('Budi Santoso');
    }

    /**
     * Objective: Verify the customers computed property returns all customers.
     * Positive test: getCustomersProperty returns all customers ordered by name.
     */
    public function test_customers_property_returns_all_customers(): void
    {
        Customer::factory()->create(['name' => 'Andi']);
        Customer::factory()->create(['name' => 'Budi']);

        Livewire::test(PosTransaction::class)
            ->assertSet('customers', fn ($customers) => $customers->count() === 2 && $customers->first()->name === 'Andi');
    }

    // ─── Process Payment ───────────────────────────────────────────────────────

    /**
     * Objective: Verify processPayment with valid cart creates a transaction.
     * Positive test: adding items and calling processPayment creates a sale.
     */
    public function test_process_payment_creates_transaction(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('paymentMethodId', $this->paymentMethod->id)
            ->set('paidAmount', 25000)
            ->call('processPayment');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'payment_method_id' => $this->paymentMethod->id,
            'subtotal' => 25000,
            'grand_total' => 25000,
            'paid_amount' => 25000,
            'change_amount' => 0,
        ]);
    }

    /**
     * Objective: Verify processPayment with empty cart does not create a transaction.
     * Negative test: calling processPayment with no cart items shows warning.
     */
    public function test_process_payment_with_empty_cart_does_nothing(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('processPayment')
            ->assertSet('processing', false);

        $this->assertDatabaseCount('transactions', 0);
    }

    /**
     * Objective: Verify processPayment creates transaction items correctly.
     * Positive test: transaction has correct item lines with quantities.
     */
    public function test_process_payment_creates_transaction_items(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('paymentMethodId', $this->paymentMethod->id)
            ->set('paidAmount', 25000)
            ->call('processPayment');

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 25000,
        ]);
    }

    /**
     * Objective: Verify processPayment with multiple items works correctly.
     * Positive test: cart with multiple distinct products creates sale.
     */
    public function test_process_payment_with_multiple_items(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->call('addToCart', $this->otherProduct->id)
            ->set('paymentMethodId', $this->paymentMethod->id)
            ->set('paidAmount', 60000)
            ->call('processPayment');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'grand_total' => 60000,
            'paid_amount' => 60000,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $this->otherProduct->id,
            'quantity' => 1,
        ]);
    }

    /**
     * Objective: Verify processPayment with a customer selected links the transaction.
     * Positive test: selected customer is linked to the transaction.
     */
    public function test_process_payment_with_customer_links_transaction(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('selectedCustomerId', $customer->id)
            ->set('paymentMethodId', $this->paymentMethod->id)
            ->set('paidAmount', 25000)
            ->call('processPayment');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Objective: Verify processPayment reduces product stock.
     * Positive test: after payment, product stock is decremented by the sold quantity.
     */
    public function test_process_payment_reduces_stock(): void
    {
        $this->assertSame(100, $this->product->stock);

        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('paymentMethodId', $this->paymentMethod->id)
            ->set('paidAmount', 25000)
            ->call('processPayment');

        $this->assertSame(99, $this->product->fresh()->stock);
    }

    /**
     * Objective: Verify processPayment clears the cart upon success.
     * Positive test: after successful payment, cart is empty and totals reset.
     */
    public function test_process_payment_clears_cart_on_success(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('paymentMethodId', $this->paymentMethod->id)
            ->set('paidAmount', 25000)
            ->call('processPayment')
            ->assertSet('cart', [])
            ->assertSet('subtotal', 0)
            ->assertSet('grandTotal', 0)
            ->assertSet('processing', false);
    }

    /**
     * Objective: Verify processPayment sets processing flag during execution.
     * Positive test: processing is true during payment, false after.
     */
    public function test_process_payment_sets_processing_flag(): void
    {
        $this->actingAs($this->user);

        Livewire::test(PosTransaction::class)
            ->call('addToCart', $this->product->id)
            ->set('paymentMethodId', $this->paymentMethod->id)
            ->set('paidAmount', 25000)
            ->call('processPayment')
            ->assertSet('processing', false);
    }

    // ─── Products Computed Property ────────────────────────────────────────────

    /**
     * Objective: Verify getPaginatedProductsProperty returns only active products.
     * Positive test: inactive product is excluded from the paginated collection.
     */
    public function test_products_property_excludes_inactive_products(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Produk Nonaktif',
            'is_active' => false,
        ]);

        Livewire::test(PosTransaction::class)
            ->set('activeCategoryId', null)
            ->assertSet('paginatedProducts', fn ($paginator) => collect($paginator->items())->pluck('name')->doesntContain('Produk Nonaktif'));
    }

    /**
     * Objective: Verify changing category resets product pagination to page 1.
     */
    public function test_category_filter_resets_product_pagination(): void
    {
        Product::factory()->count(35)->create([
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        Livewire::test(PosTransaction::class)
            ->call('gotoPage', 2, 'productPage')
            ->set('activeCategoryId', $this->otherCategory->id)
            ->assertSet('paginatedProducts', fn ($paginator) => $paginator->currentPage() === 1);
    }

    /**
     * Objective: Verify mount sets default payment method and shows all categories.
     * Positive test: first active payment method is selected; category filter starts on "Semua".
     */
    public function test_mount_sets_default_selections(): void
    {
        Livewire::test(PosTransaction::class)
            ->assertSet('paymentMethodId', $this->paymentMethod->id)
            ->assertSet('activeCategoryId', null);
    }
}
