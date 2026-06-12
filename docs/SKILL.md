# SKILL.md — Skill yang Dibutuhkan untuk Project POS

Dokumen ini memetakan skill (agent skill yang sudah terpasang + keahlian manusia) yang relevan untuk membangun MVP POS ini, beserta kapan dipakai. Tujuannya: setiap kontributor/agent tahu skill mana yang diaktifkan untuk tugas tertentu.

Stack acuan: Laravel 13, PHP 8.4, Livewire 4, Filament v5, Sanctum, MySQL 8, Redis 7, Tailwind v4.

---

## 1. Skill Agent Wajib (terpasang di repo)

Skill berikut tersedia di lingkungan dan HARUS dipakai sesuai pemicunya.

### laravel-best-practices
- Lokasi: `.agents/skills/laravel-best-practices`
- Pakai saat: menulis/refactor controller, model, migration, form request, policy, job, command, service, query Eloquent.
- Untuk POS: StockService (transaksi + lockForUpdate), query produk/laporan (hindari N+1), caching laporan, validasi via Form Request, otorisasi via Policy.

### livewire-development
- Lokasi: `.agents/skills/livewire-development`
- Pakai saat: komponen Livewire, reaktivitas, validasi real-time, loading state. Filament dibangun di atas Livewire.
- Untuk POS: custom page/komponen Filament yang butuh interaksi khusus (mis. layar kasir, pencarian produk by barcode).

### fluxui-development — TIDAK DIPAKAI
- Flux UI sudah DICABUT dari project. Jangan gunakan komponen `<flux:*>`. UI admin sepenuhnya pakai komponen Filament.

### fortify-development — TIDAK DIPAKAI
- Fortify sudah DICABUT. Auth admin = login bawaan Filament; auth API = Sanctum token. Abaikan skill ini.

### tailwindcss-development
- Lokasi: `.agents/skills/tailwindcss-development`
- Pakai saat: styling Blade/komponen, layout responsif, kustomisasi tema Filament, halaman struk.

### able-pro-laravel-ui (opsional)
- Pakai hanya jika mengadopsi template Able Pro. Default MVP: tema Filament standar, jadi umumnya TIDAK dipakai.

---

## 2. Skill Pendukung (workflow & kualitas)

### test-driven-development
- Pakai saat: implementasi fitur/bugfix. Tulis test dulu untuk StockService, alur penjualan, idempotency, void/refund.

### systematic-debugging
- Pakai saat: ada bug, test gagal, perilaku tak terduga (mis. drift stok, race condition). Reproduksi dulu, baru perbaiki.

### verification-before-completion
- Pakai saat: sebelum klaim "selesai". Jalankan `php artisan test`, `pint`, `npm run build`, dan konfirmasi output.

### database-design / database-migrations
- Pakai saat: merancang skema (lihat SCHEMA.md), menulis migrasi, index, FK, strategi rollback.

### api-design
- Pakai saat: mendesain endpoint REST `/api/v1` untuk mobile (resource naming, status code, pagination, error, versioning, rate limit).

### security-review / backend-security-coder
- Pakai saat: menyentuh auth, input user, endpoint API, pembayaran. Validasi input, otorisasi, throttle, jangan bocorkan rahasia.

### code-review / requesting-code-review / receiving-code-review
- Pakai saat: sebelum merge fitur besar (StockService, transaksi, API auth).

---

## 3. MCP & Dokumentasi

### laravel-boost (search-docs)
- WAJIB dipakai untuk mencari dokumentasi versi-spesifik Laravel/Filament/Livewire/Sanctum/Pest sebelum menebak API.

### context7
- Pakai untuk dokumentasi library di luar ekosistem Laravel jika perlu. `resolve-library-id` dulu, baru `query-docs`.

### mgrep
- WAJIB sebagai pengganti grep untuk pencarian kode semantik ("di mana StockService dipakai", "alur idempotency").

---

## 4. Pemetaan Tugas → Skill

| Tugas | Skill utama |
|-------|-------------|
| Migrasi & model inti (Fase 1) | laravel-best-practices, database-migrations, search-docs |
| StockService + audit stok | laravel-best-practices, test-driven-development |
| Filament Resources (Product, Transaction, dll) | livewire-development, tailwindcss-development, search-docs |
| REST API penjualan idempoten | api-design, laravel-best-practices, backend-security-coder |
| Auth API (Sanctum) | backend-security-coder, search-docs |
| Dashboard & laporan | laravel-best-practices (caching, query), tailwindcss-development |
| Debug race condition / drift stok | systematic-debugging |
| Sebelum merge / rilis | verification-before-completion, code-review |

---

## 5. Skill Keahlian Manusia (non-agent)

- Domain retail/kasir: alur penjualan, diskon, pajak, struk thermal (58/80mm), shift kas.
- Operasional MySQL/Redis: indexing, lock, failover, persistence.
- Docker compose: orkestrasi app/nginx/mysql/redis/queue (lihat `docker-compose.yml`).
- Mobile integration: konsumsi REST API + Sanctum token, penanganan offline ringan (idempotency).
