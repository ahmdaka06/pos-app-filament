# RULES.md — Peraturan Penulisan & Implementasi Project POS

Aturan wajib untuk semua kontributor/agent di project ini. Tujuan: kode konsisten, aman, teruji, dan selaras dengan PRD-MVP-POS.md, SCHEMA.md, dan SKILL.md.

Stack: Laravel 13, PHP 8.4, Livewire 4, Filament v5, Sanctum, MySQL 8, Redis 7, Tailwind v4.

---

## 0. Prinsip Utama

- Consistency first: ikuti pola file sekitar sebelum memperkenalkan pola baru.
- Auth: admin = login Filament; API = Sanctum. Fortify/Flux SUDAH DICABUT — jangan dipakai lagi.
- Sumber kebenaran stok = `stock_movements` (immutable). `products.stock` hanya cache turunan.
- Verifikasi sebelum klaim selesai: `php artisan test`, `vendor/bin/pint`, `npm run build`.

---

## 1. Konvensi PHP & Laravel

- PHP 8.4: gunakan constructor property promotion, return type & type hint eksplisit, enum TitleCase.
- Selalu pakai kurung kurawal pada struktur kontrol, walau satu baris.
- PHPDoc untuk array shape & generic; hindari komentar inline kecuali logika kompleks.
- Buat file via Artisan: `php artisan make:model/controller/migration/...` dengan `--no-interaction`.
- Generic class: `php artisan make:class`.
- URL: pakai named route + `route()`.
- Helper Laravel (`Str`, `Arr`, `Number`) lebih diutamakan dari fungsi PHP mentah.
- Format wajib: jalankan `vendor/bin/pint --dirty --format agent` sebelum finalisasi.

## 2. Arsitektur & Struktur

- Patuhi struktur direktori yang ada; jangan buat folder dasar baru tanpa persetujuan.
- Logika bisnis non-trivial → Action/Service class single-purpose (mis. `StockService`, `CreateSaleAction`). Controller/komponen tipis.
- Dependency injection lebih diutamakan dari helper `app()`.
- Jangan ubah dependency project tanpa persetujuan.
- Jangan hardcode nama tabel; pakai Eloquent atau `(new Model)->getTable()`.

## 3. Database & Eloquent

- Ikuti SCHEMA.md untuk semua tabel/kolom/index.
- Migrasi: FK pakai `constrained()`, index di migrasi yang sama, `down()` reversible, satu concern per migrasi.
- Jangan ubah migrasi yang sudah jalan di produksi — buat forward-fix.
- Definisikan `$fillable`/`$guarded` di setiap model.
- Cast di method `casts()`; uang `decimal:2`, tanggal ke Carbon.
- Cegah N+1: eager load `with()`, `withCount()`; aktifkan `Model::preventLazyLoading()` di dev.
- Mutasi stok HARUS lewat `StockService` di dalam DB transaction + `lockForUpdate()`. Tidak ada update stok langsung tersebar.
- Uang: `decimal(15,2)`. Hindari float untuk perhitungan uang.

## 4. Keamanan

- Validasi via Form Request; gunakan `$request->validated()`, JANGAN `$request->all()`.
- Otorisasi setiap aksi via Policy/Gate (void/refund = Manager+).
- API: `auth:sanctum` + `throttle` pada login & endpoint. Token bisa di-revoke.
- Jangan commit `.env`/rahasia; akses lewat `config()`.
- Output Blade pakai `{{ }}` (auto-escape). Tidak ada raw SQL dengan input user.
- Idempotency-Key wajib pada `POST /transactions` (lihat SCHEMA.md §4.3).

## 5. API (REST `/api/v1`)

- Versioning di path: `/api/v1`. Bungkus respons dengan Eloquent API Resources.
- Status code sesuai semantik: 200/201/204, 401/403/404, 409 (idempotency/stok), 422 (validasi), 429 (throttle).
- Pagination pakai default Laravel (`data`/`links`/`meta`).
- Penamaan resource jamak: `/products`, `/transactions`. Lihat PRD §6.
- Error format mengikuti standar Laravel (`message` + `errors`).

## 6. Filament (Admin)

- Panel di `/admin`. `User implements FilamentUser` (`canAccessPanel`).
- Resource discovery di `app/Filament/Resources`, pages di `app/Filament/Pages`, widgets di `app/Filament/Widgets`.
- Stock Movements = read-only resource (audit). Transactions = read + aksi void/refund sesuai policy.
- Jangan pakai komponen `<flux:*>` (Flux dicabut). Gunakan komponen Filament.
- Aksi destruktif (void/refund/hapus) wajib konfirmasi + cek policy.

## 7. Testing

- Wajib test untuk fitur & bugfix. Mayoritas feature test; unit test untuk StockService & kalkulasi total.
- Gunakan PHPUnit (project ini PHPUnit, bukan Pest). Buat via `php artisan make:test`.
- Pakai factory + state; `LazilyRefreshDatabase`. Test DB = SQLite in-memory (lihat phpunit.xml).
- Tutupi happy path, failure path, edge case (stok 0, qty <= 0, diskon > harga, backorder, idempotency dobel, void restock).
- Jalankan test terkait setelah tiap perubahan: `php artisan test --compact --filter=...`.
- Jangan hapus test tanpa persetujuan.

## 8. Frontend / Build

- Aset via Vite (`resources/css/app.css`, `resources/js/app.js`). Tailwind v4.
- Setelah ubah aset, jalankan `npm run build` (atau `npm run dev`).
- Tidak ada JS/CSS inline di Blade; tidak ada HTML di PHP class.

## 9. Dokumentasi & Git

- Dokumentasi disimpan di `/docs` (markdown). Jangan taruh dokumen di root.
- Changelog ringkas di `.opencode/CHANGELOG.md` setelah perubahan signifikan.
- Operasi Git (commit/push/dll) HANYA atas instruksi eksplisit user. Default: read-only git.
- Jangan tambahkan baris co-author di pesan commit.

## 10. Alur Kerja Agent

- Pakai skill sesuai pemicu (lihat SKILL.md): laravel-best-practices, livewire-development, api-design, test-driven-development, dst.
- Cari dokumentasi versi-spesifik dengan `search-docs` (laravel-boost) sebelum menebak API.
- Pakai `mgrep` untuk pencarian kode, bukan grep.
- Inspect → edit → verify. Jangan klaim sukses tanpa bukti (lihat verification-before-completion).

---

## 11. Definition of Done (DoD)

Sebuah tugas dianggap selesai jika:
1. Kode mengikuti RULES.md & selaras SCHEMA.md.
2. `vendor/bin/pint` bersih.
3. Test relevan hijau (`php artisan test`).
4. `npm run build` sukses (jika menyentuh aset).
5. Tidak ada referensi ke paket yang dicabut (Fortify/Flux/Sail/chisel/blaze/pao).
6. Perubahan terdokumentasi bila perlu (docs + changelog).
