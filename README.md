# POS App Filament

Aplikasi Point of Sale berbasis web yang dibangun dengan Laravel 13, Filament 5, dan Livewire 4. Mendukung manajemen produk, transaksi penjualan, laporan, dan REST API untuk integrasi klien eksternal.

## Fitur Utama

- Panel kasir (POS terminal) berbasis Livewire
- Manajemen produk, kategori, pelanggan, dan metode pembayaran
- Tracking stok otomatis dengan audit log
- Void & refund transaksi
- Laporan penjualan dengan export CSV
- REST API berversi (`/api/v1`) dengan autentikasi Sanctum
- Role-based access control: Owner, Manager, Cashier

## Tech Stack

- **PHP** 8.4
- **Laravel** 13
- **Filament** 5
- **Livewire** 4
- **TailwindCSS** 4
- **MySQL** 8

---

## Cara Install

### Opsi 1 — Laragon (Windows)

**Prasyarat:**
- [Laragon](https://laragon.org/download/) (Full, versi terbaru)
- PHP 8.4 (sudah termasuk di Laragon Full)
- Node.js 20+
- Composer

**Langkah instalasi:**

1. Clone repository ke folder `www` Laragon:

   ```bash
   cd C:/laragon/www
   git clone <repo-url> pos-app-filament
   cd pos-app-filament
   ```

2. Install dependensi PHP dan Node:

   ```bash
   composer install
   npm install
   ```

3. Salin file environment dan generate app key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Konfigurasi database di file `.env`:

   ```env
   APP_NAME="POS App"
   APP_URL=http://pos-app-filament.test

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pos_app_filament
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   > Laragon menggunakan `root` tanpa password secara default.

5. Buat database `pos_app_filament` melalui phpMyAdmin atau HeidiSQL di Laragon, lalu jalankan migrasi dan seeder:

   ```bash
   php artisan migrate --seed
   ```

6. Build assets frontend:

   ```bash
   npm run build
   ```

7. Buat symlink storage:

   ```bash
   php artisan storage:link
   ```

8. Buka browser dan akses `http://pos-app-filament.test` atau `http://localhost/pos-app-filament/public`.

---

### Opsi 2 — Docker

**Prasyarat:**
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Docker Compose v2

**Langkah instalasi:**

1. Clone repository:

   ```bash
   git clone <repo-url> pos-app-filament
   cd pos-app-filament
   ```

2. Salin file environment:

   ```bash
   cp .env.example .env
   ```

3. Sesuaikan `.env` untuk Docker:

   ```env
   APP_NAME="POS App"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=pos_app_filament
   DB_USERNAME=pos
   DB_PASSWORD=secret

   REDIS_HOST=redis
   REDIS_PORT=6379
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   ```

4. Build dan jalankan semua container:

   ```bash
   docker compose up -d --build
   ```

   Container yang akan berjalan:
   | Container     | Deskripsi              | Port          |
   | ------------- | ---------------------- | ------------- |
   | `pos_app`     | PHP-FPM 8.4            | —             |
   | `pos_nginx`   | Nginx web server       | `8000:80`     |
   | `pos_mysql`   | MySQL 8.0              | `3306:3306`   |
   | `pos_redis`   | Redis 7                | `6379:6379`   |
   | `pos_queue`   | Laravel Queue Worker   | —             |

5. Generate app key:

   ```bash
   docker compose exec app php artisan key:generate
   ```

6. Jalankan migrasi dan seeder:

   ```bash
   docker compose exec app php artisan migrate --seed
   ```

7. Build assets frontend:

   ```bash
   docker compose exec app npm install
   docker compose exec app npm run build
   ```

8. Buat symlink storage:

   ```bash
   docker compose exec app php artisan storage:link
   ```

9. Akses aplikasi di `http://localhost:8000`.

**Menghentikan container:**

```bash
docker compose down
```

**Menghapus semua data (termasuk database):**

```bash
docker compose down -v
```

---

## Data Login Default

Setelah menjalankan `php artisan migrate --seed`, tersedia 3 akun default:

| Role    | Email               | Password   | Akses                                              |
| ------- | ------------------- | ---------- | -------------------------------------------------- |
| Owner   | owner@pos.test      | `password` | Penuh — semua fitur termasuk manajemen pengguna    |
| Manager | manager@pos.test    | `password` | Produk, transaksi, laporan, void & refund          |
| Cashier | cashier@pos.test    | `password` | POS terminal dan melihat transaksi sendiri         |

---

## Akses Panel Admin

Setelah login, panel tersedia di:

```
http://localhost:8000/admin          (Docker)
http://pos-app-filament.test/admin   (Laragon)
```

### Navigasi Panel

| Menu              | Deskripsi                                   |
| ----------------- | ------------------------------------------- |
| Dashboard         | Ringkasan statistik penjualan hari ini      |
| POS               | Terminal kasir untuk transaksi penjualan    |
| Transactions      | Daftar transaksi, void, dan refund          |
| Products          | Manajemen produk dan stok                   |
| Categories        | Kategori produk                             |
| Customers         | Data pelanggan                              |
| Payment Methods   | Metode pembayaran                           |
| Stock Movements   | Audit log pergerakan stok                   |
| Sales Report      | Laporan penjualan dengan export CSV         |
| Store Settings    | Konfigurasi nama toko, pajak, dll           |
| Users             | Manajemen pengguna (Owner only)             |

---

## REST API

Base URL: `/api/v1`

**Autentikasi** menggunakan Laravel Sanctum (Bearer Token).

```bash
# Login
POST /api/v1/auth/login
Body: { "email": "owner@pos.test", "password": "password" }

# Gunakan token dari response di header:
Authorization: Bearer <token>
```

### Endpoint Tersedia

| Method | Endpoint                                   | Deskripsi                    |
| ------ | ------------------------------------------ | ---------------------------- |
| POST   | `/api/v1/auth/login`                       | Login, mendapat token        |
| GET    | `/api/v1/auth/me`                          | Info user yang login         |
| POST   | `/api/v1/auth/logout`                      | Logout, revoke token         |
| GET    | `/api/v1/products`                         | Daftar produk (paginasi)     |
| GET    | `/api/v1/products/{id}`                    | Detail produk                |
| GET    | `/api/v1/categories`                       | Daftar kategori              |
| GET    | `/api/v1/customers`                        | Daftar pelanggan             |
| POST   | `/api/v1/customers`                        | Tambah pelanggan             |
| GET    | `/api/v1/payment-methods`                  | Daftar metode pembayaran     |
| GET    | `/api/v1/transactions`                     | Daftar transaksi             |
| POST   | `/api/v1/transactions`                     | Buat transaksi baru          |
| GET    | `/api/v1/transactions/{id}`                | Detail transaksi             |
| PATCH  | `/api/v1/transactions/{id}/void`           | Void transaksi               |
| PATCH  | `/api/v1/transactions/{id}/refund`         | Refund transaksi             |
| GET    | `/api/v1/reports/summary`                  | Ringkasan laporan penjualan  |

---

## Menjalankan Tests

```bash
# Semua test
php artisan test --compact

# Test spesifik file
php artisan test --compact tests/Feature/Api/TransactionTest.php

# Filter nama test
php artisan test --compact --filter=testCreateTransaction
```

---

## Perintah Artisan Berguna

```bash
# Reset database dan seed ulang
php artisan migrate:fresh --seed

# Lihat semua route
php artisan route:list --except-vendor

# Jalankan queue worker (Laragon)
php artisan queue:work

# Format kode dengan Pint
vendor/bin/pint
```
