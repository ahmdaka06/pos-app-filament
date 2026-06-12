# SCHEMA.md — Desain Database MVP POS

- Engine: MySQL 8 (utf8mb4 / utf8mb4_unicode_ci)
- Konvensi: snake_case, plural untuk nama tabel, `id` bigint auto-increment PK
- Uang: `decimal(15,2)` (konsisten Laravel cast `decimal:2`)
- Waktu: `timestamps` (created_at, updated_at), `softDeletes` di tabel master yang relevan
- FK: gunakan `constrained()` + aksi `cascade`/`restrict`/`set null` sesuai semantik
- Sumber kebenaran stok: tabel `stock_movements` (immutable). Kolom `products.stock` = nilai turunan/cache.

Status implementasi saat ini: hanya tabel fondasi yang sudah ada (`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`). Tabel domain di bawah ini DIBUAT pada Fase 1–3 via `php artisan make:migration`.

---

## 1. Diagram Relasi (ringkas)

```
users ──< stock_movements >── products >── categories
  │            │
  │  └──< transaction_items >── transactions
  │         │
  └──< transactions                 ├── customers (nullable)
   └── payment_methods
```

- `categories` 1—N `products`
- `products` 1—N `stock_movements`, 1—N `transaction_items`
- `transactions` 1—N `transaction_items`
- `users` 1—N `transactions` (kasir), 1—N `stock_movements`
- `customers` 1—N `transactions` (opsional)
- `payment_methods` 1—N `transactions`

---

## 2. Tabel Fondasi (sudah ada)

### users
Extend pada Fase 1 (tambah kolom role & status).

| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint UNSIGNED PK | |
| name | varchar(255) | |
| email | varchar(255) UNIQUE | |
| email_verified_at | timestamp NULL | |
| password | varchar(255) | hashed |
| role | enum('owner','manager','cashier') | DEFAULT 'cashier' — DITAMBAHKAN Fase 1 |
| is_active | boolean | DEFAULT true — DITAMBAHKAN Fase 1 |
| remember_token | varchar(100) NULL | |
| timestamps | | |

Auth: `User implements FilamentUser` (akses panel) + trait `HasApiTokens` (Sanctum).

### personal_access_tokens (Sanctum)
Token API mobile. Dikelola Sanctum, jangan diubah manual.

---

## 3. Tabel Domain (akan dibuat)

### 3.1 categories

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| name | varchar(255) | no | | |
| slug | varchar(255) | no | | UNIQUE |
| is_active | boolean | no | true | |
| timestamps | | | | |
| deleted_at | timestamp | yes | | softDeletes |

Index: UNIQUE(slug), INDEX(is_active).

### 3.2 products

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| category_id | bigint FK | yes | | constrained, nullOnDelete |
| sku | varchar(64) | no | | UNIQUE |
| barcode | varchar(64) | yes | | INDEX |
| name | varchar(255) | no | | |
| description | text | yes | | |
| price | decimal(15,2) | no | 0 | harga jual |
| cost_price | decimal(15,2) | yes | | harga modal |
| unit | varchar(20) | no | 'pcs' | |
| stock | int | no | 0 | nilai turunan dari stock_movements |
| reorder_level | int | no | 0 | ambang stok rendah |
| track_stock | boolean | no | true | |
| allow_backorder | boolean | no | false | |
| image_path | varchar(255) | yes | | |
| is_active | boolean | no | true | |
| timestamps | | | | |
| deleted_at | timestamp | yes | | softDeletes |

Index: UNIQUE(sku), INDEX(barcode), INDEX(category_id), INDEX(is_active).

Aturan: jika `track_stock=true` dan `allow_backorder=false`, stok tidak boleh < 0.

### 3.3 stock_movements (immutable — audit trail)

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| product_id | bigint FK | no | | constrained, cascadeOnDelete |
| user_id | bigint FK | yes | | nullOnDelete |
| type | enum('sale','void','refund','purchase','adjustment','opname') | no | | |
| quantity | int | no | | signed: negatif keluar, positif masuk |
| stock_before | int | no | | snapshot saldo sebelum |
| stock_after | int | no | | snapshot saldo sesudah |
| reference_type | varchar(255) | yes | | morph (transactions/adjustments) |
| reference_id | bigint | yes | | morph id |
| note | varchar(255) | yes | | |
| created_at | timestamp | no | | tanpa updated_at (immutable) |

Index: INDEX(product_id, created_at), INDEX(reference_type, reference_id), INDEX(type).

Aturan: hanya INSERT. Tidak ada UPDATE/DELETE. Koreksi dibuat sebagai movement baru.

### 3.4 customers

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| name | varchar(255) | no | | |
| phone | varchar(32) | yes | | INDEX |
| email | varchar(255) | yes | | |
| note | varchar(255) | yes | | |
| timestamps | | | | |
| deleted_at | timestamp | yes | | softDeletes |

Index: INDEX(phone).

### 3.5 payment_methods

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| code | varchar(32) | no | | UNIQUE (cash, transfer, qris) |
| name | varchar(64) | no | | |
| is_active | boolean | no | true | |
| timestamps | | | | |

Index: UNIQUE(code), INDEX(is_active).

### 3.6 transactions

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| invoice_number | varchar(32) | no | | UNIQUE (INV-YYYYMMDD-xxxx) |
| user_id | bigint FK | no | | kasir, restrictOnDelete |
| customer_id | bigint FK | yes | | nullOnDelete |
| payment_method_id | bigint FK | no | | restrictOnDelete |
| status | enum('completed','void','refunded') | no | 'completed' | |
| subtotal | decimal(15,2) | no | 0 | |
| discount_total | decimal(15,2) | no | 0 | |
| tax_total | decimal(15,2) | no | 0 | |
| grand_total | decimal(15,2) | no | 0 | |
| paid_amount | decimal(15,2) | no | 0 | |
| change_amount | decimal(15,2) | no | 0 | |
| idempotency_key | varchar(64) | yes | | UNIQUE |
| note | varchar(255) | yes | | |
| voided_at | timestamp | yes | | |
| voided_by | bigint FK | yes | | nullOnDelete |
| timestamps | | | | |

Index: UNIQUE(invoice_number), UNIQUE(idempotency_key), INDEX(created_at), INDEX(user_id), INDEX(status).

Aturan total: `grand_total = subtotal - discount_total + tax_total`, `change_amount = paid_amount - grand_total` (>= 0).

### 3.7 transaction_items

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| transaction_id | bigint FK | no | | cascadeOnDelete |
| product_id | bigint FK | yes | | nullOnDelete |
| product_name | varchar(255) | no | | snapshot |
| sku | varchar(64) | yes | | snapshot |
| price | decimal(15,2) | no | 0 | harga saat transaksi |
| quantity | int | no | 1 | > 0 |
| discount | decimal(15,2) | no | 0 | diskon per baris |
| line_total | decimal(15,2) | no | 0 | (price*quantity)-discount |
| timestamps | | | | |

Index: INDEX(transaction_id), INDEX(product_id).

Snapshot `product_name`/`sku`/`price` agar riwayat tetap akurat meski produk berubah/terhapus.

### 3.8 store_settings (single row / key-value)

| Kolom | Tipe | Null | Default | Catatan |
|-------|------|------|---------|---------|
| id | bigint PK | no | | |
| store_name | varchar(255) | no | | |
| address | varchar(255) | yes | | |
| logo_path | varchar(255) | yes | | |
| currency | varchar(8) | no | 'IDR' | |
| tax_percent | decimal(5,2) | no | 0 | pajak default |
| invoice_prefix | varchar(16) | no | 'INV' | |
| receipt_footer | varchar(255) | yes | | |
| timestamps | | | | |

---

## 4. Aturan Integritas & Transaksi

### 4.1 Pengurangan stok (penjualan)
Dalam satu DB transaction:
1. `SELECT ... FOR UPDATE` baris produk (`lockForUpdate()`).
2. Validasi stok cukup (kecuali `allow_backorder`).
3. Insert `transactions` + `transaction_items`.
4. Insert `stock_movements` (type=`sale`, quantity negatif, snapshot before/after).
5. Update `products.stock = stock_after`.
Commit. Jika gagal di langkah mana pun, rollback penuh.

### 4.2 Void / Refund
- Tidak menghapus transaksi. Set `status` ke `void`/`refunded`, isi `voided_at`/`voided_by`.
- Insert `stock_movements` balik (type=`void`/`refund`, quantity positif) untuk mengembalikan stok.

### 4.3 Idempotency
- `POST /transactions` wajib header `Idempotency-Key`.
- Sebelum membuat, cek `transactions.idempotency_key`. Jika ada, kembalikan transaksi tsama (200) tanpa membuat ganda.

### 4.4 Rekonsiliasi stok
- Command terjadwal membandingkan `products.stock` dengan `SUM(stock_movements.quantity)` per produk; laporkan/koreksi drift.

---

## 5. Catatan Migrasi
- Buat dengan `php artisan make:migration`; FK pakai `constrained()`.
- Tambahkan index di migrasi yang sama (bukan menyusul).
- Cermin default kolom di `$attributes` model.
- `down()` reversible; satu concern per migrasi (jangan campur DDL & DML).
- Jangan ubah migrasi yang sudah jalan di produksi — buat migrasi forward-fix baru.
