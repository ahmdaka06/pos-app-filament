# Stock Movement on Product CRUD Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Automatically log stock movements when products are created/updated via Filament CRUD, using existing `StockMovement` model and `StockService`.

**Architecture:** Add `stock` field to product form. Override `mutateFormDataBeforeCreate` in `CreateProduct` to capture initial stock. Override `afterSave` in `EditProduct` to diff stock changes and call `StockService::adjust()`. No new models/actions needed.

**Tech Stack:** Filament 5 (`CreateRecord`, `EditRecord`), `StockService`, `StockMovementType::Purchase` (initial stock), `StockMovementType::Adjustment` (stock edits).

---

### Task 1: Add `stock` field to ProductForm

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`
- Test: `tests/Feature/Filament/ProductResourceTest.php`

**Step 1: Write failing test that stock field exists on create form**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php --filter=test_create_product_form_has_stock_field`
Expected: FAIL (test not found)

**Step 2: Write the test**

```php
public function test_create_product_form_has_stock_field(): void
{
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->get('/admin/products/create')
        ->assertOk()
        ->assertSee('Stock');
}
```

**Step 3: Run to verify failure**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php --filter=test_create_product_form_has_stock_field`
Expected: FAIL ("Stock" not found)

**Step 4: Add stock field to form**

Add to `ProductForm::configure()`, right after `reorder_level`:

```php
TextInput::make('stock')
    ->numeric()
    ->default(0)
    ->required(),
```

**Step 5: Run test to verify passes**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php --filter=test_create_product_form_has_stock_field`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Filament/Resources/Products/Schemas/ProductForm.php tests/Feature/Filament/ProductResourceTest.php
git commit -m "feat(products): add stock field to product form"
```

---

### Task 2: Create stock movement on product creation

**Files:**
- Modify: `app/Filament/Resources/Products/Pages/CreateProduct.php`
- Test: `tests/Feature/Filament/ProductResourceTest.php`

**Step 1: Write failing test for CreateProduct stock movement**

```php
public function test_creating_product_with_stock_logs_purchase_movement(): void
{
    $owner = User::factory()->owner()->create(['is_active' => true]);
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs($owner)->post('/admin/products', [
        'category_id' => $category->id,
        'sku' => 'INV-TEST',
        'name' => 'Test Product',
        'price' => 10000,
        'stock' => 50,
        'track_stock' => true,
    ]);

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
    $owner = User::factory()->owner()->create(['is_active' => true]);
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs($owner)->post('/admin/products', [
        'category_id' => $category->id,
        'sku' => 'INV-ZERO',
        'name' => 'Zero Stock Product',
        'price' => 5000,
        'stock' => 0,
        'track_stock' => true,
    ]);

    $product = Product::where('sku', 'INV-ZERO')->first();
    $this->assertNotNull($product);
    $this->assertSame(0, $product->stock);

    $this->assertDatabaseMissing('stock_movements', [
        'product_id' => $product->id,
    ]);
}
```

**Step 2: Run to verify failure**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php --filter=test_creating_product_with_stock`
Expected: FAIL (no stock movement logged)

**Step 3: Implement CreateProduct stock movement**

In `CreateProduct`, override `afterCreate()`:

```php
use App\Models\User;
use App\Services\StockService;

// In CreateProduct class
protected function afterCreate(): void
{
    $product = $this->getRecord();
    $stock = (int) ($this->data['stock'] ?? 0);

    if ($stock > 0 && $product->track_stock) {
        app(StockService::class)->applyMovement(
            product: $product,
            type: \App\Enums\StockMovementType::Purchase,
            quantity: $stock,
            reference: null,
            note: 'Initial stock on product creation',
            user: auth()->user(),
        );
    }
}
```

Note: `afterCreate` runs after the record is saved to DB, so `$this->getRecord()` has the product ID.

**Step 4: Run tests to verify passes**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php --filter=test_creating_product_with_stock`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Filament/Resources/Products/Pages/CreateProduct.php tests/Feature/Filament/ProductResourceTest.php
git commit -m "feat(products): log stock movement on product creation"
```

---

### Task 3: Create stock movement on product stock update

**Files:**
- Modify: `app/Filament/Resources/Products/Pages/EditProduct.php`
- Test: `tests/Feature/Filament/ProductResourceTest.php`

**Step 1: Write failing test for EditProduct stock movement**

Add these test methods:

```php
public function test_updating_product_stock_logs_adjustment_movement(): void
{
    $owner = User::factory()->owner()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'stock' => 10,
        'track_stock' => true,
    ]);

    $this->actingAs($owner)->put("/admin/products/{$product->id}", [
        'category_id' => $product->category_id,
        'sku' => $product->sku,
        'name' => $product->name,
        'price' => $product->price,
        'stock' => 25,
    ]);

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
    $owner = User::factory()->owner()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'stock' => 10,
        'track_stock' => true,
    ]);

    $this->actingAs($owner)->put("/admin/products/{$product->id}", [
        'category_id' => $product->category_id,
        'sku' => $product->sku,
        'name' => $product->name,
        'price' => $product->price,
        'stock' => 10,
    ]);

    $this->assertDatabaseMissing('stock_movements', [
        'product_id' => $product->id,
    ]);
}
```

**Step 2: Run to verify failure**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php --filter=test_updating_product_stock`
Expected: FAIL (no stock movement logged)

**Step 3: Implement EditProduct stock movement**

In `EditProduct`, override `afterSave()`:

```php
use App\Services\StockService;

// In EditProduct class, add property
protected int $originalStock = 0;

// Override fillForm to capture original stock
protected function fillForm(): void
{
    parent::fillForm();
    $this->originalStock = (int) ($this->data['stock'] ?? 0);
}

// Override afterSave
protected function afterSave(): void
{
    $product = $this->getRecord();
    $newStock = (int) ($this->data['stock'] ?? 0);
    $diff = $newStock - $this->originalStock;

    if ($diff !== 0 && $product->track_stock) {
        app(StockService::class)->adjust(
            product: $product,
            quantity: $diff,
            reason: 'Manual adjustment via product edit',
            user: auth()->user(),
        );
    }
}
```

**Step 4: Run tests to verify passes**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php --filter=test_updating_product_stock`
Expected: PASS

**Step 5: Run full test suite**

Run: `php artisan test --compact tests/Feature/`
Expected: All existing tests still pass

**Step 6: Commit**

```bash
git add app/Filament/Resources/Products/Pages/EditProduct.php tests/Feature/Filament/ProductResourceTest.php
git commit -m "feat(products): log stock movement on product stock update"
```

---

### Task 4: Add stock field to product edit form and list table

**Files:**
- Modify: `app/Filament/Resources/Products/Tables/ProductsTable.php`

**Step 1: Verify stock column in table**

Check if the products table already shows the `stock` column in the list view. If not, add it:

```php
// In ProductsTable::configure()
Tables\Columns\TextColumn::make('stock')
    ->numeric()
    ->sortable(),
```

**Step 2: Run existing tests**

Run: `php artisan test --compact tests/Feature/Filament/ProductResourceTest.php`
Expected: PASS

**Step 3: Commit**

```bash
git add app/Filament/Resources/Products/Tables/ProductsTable.php
git commit -m "feat(products): add stock column to products table"
```

---

### Task 5: Create StockMovementFactory

**Files:**
- Create: `database/factories/StockMovementFactory.php`

**Step 1: Write failing test that factory exists (optional — skip this, factories are utility)**

Run: `php artisan tinker --execute '(new Database\Factories\StockMovementFactory)->definition();'`
Expected: Error (class not found)

**Step 2: Create factory**

```php
<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'type' => StockMovementType::Adjustment,
            'quantity' => fake()->randomDigitNotNull(),
            'stock_before' => 10,
            'stock_after' => 20,
            'note' => null,
        ];
    }

    public function purchase(): static
    {
        return $this->state(fn (array $attrs) => ['type' => StockMovementType::Purchase]);
    }

    public function sale(): static
    {
        return $this->state(fn (array $attrs) => ['type' => StockMovementType::Sale]);
    }
}
```

**Step 3: Verify factory works**

Run: `php artisan tinker --execute '(new Database\Factories\StockMovementFactory)->definition();'`
Expected: Returns array

**Step 4: Commit**

```bash
git add database/factories/StockMovementFactory.php
git commit -m "feat(products): add StockMovementFactory"
```

---

**Plan complete and saved to `docs/plans/2026-06-12-stock-movement-product-crud.md`.**

**Two execution options:**

1. **Subagent-Driven (this session)** — I dispatch a fresh subagent per task, review between tasks, fast iteration.

2. **Parallel Session (separate)** — New session with `executing-plans`, batch execution with checkpoints.

Which approach?
