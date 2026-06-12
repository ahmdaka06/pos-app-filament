# PRD — MVP Point of Sale (POS)

- Status: Draft v1.2
- Owner: Product / Engineering
- Last updated: 2026-06-12
- Target stack: Laravel 13, PHP 8.4, Livewire 4, Filament v5 (admin), Sanctum (API), MySQL 8, Redis 7
- Repo baseline: Filament v5 admin panel + Sanctum API. Fortify, Flux UI, dan seluruh UI auth Livewire bawaan starter kit sudah dicabut. Auth web sepenuhnya ditangani Filament.

### Dokumen Terkait
- `docs/SCHEMA.md` — desain database (tabel, kolom, index, aturan integritas & transaksi stok). Acuan tunggal untuk semua migrasi.
- `docs/SKILL.md` — skill agent & keahlian yang dipakai per tugas (laravel-best-practices, livewire-development, api-design, dll).
- `docs/RULES.md` — peraturan penulisan & implementasi (PHP/Laravel, DB, keamanan, API, Filament, testing, DoD).

Urutan baca: PRD (apa & mengapa) → SCHEMA (struktur data) → RULES (cara implementasi) → SKILL (alat/skill mana).

---

## 1. Ringkasan

Web kasir sederhana untuk UMKM/retail kecil. Terdiri dari dua permukaan:

1. Admin Dashboard (Filament) — manajemen master data, inventaris, transaksi, laporan.
2. REST API — dikonsumsi aplikasi mobile kasir (Flutter/React Native/dll) untuk operasi penjualan di lapangan.

Sumber kebenaran data tunggal (MySQL), cache & antrian via Redis. Otentikasi admin web ditangani Filament (login panel `/admin`); API memakai token (Sanctum).

### 1.1 Tujuan MVP
- Kasir dapat melakukan transaksi penjualan dari mobile secara cepat dan offline-tolerant ringan (idempoten).
- Admin dapat mengelola produk, stok, dan melihat riwayat + laporan dasar.
- Stok akurat dan otomatis berkurang saat penjualan, bertambah saat penyesuaian/pembelian.

### 1.2 Di luar cakupan (Non-Goals MVP)
- Multi-cabang/multi-gudang lanjutan (hanya satu lokasi default di MVP).
- Akuntansi penuh, pajak multi-tier kompleks, loyalty/poin.
- Integrasi pembayaran online/EDC, e-faktur.
- Sinkronisasi offline penuh (hanya idempotency key untuk mencegah double-post).

---

## 2. Persona & Peran

| Peran | Akses | Deskripsi |
|-------|-------|-----------|
| Owner/Admin | Filament penuh + API | Kelola master data, lihat semua laporan, kelola user. |
| Manajer/Supervisor | Filament (tanpa user mgmt) | Kelola produk, stok, lihat laporan, void transaksi. |
| Kasir | API (mobile) + login terbatas | Buat transaksi, lihat produk & stok, lihat riwayat sendiri. |

Implementasi peran: kolom `role` enum sederhana di tabel `users` untuk MVP (Owner, Manager, Cashier). Otorisasi via Policy + Gate. Bisa di-upgrade ke spatie/permission pasca-MVP.

---

## 3. Arsitektur & Stack

- Backend: Laravel 13 (PHP 8.4).
- Admin UI: Filament v5 (panel `/admin`).
- Web/admin auth: Login bawaan Filament. `User` mengimplementasikan `FilamentUser` (`canAccessPanel`). Fortify + Flux UI sudah dihapus.
- API auth: Laravel Sanctum (personal access token untuk mobile). `User` memakai trait `HasApiTokens`.
- Root `/` redirect ke `/admin`.
- Database: MySQL 8.
- Cache, Session, Queue: Redis (produksi). `file` hanya untuk lokal cepat.
- Queue worker: untuk job ringan (mis. rekalkulasi laporan, notifikasi stok rendah).
- Kontainerisasi: docker-compose (app PHP-FPM + Nginx, MySQL, Redis, queue worker).

### 3.1 Keputusan teknis kunci
- Stok dijaga konsisten dengan transaksi DB + `lockForUpdate()` saat pengurangan stok.
- Setiap mutasi stok dicatat sebagai baris immutable di `stock_movements` (audit trail), `products.stock` adalah cache nilai turunan.
- Penjualan via API idempoten menggunakan header `Idempotency-Key`.
- Uang disimpan sebagai integer minor unit (sen/rupiah bulat) untuk hindari error float, atau `decimal(15,2)`. MVP: `decimal(15,2)` konsisten dengan kebiasaan Laravel + cast.

---

## 4. Modul

### 4.1 Modul Inti — Inventaris & Manajemen Stok

Fitur:
- CRUD Kategori produk.
- CRUD Produk: SKU (unik), barcode, nama, kategori, harga jual, harga modal (opsional), unit, gambar, status aktif, batas stok minimum (`reorder_level`).
- Tampilan stok real-time per produk.
- Penyesuaian stok manual (stock adjustment) dengan alasan: pembelian masuk, retur, rusak, hilang, koreksi opname.
- Riwayat pergerakan stok (`stock_movements`) per produk: tipe, qty, saldo sebelum/sesudah, referensi (transaksi/adjustment), user, waktu.
- Peringatan stok rendah (stok <= `reorder_level`) di dashboard + opsi notifikasi.

Aturan:
- Stok tidak boleh minus untuk produk yang `track_stock = true` (default). Penjualan ditolak jika stok kurang, kecuali produk diset `allow_backorder`.
- Setiap perubahan stok harus melalui service `StockService` yang menulis `stock_movements` dan memperbarui `products.stock` dalam satu transaksi.

### 4.2 Modul Inti — Transaksi & Riwayat

Fitur:
- Buat transaksi penjualan: pilih item (qty, harga), diskon per item & per transaksi, pajak (opsional, single rate MVP), metode bayar (cash/transfer/qris-manual), uang diterima & kembalian.
- Nomor transaksi unik (mis. `INV-YYYYMMDD-xxxx`).
- Status transaksi: `completed`, `void`, `refunded` (refund penuh MVP).
- Riwayat transaksi: filter tanggal, kasir, metode bayar, status.
- Detail transaksi + cetak/struk (PDF / format struk thermal 58/80mm — endpoint data struk untuk mobile).
- Void / refund dengan otorisasi (Manager+) yang otomatis mengembalikan stok.

Aturan:
- Membuat transaksi `completed` mengunci & mengurangi stok atomik.
- Void/refund membuat stock_movement balik (restock) dan tidak menghapus data asli (audit).

### 4.3 Modul MVP Lainnya

1. Autentikasi & Pengguna
   - Login admin via panel Filament (`/admin/login`). Manajemen user + role di Filament (Owner only).
   - Penerbitan token API (Sanctum) untuk perangkat mobile kasir, dengan kemampuan revoke.

2. Pelanggan (Customers) — ringan
   - CRUD pelanggan opsional (nama, telp, email). Bisa ditautkan ke transaksi untuk riwayat. Tidak wajib di setiap transaksi (walk-in default).

3. Pembayaran (Payment Methods)
   - Daftar metode bayar yang dapat dikonfigurasi (cash, transfer, QRIS manual). Satu pembayaran per transaksi di MVP.

4. Pengaturan Toko (Store Settings)
   - Nama toko, alamat, logo, mata uang, format/footer struk, persen pajak default, prefix nomor invoice.

5. Dashboard & Laporan
   - Ringkasan: penjualan hari ini, jumlah transaksi, rata-rata nilai transaksi, produk terlaris, stok rendah.
   - Laporan penjualan per rentang tanggal (harian/range), export CSV.
   - Laporan stok (nilai persediaan, pergerakan).

6. Shift Kas (opsional-stretch, bisa di fase 2)
   - Buka/tutup shift kasir, saldo awal, kas masuk/keluar, rekonsiliasi. Ditandai stretch goal; tidak memblok MVP.

---

## 5. Model Data (Skema Awal)

> Detail lengkap (tipe kolom, null/default, index, aturan integritas & transaksi stok) ada di `docs/SCHEMA.md`. Bagian ini hanya ringkasan; SCHEMA.md adalah acuan tunggal untuk migrasi.

Catatan: hanya tabel fondasi (`users`, `personal_access_tokens`, dll) yang sudah ada. Sisanya dibuat via `php artisan make:migration` mengikuti konvensi Laravel (FK `constrained()`, indeks pada kolom filter/sort).

### users (extend)
- + `role` enum('owner','manager','cashier') default 'cashier'
- + `is_active` boolean default true

### categories
- id, name, slug (unik), is_active, timestamps

### products
- id, category_id (FK), sku (unik), barcode (nullable, index), name, description (nullable)
- price decimal(15,2), cost_price decimal(15,2) nullable
- unit string default 'pcs'
- stock integer default 0 (nilai turunan/cache)
- reorder_level integer default 0
- track_stock boolean default true, allow_backorder boolean default false
- image_path nullable, is_active boolean default true
- timestamps, softDeletes
- index: (is_active), (category_id)

### stock_movements (immutable audit)
- id, product_id (FK), user_id (FK nullable)
- type enum('sale','void','refund','purchase','adjustment','opname')
- quantity integer (signed: negatif keluar, positif masuk)
- stock_before integer, stock_after integer
- reference_type/reference_id (nullable morphs) → transaksi/adjustment
- note nullable, created_at
- index: (product_id, created_at)

### customers
- id, name, phone (nullable, index), email (nullable), note, timestamps, softDeletes

### payment_methods
- id, code (unik), name, is_active, timestamps

### transactions
- id, invoice_number (unik), user_id (FK kasir), customer_id (FK nullable)
- payment_method_id (FK), status enum('completed','void','refunded') default 'completed'
- subtotal, discount_total, tax_total, grand_total decimal(15,2)
- paid_amount, change_amount decimal(15,2)
- idempotency_key (unik, nullable, index), note nullable
- voided_at nullable, voided_by nullable, timestamps
- index: (created_at), (user_id), (status)

### transaction_items
- id, transaction_id (FK), product_id (FK nullable, set null on delete)
- product_name snapshot, sku snapshot
- price decimal(15,2), quantity integer
- discount decimal(15,2) default 0, line_total decimal(15,2)
- timestamps

### store_settings (key-value atau single row)
- store_name, address, logo_path, currency default 'IDR'
- tax_percent decimal(5,2) default 0, invoice_prefix default 'INV'
- receipt_footer nullable

---

## 6. Desain REST API (mobile)

Konvensi:
- Base path berversi: `/api/v1`.
- Auth: `Authorization: Bearer <sanctum-token>`. Login menukar kredensial → token.
- Format: JSON. Resource dibungkus Eloquent API Resources. Pagination via Laravel default (`data`, `links`, `meta`).
- Penamaan resource: jamak, kebab/sneak konsisten (`/products`, `/transactions`).
- Rate limiting: `throttle` pada auth (mis. 5/min) dan API umum (mis. 60/min).
- Idempotency: header `Idempotency-Key` wajib pada `POST /transactions`.

### 6.1 Status code
- 200 OK (baca/aksi sukses), 201 Created (resource baru), 204 No Content (delete).
- 401 Unauthorized, 403 Forbidden (policy), 404 Not Found.
- 422 Unprocessable Entity (validasi, format error Laravel standar).
- 409 Conflict (idempotency/stok bentrok), 429 Too Many Requests.

### 6.2 Endpoint

Auth:
- POST `/api/v1/auth/login` — {email, password, device_name} → {token, user}
- POST `/api/v1/auth/logout` — revoke token saat ini
- GET  `/api/v1/auth/me` — profil kasir

Master / katalog (read untuk kasir):
- GET `/api/v1/categories`
- GET `/api/v1/products?search=&category_id=&barcode=&page=` — daftar, filter, paginate
- GET `/api/v1/products/{product}` — detail + stok
- GET `/api/v1/payment-methods`
- GET `/api/v1/customers?search=` , POST `/api/v1/customers`

Transaksi:
- POST `/api/v1/transactions` — buat penjualan (idempoten). Body: items[], discounts, payment_method_id, paid_amount, customer_id?, note?
- GET  `/api/v1/transactions?from=&to=&status=&mine=1&page=` — riwayat
- GET  `/api/v1/transactions/{transaction}` — detail + struk data
- POST `/api/v1/transactions/{transaction}/void` — Manager+ (kembalikan stok)
- POST `/api/v1/transactions/{transaction}/refund` — Manager+ (refund penuh)

Dashboard ringkas (mobile):
- GET `/api/v1/reports/summary?date=` — penjualan hari ini, jumlah trx, dsb.

### 6.3 Contoh: POST /api/v1/transactions

Request:
```
POST /api/v1/transactions
Authorization: Bearer <token>
Idempotency-Key: 6f9a...e1
Content-Type: application/json

{
  "customer_id": null,
  "payment_method_id": 1,
  "discount_total": 0,
  "items": [
    { "product_id": 12, "quantity": 2, "discount": 0 },
    { "product_id": 34, "quantity": 1, "discount": 1000 }
  ],
  "paid_amount": 50000,
  "note": "walk-in"
}
```

Response 201:
```
{
  "data": {
    "id": 901,
    "invoice_number": "INV-20260612-0042",
    "status": "completed",
    "subtotal": 45000,
    "discount_total": 1000,
    "tax_total": 0,
    "grand_total": 44000,
    "paid_amount": 50000,
    "change_amount": 6000,
    "items": [ ... ],
    "created_at": "2026-06-12T11:50:00Z"
  }
}
```

Error 422 (stok kurang):
```
{ "message": "Stok tidak mencukupi untuk SKU-0012.", "errors": { "items.0.quantity": ["Stok tersedia: 1"] } }
```

Aturan idempotency: bila `Idempotency-Key` sama dikirim ulang, kembalikan transaksi yang sudah dibuat (200) alih-alih membuat ganda.

---

## 7. Admin Dashboard (Filament)

Panel `/admin`, akses Owner/Manager.

Resources:
- Products (dengan badge stok rendah, filter kategori/status, aksi penyesuaian stok cepat).
- Categories.
- Stock Movements (read-only, filter produk/tipe/tanggal).
- Transactions (read + aksi void/refund sesuai policy, lihat detail/struk).
- Customers.
- Payment Methods.
- Users (Owner only) + penerbitan/revoke token API.
- Store Settings (custom page).

Dashboard widgets:
- Penjualan hari ini, jumlah transaksi, AOV (average order value).
- Grafik penjualan 7/30 hari.
- Tabel produk terlaris.
- Tabel/lonceng stok rendah.

---

## 8. Persyaratan Non-Fungsional

- Performa: respons API p95 < 500ms untuk operasi baca; pembuatan transaksi < 800ms.
- Konsistensi: pengurangan stok atomik (DB transaction + lock), tidak ada oversell.
- Keamanan:
  - `$fillable`/`$guarded` di semua model; otorisasi via Policy/Gate.
  - Validasi via Form Request; tidak pakai `$request->all()`.
  - Sanctum token, throttle pada login & API; HTTPS di produksi.
  - Tidak commit `.env`; rahasia via `config()`.
- Reliabilitas: idempotency pada penjualan; audit trail stok & transaksi immutable.
- Observability: log terstruktur, queue untuk tugas berat.
- Lokali­sasi: format mata uang IDR, zona waktu toko.

---

## 9. Pengujian (wajib per perubahan)

> Aturan testing lengkap (framework, factory, edge case, DoD) ada di `docs/RULES.md` §7 & §11. Project ini memakai PHPUnit (bukan Pest); test DB = SQLite in-memory.

- Feature tests: alur penjualan (sukses, stok kurang, idempotency, void/refund restock), CRUD produk, auth API, otorisasi peran.
- Unit tests: StockService (mutasi & saldo), kalkulasi total/diskon/pajak/kembalian.
- Gunakan factory + state; `LazilyRefreshDatabase`.
- Target: jalur happy, gagal, dan edge (stok 0, qty 0/negatif, diskon > harga, backorder).

---

## 10. Rencana Rilis (Fase)

Fase 0 — Fondasi (SELESAI)
- Filament v5 panel `/admin` + Sanctum API terpasang & terverifikasi. Fortify/Flux/Sail/chisel/blaze/pao dicabut.
- Konfigurasi Redis (cache/queue/session) & docker-compose tersedia.
- Berikutnya: migrasi & model inti, seeder (kategori, produk contoh, payment methods, user owner).

Fase 1 — Inventaris
- CRUD produk/kategori, StockService, stock_movements, penyesuaian stok, peringatan stok rendah.

Fase 2 — Transaksi
- API penjualan idempoten, riwayat, void/refund, struk data, Filament transactions.

Fase 3 — Dashboard & Laporan
- Widgets, laporan penjualan/stok, export CSV, store settings.

Fase 4 (stretch) — Shift kas, pelanggan lanjutan, multi-metode bayar.

---

## 11. Kriteria Penerimaan MVP

- Kasir login via API, ambil produk, buat transaksi; stok berkurang benar & tidak bisa oversell.
- Pengiriman ulang dengan Idempotency-Key sama tidak menggandakan transaksi.
- Void/refund mengembalikan stok dan tercatat di audit.
- Admin mengelola produk/stok dan melihat riwayat + laporan dasar di Filament.
- Dashboard menampilkan penjualan hari ini & peringatan stok rendah.
- Semua perubahan ditutupi test yang lulus.

---

## 12. Risiko & Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Race condition stok (oversell) | DB transaction + `lockForUpdate`, uji konkuren |
| Double-post dari jaringan mobile buruk | Idempotency-Key unik + lookup |
| Drift nilai `products.stock` vs movements | Movements sebagai sumber kebenaran; command rekonsiliasi terjadwal |
| Ketergantungan Redis di produksi | Failover store + health check di compose |
| Auth admin & API terpisah | Filament untuk panel, Sanctum untuk mobile; `User` implement `FilamentUser` + `HasApiTokens` |
