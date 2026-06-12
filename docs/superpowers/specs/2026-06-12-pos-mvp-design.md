# POS MVP — Design Doc

- Date: 2026-06-12
- Status: Approved (brainstorming) — pending spec review
- Related: `docs/PRD-MVP-POS.md`, `docs/SCHEMA.md`, `docs/RULES.md`, `docs/SKILL.md`
- Build strategy: Opsi A — vertical slices berurutan dependency

## Konteks

Project Laravel 13 + PHP 8.4 + Filament v5 (panel `/admin`) + Sanctum (API). Fortify/Flux sudah dicabut. Saat ini hanya fondasi yang ada: `User` model (implements `FilamentUser`, trait `HasApiTokens`), `AdminPanelProvider`, dan tabel fondasi (`users`, `cache`, `jobs`, `personal_access_tokens`). Belum ada model/migrasi domain, belum ada `app/Filament`.

MySQL untuk dev/prod, SQLite in-memory untuk test (phpunit.xml). Redis tersedia. docker-compose tersedia.

## Keputusan (dari brainstorming)

- Cakupan: seluruh MVP Fase 1-3. Shift kas (Fase 4) dikecualikan.
- Otorisasi: enum `role` (owner/manager/cashier) di `users` + Laravel Policies/Gates.
- Struk: data JSON via API (tanpa PDF server).
- Gambar produk: disk `public` lokal (FileUpload Filament, API kembalikan URL).
- Testing: menyeluruh — unit (StockService, kalkulasi total) + feature kritikal.
- REST API: hanya endpoint operasional kasir. CRUD master data via Filament.
- Filament transactions: MVP read-only + void/refund. Form "Buat Penjualan" di Filament ditunda pasca-MVP (akan memakai `CreateSaleAction` yang sama).

## Arsitektur & Pola Lintas-Modul

Struktur kode (RULES.md — Action/Service single-purpose, controller/komponen tipis):

- `app/Models/` — Category, Product, StockMovement, Customer, PaymentMethod, Transaction, TransactionItem, StoreSetting.
- `app/Services/StockService.php` — satu-satunya jalur mutasi stok (DB transaction + `lockForUpdate`).
- `app/Actions/` — `CreateSaleAction`, `VoidTransactionAction`, `RefundTransactionAction`. Dipakai bersama oleh Filament & API.
- `app/Enums/` — `UserRole`, `TransactionStatus`, `StockMovementType`, `PaymentMethodCode` (string-backed, TitleCase keys).
- `app/Filament/Resources/` + `Pages/` + `Widgets/` — admin UI.
- `app/Http/Controllers/Api/V1/` + `app/Http/Resources/` + `app/Http/Requests/Api/V1/` — REST API.
- `app/Policies/` — otorisasi per model.
- `app/Exceptions/InsufficientStockException.php`.

Pola kunci:

- Otorisasi: enum `role` + Policy per model. Cashier = baca + buat penjualan; manager/owner = void/refund + master data. Filament resource & API controller sama-sama lewat Policy.
- Uang: `decimal(15,2)`, cast `decimal:2`. Kalkulasi total terpusat di satu helper/Action (konsisten Filament & API).
- Stok: hanya `StockService` menyentuh `products.stock` + nulis `stock_movements` (immutable, INSERT only).
- Idempotency: `CreateSaleAction` cek `transactions.idempotency_key` sebelum membuat.
- Test DB: SQLite in-memory; dev/prod MySQL.

## Slice 1 — Fondasi Domain

Migrasi: extend `users` — `role` enum('owner','manager','cashier') default 'cashier', `is_active` boolean default true.

- `app/Enums/UserRole.php`: keys `Owner`/`Manager`/`Cashier`, string-backed. Method `canManageTransactions(): bool` (owner|manager).
- `User` model: tambah `role`/`is_active` ke `$fillable`, cast `role`=>UserRole, `is_active`=>bool. `canAccessPanel()` => `$this->is_active === true`. Perbarui PHPDoc.
- Otorisasi via Policy: `TransactionPolicy` (didefinisikan di Slice 4) dengan ability `void`/`refund` => `role->canManageTransactions()`. Tidak memakai Gate global terpisah; semua lewat Policy agar Filament & API konsisten.
- `DatabaseSeeder`: owner default (`owner@pos.test`), 1 manager, 1 cashier (password 'password' dev only).
- `UserFactory`: state `owner()`, `manager()`, `cashier()`.

Test:
- Feature: user `is_active=false` tidak bisa akses `/admin`; cashier ditolak aksi manager.
- Unit: `UserRole::canManageTransactions()`.

## Slice 2 — Inventaris + StockService (inti)

Migrasi (SCHEMA.md §3.1-3.3): `categories`, `products`, `stock_movements`. FK `constrained()`, index sesuai SCHEMA, `softDeletes` di categories/products.

- `app/Enums/StockMovementType.php`: `Sale`, `Void`, `Refund`, `Purchase`, `Adjustment`, `Opname`.
- Models:
  - `Category` — hasMany products; scope `active()`; auto-slug dari name.
  - `Product` — belongsTo category; hasMany stockMovements; casts (price/cost_price decimal:2, bool flags); scope `active()`, `lowStock()` (stock <= reorder_level); accessor `isLowStock`.
  - `StockMovement` — belongsTo product/user; morphTo reference; immutable (no updated_at, guard di model).
- `StockService` (semua method DB transaction + `lockForUpdate`):
  - `applyMovement(Product $p, StockMovementType $type, int $qty, ?Model $reference = null, ?string $note = null, ?User $user = null): StockMovement` — hitung stock_before/after, validasi non-negatif kecuali `allow_backorder` atau `track_stock=false`; tulis movement; update `products.stock`. Lempar `InsufficientStockException` bila kurang.
  - `adjust(Product $p, int $qty, string $reason, ?User $user = null): StockMovement` — wrapper penyesuaian manual (type Adjustment).
- Filament:
  - `CategoryResource` — CRUD, toggle active.
  - `ProductResource` — CRUD, FileUpload gambar (disk public), badge low-stock, filter kategori/status, aksi "Adjust Stock" (qty + alasan => StockService::adjust).
  - `StockMovementResource` — read-only, filter product/type/tanggal.
- Low-stock: indikator visual di ProductResource + filter `lowStock()`.

Test:
- Unit StockService: kurangi sukses; tolak saat kurang (InsufficientStockException); backorder lolos; before/after benar; movement tercatat; update products.stock.
- Feature: adjust via service; low-stock scope.

## Slice 3 — Master Pendukung

Migrasi (SCHEMA.md §3.4, 3.5, 3.8): `customers`, `payment_methods`, `store_settings`.

- `app/Enums/PaymentMethodCode.php`: `Cash`, `Transfer`, `Qris`.
- Models:
  - `Customer` — softDeletes; scope `search()` (nama/phone); hasMany transactions.
  - `PaymentMethod` — scope `active()`; hasMany transactions.
  - `StoreSetting` — single-row; `StoreSetting::current()` (cache via `Cache::remember`); default currency 'IDR', tax_percent, invoice_prefix 'INV', receipt_footer.
- Filament: `CustomerResource` (CRUD ringan), `PaymentMethodResource` (CRUD + toggle), `ManageStoreSettings` (custom page single record; invalidate cache saat save).
- Seeder: payment methods default (cash/transfer/qris), 1 store_settings row.

Test:
- Feature: customer search; payment method active scope; store settings tersimpan + cache invalidate.

## Slice 4 — Transaksi + Actions

Migrasi (SCHEMA.md §3.6, 3.7): `transactions`, `transaction_items`. UNIQUE(invoice_number), UNIQUE(idempotency_key), index status/user/created_at.

- `app/Enums/TransactionStatus.php`: `Completed`, `Void`, `Refunded`.
- Models:
  - `Transaction` — belongsTo user/customer/paymentMethod; hasMany items; casts uang decimal:2 + status enum; scope `mine()`, filter tanggal/status.
  - `TransactionItem` — belongsTo transaction/product; snapshot product_name/sku/price.
- Helper kalkulasi total (dipakai bersama): `subtotal = sum(line_total)`, `line_total = price*qty - discount`, `tax_total` dari StoreSetting.tax_percent, `grand_total = subtotal - discount_total + tax_total`, `change_amount = paid_amount - grand_total` (>= 0).
- Actions:
  - `CreateSaleAction` — DB transaction: jika `idempotency_key` ada & cocok => kembalikan transaksi lama; generate invoice_number `INV-YYYYMMDD-xxxx` (prefix dari StoreSetting); hitung total; buat transaction + items; `StockService::applyMovement(Sale, -qty, reference=transaction)` per item; commit. Validasi paid_amount >= grand_total.
  - `VoidTransactionAction` — status=>Void, voided_at/by; restock via StockService (type Void). Hanya transaksi Completed.
  - `RefundTransactionAction` — status=>Refunded; restock (type Refund). MVP refund penuh. Hanya Completed.
- Filament `TransactionResource` (read-only + aksi): list (filter tanggal/kasir/status/payment), view detail + items, aksi Void/Refund (konfirmasi + Policy manager+). TANPA create di MVP.
- Receipt JSON: method `Transaction::toReceiptArray()` (toko dari StoreSetting, items, total, bayar, footer) — dipakai API show.

Test:
- Unit: kalkulasi total/diskon/pajak/kembalian.
- Feature: penjualan sukses (stok turun, movement Sale); stok kurang ditolak; void restock + status; refund restock + status; otorisasi (cashier tak bisa void/refund).

## Slice 5 — REST API Kasir (`/api/v1`)

Sanctum auth, cashier-operational. Semua response via Eloquent API Resources; validasi via Form Requests; `throttle` pada login & grup API; error format Laravel standar. Pemetaan status: 200 (baca/aksi/idempotency ulang), 201 (transaksi baru), 401/403 (auth/policy), 404, 422 (validasi & stok kurang), 429 (throttle).

Endpoints:
- `POST /api/v1/auth/login` (email, password, device_name => token + user), `POST /api/v1/auth/logout`, `GET /api/v1/auth/me`
- `GET /api/v1/categories`
- `GET /api/v1/products` (search/barcode/category, paginate), `GET /api/v1/products/{product}`
- `GET /api/v1/payment-methods`
- `GET /api/v1/customers` (search), `POST /api/v1/customers`
- `POST /api/v1/transactions` (header `Idempotency-Key` wajib => CreateSaleAction), `GET /api/v1/transactions` (filter, `mine=1`), `GET /api/v1/transactions/{transaction}` (+ receipt JSON)
- `POST /api/v1/transactions/{transaction}/void` (manager+), `POST /api/v1/transactions/{transaction}/refund` (manager+)
- `GET /api/v1/reports/summary?date=`

Detail:
- API Resources: `ProductResource`, `CategoryResource`, `CustomerResource`, `PaymentMethodResource`, `TransactionResource` (+ items + receipt).
- Idempotency: jika key dikirim ulang => 200 transaksi sama (bukan 201 ganda). Stok kurang => 422 dengan errors per item.
- Token Sanctum dapat di-revoke (logout menghapus token saat ini).

Test:
- Feature: login mengeluarkan token + throttle; create sale via API (stok turun); idempotency dobel tidak menggandakan; stok kurang => 422; void/refund (manager+) & cashier ditolak (403); list `mine`; products search/paginate; reports/summary.

## Slice 6 — Dashboard & Laporan

- Filament Widgets: `StatsOverview` (penjualan hari ini sum grand_total status=Completed, jumlah transaksi, AOV, item terjual), chart penjualan 7/30 hari, tabel produk terlaris, indikator stok rendah.
- Laporan: halaman penjualan per rentang tanggal (from/to) ringkasan + daftar; laporan stok (nilai persediaan = sum stock*cost_price, pergerakan).
- Export CSV via `StreamedResponse` (tanpa package tambahan).
- Performa: agregat `selectRaw`/`withCount`, hindari N+1; cache pendek `Cache::remember` untuk summary.
- API `GET /reports/summary` (tercakup Slice 5).

Test:
- Feature: summary benar (hanya Completed; void/refund dikecualikan); low-stock muncul; export CSV baris benar.

## Verifikasi End-to-End

Per slice (Definition of Done, RULES.md §11):
1. `vendor/bin/pint --dirty --format agent` bersih.
2. `php artisan test --compact` hijau (jalankan filter per slice saat dev).
3. `php artisan migrate:fresh --seed` sukses di MySQL dev.
4. `npm run build` sukses bila menyentuh aset.

Manual akhir MVP:
- Login `/admin` sebagai owner; CRUD kategori/produk; adjust stok; lihat low-stock; kelola payment methods/customers/store settings; lihat dashboard.
- API: login (dapat token) -> GET products -> POST transactions dengan Idempotency-Key (stok turun) -> ulangi key sama (tidak ganda) -> GET transaction (receipt JSON) -> void/refund sebagai manager (stok kembali).
- Cek otorisasi: cashier tidak bisa void/refund (403).

## Urutan Eksekusi

Slice 1 -> 2 -> 3 -> 4 -> 5 -> 6. Tiap slice: migrasi+model+service/action+Filament/API+test, lalu verifikasi DoD sebelum lanjut.

## Di Luar Cakupan (MVP)

Shift kas, form buat-penjualan di Filament, PDF struk, multi-pembayaran, S3, multi-cabang, spatie/permission.
