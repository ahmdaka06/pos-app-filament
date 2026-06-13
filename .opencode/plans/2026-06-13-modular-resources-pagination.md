# Modular Resources & Custom Pagination Meta Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor API resources into modular subfolders and replace Laravel's default pagination envelope with a clean `meta` object across all paginated endpoints.

**Architecture:** A shared `BaseCollection` overrides `paginationInformation()` to output `{page, limit, total_pages, total}`. Transaction, Product, and Customer each get their own subfolder with a `Resource` + `Collection` pair. Controllers swap `Resource::collection()` for `new XyzCollection()`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, Laravel Pint

---

## File Map

| Action | File |
|---|---|
| Create | `app/Http/Resources/Shared/BaseCollection.php` |
| Create | `app/Http/Resources/Transaction/TransactionResource.php` |
| Create | `app/Http/Resources/Transaction/TransactionCollection.php` |
| Delete | `app/Http/Resources/TransactionResource.php` |
| Create | `app/Http/Resources/Product/ProductResource.php` |
| Create | `app/Http/Resources/Product/ProductCollection.php` |
| Delete | `app/Http/Resources/ProductResource.php` |
| Create | `app/Http/Resources/Customer/CustomerResource.php` |
| Create | `app/Http/Resources/Customer/CustomerCollection.php` |
| Delete | `app/Http/Resources/CustomerResource.php` |
| Modify | `app/Http/Controllers/Api/V1/TransactionController.php` |
| Modify | `app/Http/Controllers/Api/V1/ProductController.php` |
| Modify | `app/Http/Controllers/Api/V1/CustomerController.php` |
| Modify | `tests/Feature/Api/CatalogApiTest.php` |
| Modify | `tests/Feature/Api/TransactionApiTest.php` |

---

## Task 1: Create `Shared/BaseCollection`

**Files:**
- Create: `app/Http/Resources/Shared/BaseCollection.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Api/PaginationMetaTest.php`:

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_response_has_custom_meta_shape(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(5)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.page', 1)
            ->assertJsonMissingPath('links')
            ->assertJsonMissingPath('meta.current_page');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/Api/PaginationMetaTest.php
```

Expected: FAIL — `meta.page` not found, `links` present.

- [ ] **Step 3: Create the directory and file**

Create `app/Http/Resources/Shared/BaseCollection.php`:

```php
<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseCollection extends ResourceCollection
{
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'page'        => $this->resource->currentPage(),
                'limit'       => $this->resource->perPage(),
                'total_pages' => $this->resource->lastPage(),
                'total'       => $this->resource->total(),
            ],
        ];
    }
}
```

- [ ] **Step 4: Test still fails (ProductCollection doesn't exist yet — expected)**

Leave it; it will pass after Task 3 wires up ProductCollection.

---

## Task 2: Move `TransactionResource` into `Transaction/` subfolder

**Files:**
- Create: `app/Http/Resources/Transaction/TransactionResource.php`
- Delete: `app/Http/Resources/TransactionResource.php`

- [ ] **Step 1: Create the new file**

Create `app/Http/Resources/Transaction/TransactionResource.php`:

```php
<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'invoice_number'     => $this->invoice_number,
            'status'             => $this->status->value,
            'subtotal'           => $this->subtotal,
            'discount_total'     => $this->discount_total,
            'tax_total'          => $this->tax_total,
            'grand_total'        => $this->grand_total,
            'paid_amount'        => $this->paid_amount,
            'change_amount'      => $this->change_amount,
            'customer_id'        => $this->customer_id,
            'payment_method_id'  => $this->payment_method_id,
            'created_at'         => $this->created_at?->toIso8601String(),
            'items'              => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'product_id'   => $i->product_id,
                'product_name' => $i->product_name,
                'sku'          => $i->sku,
                'price'        => $i->price,
                'quantity'     => $i->quantity,
                'discount'     => $i->discount,
                'line_total'   => $i->line_total,
            ])),
            'receipt' => $this->when($request->routeIs('*.show'), fn () => $this->toReceiptArray()),
        ];
    }
}
```

- [ ] **Step 2: Delete the old file**

```bash
Remove-Item -LiteralPath "app/Http/Resources/TransactionResource.php"
```

---

## Task 3: Create `TransactionCollection` and update `TransactionController`

**Files:**
- Create: `app/Http/Resources/Transaction/TransactionCollection.php`
- Modify: `app/Http/Controllers/Api/V1/TransactionController.php`

- [ ] **Step 1: Create `TransactionCollection`**

Create `app/Http/Resources/Transaction/TransactionCollection.php`:

```php
<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\Shared\BaseCollection;

class TransactionCollection extends BaseCollection
{
    public $collects = TransactionResource::class;
}
```

- [ ] **Step 2: Update `TransactionController`**

Replace the entire file `app/Http/Controllers/Api/V1/TransactionController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateSaleAction;
use App\Actions\RefundTransactionAction;
use App\Actions\VoidTransactionAction;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransactionRequest;
use App\Http\Resources\Transaction\TransactionCollection;
use App\Http\Resources\Transaction\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query()->latest();

        if ($request->boolean('mine')) {
            $query->mine($request->user()->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return new TransactionCollection($query->paginate(20));
    }

    public function store(StoreTransactionRequest $request, CreateSaleAction $action)
    {
        $key = $request->header('Idempotency-Key');

        $existing = $key ? Transaction::where('idempotency_key', $key)->first() : null;

        try {
            $transaction = $action->execute($request->validated(), $request->user(), $key);
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages([
                'items' => [$e->getMessage()],
            ]);
        }

        $status = ($existing && $existing->id === $transaction->id) ? 200 : 201;

        return (new TransactionResource($transaction->load('items')))->response()->setStatusCode($status);
    }

    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load('items'));
    }

    public function void(Transaction $transaction, VoidTransactionAction $action)
    {
        $this->authorize('void', $transaction);

        return new TransactionResource($action->execute($transaction, request()->user())->load('items'));
    }

    public function refund(Transaction $transaction, RefundTransactionAction $action)
    {
        $this->authorize('refund', $transaction);

        return new TransactionResource($action->execute($transaction, request()->user())->load('items'));
    }
}
```

- [ ] **Step 3: Run transaction tests**

```bash
php artisan test --compact tests/Feature/Api/TransactionApiTest.php
```

Expected: All pass (meta.total assertion on line 106 is already using the correct field name).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Resources/Transaction/ app/Http/Resources/Shared/ app/Http/Controllers/Api/V1/TransactionController.php
git commit -m "refactor: move TransactionResource to modular subfolder with TransactionCollection"
```

---

## Task 4: Move `ProductResource` and create `ProductCollection`

**Files:**
- Create: `app/Http/Resources/Product/ProductResource.php`
- Create: `app/Http/Resources/Product/ProductCollection.php`
- Delete: `app/Http/Resources/ProductResource.php`
- Modify: `app/Http/Controllers/Api/V1/ProductController.php`

- [ ] **Step 1: Create `Product/ProductResource.php`**

Create `app/Http/Resources/Product/ProductResource.php`:

```php
<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'sku'         => $this->sku,
            'barcode'     => $this->barcode,
            'name'        => $this->name,
            'price'       => $this->price,
            'unit'        => $this->unit,
            'stock'       => $this->stock,
            'category_id' => $this->category_id,
            'image_url'   => $this->image_url,
            'is_active'   => $this->is_active,
        ];
    }
}
```

> Note: Verify the exact fields against the original `app/Http/Resources/ProductResource.php` before deleting it.

- [ ] **Step 2: Create `Product/ProductCollection.php`**

Create `app/Http/Resources/Product/ProductCollection.php`:

```php
<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Shared\BaseCollection;

class ProductCollection extends BaseCollection
{
    public $collects = ProductResource::class;
}
```

- [ ] **Step 3: Update `ProductController`**

Replace the entire file `app/Http/Controllers/Api/V1/ProductController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->active()
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->when($request->input('barcode'), fn ($q, $b) => $q->where('barcode', $b))
            ->when($request->input('category_id'), fn ($q, $c) => $q->where('category_id', $c))
            ->orderBy('name')
            ->paginate(20);

        return new ProductCollection($products);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }
}
```

- [ ] **Step 4: Delete the old file**

```bash
Remove-Item -LiteralPath "app/Http/Resources/ProductResource.php"
```

- [ ] **Step 5: Run catalog tests**

```bash
php artisan test --compact tests/Feature/Api/CatalogApiTest.php
```

Expected: FAIL on `links` assertion — that gets fixed in Task 6.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Resources/Product/ app/Http/Controllers/Api/V1/ProductController.php
git commit -m "refactor: move ProductResource to modular subfolder with ProductCollection"
```

---

## Task 5: Move `CustomerResource` and create `CustomerCollection`

**Files:**
- Create: `app/Http/Resources/Customer/CustomerResource.php`
- Create: `app/Http/Resources/Customer/CustomerCollection.php`
- Delete: `app/Http/Resources/CustomerResource.php`
- Modify: `app/Http/Controllers/Api/V1/CustomerController.php`

- [ ] **Step 1: Create `Customer/CustomerResource.php`**

Create `app/Http/Resources/Customer/CustomerResource.php`:

```php
<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }
}
```

- [ ] **Step 2: Create `Customer/CustomerCollection.php`**

Create `app/Http/Resources/Customer/CustomerCollection.php`:

```php
<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\Shared\BaseCollection;

class CustomerCollection extends BaseCollection
{
    public $collects = CustomerResource::class;
}
```

- [ ] **Step 3: Update `CustomerController`**

Replace the entire file `app/Http/Controllers/Api/V1/CustomerController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Resources\Customer\CustomerCollection;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::search($request->string('search')->toString())
            ->orderBy('name')
            ->paginate(20);

        return new CustomerCollection($customers);
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }
}
```

- [ ] **Step 4: Delete the old file**

```bash
Remove-Item -LiteralPath "app/Http/Resources/CustomerResource.php"
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Resources/Customer/ app/Http/Controllers/Api/V1/CustomerController.php
git commit -m "refactor: move CustomerResource to modular subfolder with CustomerCollection"
```

---

## Task 6: Update tests

**Files:**
- Modify: `tests/Feature/Api/CatalogApiTest.php`
- Modify: `tests/Feature/Api/PaginationMetaTest.php` (created in Task 1)

- [ ] **Step 1: Fix `CatalogApiTest.php`**

In `tests/Feature/Api/CatalogApiTest.php`, replace line 23:

```php
// OLD
->assertJsonStructure(['data' => [['id', 'name', 'price', 'stock']], 'meta', 'links'])
->assertJsonPath('meta.total', 1);

// NEW
->assertJsonStructure(['data' => [['id', 'name', 'price', 'stock']], 'meta'])
->assertJsonMissingPath('links')
->assertJsonPath('meta.page', 1)
->assertJsonPath('meta.limit', 20)
->assertJsonPath('meta.total', 1)
->assertJsonPath('meta.total_pages', 1);
```

- [ ] **Step 2: Run all API tests**

```bash
php artisan test --compact tests/Feature/Api/
```

Expected: All pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Api/
git commit -m "test: update pagination assertions to use custom meta shape"
```

---

## Task 7: Format and full test run

- [ ] **Step 1: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

Expected: No errors or auto-fixed files committed.

- [ ] **Step 2: Stage any Pint fixes**

```bash
git add -A
git diff --cached --stat
```

If any files were changed by Pint, commit them:

```bash
git commit -m "style: apply pint formatting"
```

- [ ] **Step 3: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass.
