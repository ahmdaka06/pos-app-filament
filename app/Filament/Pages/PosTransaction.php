<?php

namespace App\Filament\Pages;

use App\Actions\CreateSaleAction;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\SaleTotals;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

class PosTransaction extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Point Of Sale';

    protected static ?string $slug = 'pos';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected string $view = 'filament.pages.pos-transaction';

    // Cart items
    public array $cart = [];

    // Transaction data
    public ?int $selectedCustomerId = null;

    public ?int $paymentMethodId = null;

    public float $paidAmount = 0;

    public ?string $note = null;

    // UI state
    public ?int $activeCategoryId = null;

    public ?string $searchQuery = null;

    public bool $processing = false;

    public int $productPerPage = 30;

    // Computed totals
    public float $subtotal = 0;

    public float $discountTotal = 0;

    public float $taxTotal = 0;

    public float $grandTotal = 0;

    public float $changeAmount = 0;

    public function mount(): void
    {
        $this->paymentMethodId = PaymentMethod::active()->first()?->id;
        $this->activeCategoryId = null;
        $this->recalculateTotals();
    }

    public function updated(mixed $propertyName): void
    {
        if (in_array($propertyName, ['activeCategoryId', 'searchQuery'], true)) {
            $this->resetPage('productPage');
        }

        if (str_starts_with($propertyName, 'cart') || $propertyName === 'paidAmount') {
            $this->recalculateTotals();
        }
    }

    // ─── Cart Actions ───────────────────────────────────────────────────────

    public function addToCart(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $existingIndex = collect($this->cart)->search(fn (array $item) => $item['product_id'] === $productId);

        if ($existingIndex !== false) {
            $newQty = $this->cart[$existingIndex]['quantity'] + 1;

            if (! $this->validateStock($product, $newQty)) {
                return;
            }

            $this->cart[$existingIndex]['quantity'] = $newQty;
        } else {
            if (! $this->validateStock($product, 1)) {
                return;
            }

            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => 1,
                'discount' => 0,
                'stock' => $product->stock,
                'track_stock' => $product->track_stock,
                'allow_backorder' => $product->allow_backorder,
            ];
        }

        $this->recalculateTotals();
    }

    public function updateCartItem(int $index, string $field, mixed $value): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        if ($field === 'quantity') {
            $value = max(1, (int) $value);

            $product = Product::find($this->cart[$index]['product_id']);
            if ($product && ! $this->validateStock($product, $value)) {
                return;
            }
        }

        if ($field === 'discount') {
            $value = max(0, (float) $value);
        }

        $this->cart[$index][$field] = $value;
        $this->recalculateTotals();
    }

    public function removeFromCart(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);

        $this->recalculateTotals();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->selectedCustomerId = null;
        $this->note = null;
        $this->paidAmount = 0;
        $this->processing = false;

        $this->recalculateTotals();
    }

    // ─── Payment ────────────────────────────────────────────────────────────

    public function processPayment(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title(__('Keranjang kosong'))
                ->warning()
                ->send();

            return;
        }

        $this->processing = true;

        try {
            $data = [
                'items' => collect($this->cart)->map(fn (array $item) => [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount'],
                ])->all(),
                'customer_id' => $this->selectedCustomerId,
                'payment_method_id' => $this->paymentMethodId,
                'paid_amount' => (float) $this->paidAmount,
                'discount_total' => 0,
                'note' => $this->note,
            ];

            app(CreateSaleAction::class)->execute($data, auth()->user(), null);

            Notification::make()
                ->title(__('Transaksi berhasil'))
                ->success()
                ->send();

            $this->clearCart();
        } catch (InsufficientStockException $e) {
            Notification::make()
                ->title(__('Stok tidak mencukupi'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->processing = false;
        }
    }

    public function quickAmount(string $amount): void
    {
        $this->paidAmount = (float) $amount;
        $this->recalculateTotals();
    }

    // ─── Data Access ────────────────────────────────────────────────────────

    public function getCategoriesProperty(): Collection
    {
        return Category::active()->orderBy('name')->get();
    }

    public function getPaginatedProductsProperty(): LengthAwarePaginator
    {
        return Product::active()
            ->when($this->activeCategoryId, fn ($q) => $q->where('category_id', $this->activeCategoryId))
            ->when($this->searchQuery, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                    ->orWhere('barcode', 'like', "%{$this->searchQuery}%")
                    ->orWhere('sku', 'like', "%{$this->searchQuery}%");
            }))
            ->orderBy('name')
            ->paginate($this->productPerPage, pageName: 'productPage');
    }

    public function getCustomersProperty(): Collection
    {
        return Customer::orderBy('name')->get();
    }

    public function getPaymentMethodsProperty(): Collection
    {
        return PaymentMethod::active()->get();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function validateStock(Product $product, int $quantity): bool
    {
        if (! $product->track_stock) {
            return true;
        }

        if ($product->stock < $quantity && ! $product->allow_backorder) {
            Notification::make()
                ->title(__('Stok tidak mencukupi'))
                ->body(__(':name tersedia :stock', [
                    'name' => $product->name,
                    'stock' => $product->stock,
                ]))
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    private function recalculateTotals(): void
    {
        $store = StoreSetting::current();

        $totals = SaleTotals::compute(
            items: $this->cart,
            discountTotal: 0,
            taxPercent: (float) $store->tax_percent,
            paidAmount: (float) $this->paidAmount,
        );

        $this->subtotal = $totals->subtotal;
        $this->discountTotal = $totals->discountTotal;
        $this->taxTotal = $totals->taxTotal;
        $this->grandTotal = $totals->grandTotal;
        $this->changeAmount = $totals->changeAmount;
    }
}
