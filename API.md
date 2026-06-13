# API Documentation

Base URL: `/api/v1`

Semua endpoint kecuali login memerlukan autentikasi menggunakan **Laravel Sanctum Bearer Token**.

```
Authorization: Bearer <token>
```

---

## Autentikasi

### Login

```
POST /api/v1/auth/login
```

Mendapatkan token akses. Endpoint ini dibatasi **5 request per menit** (throttle).

**Request Body**

| Field | Tipe | Wajib | Keterangan |
| ----------- | ------ | ----- | ------------------- |
| email | string | Ya | Email pengguna |
| password | string | Ya | Password pengguna |
| device_name | string | Ya | Nama perangkat/klien |

**Response `200 OK`**

```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Cashier",
    "email": "cashier@pos.test",
    "role": "cashier"
  }
}
```

**Response `422 Unprocessable Entity`** — kredensial salah atau akun tidak aktif.

---

### Info Pengguna

```
GET /api/v1/auth/me
```

Mendapatkan data pengguna yang sedang login.

**Response `200 OK`**

```json
{
  "data": {
    "id": 1,
    "name": "Cashier",
    "email": "cashier@pos.test",
    "role": "cashier"
  }
}
```

---

### Logout

```
POST /api/v1/auth/logout
```

Mencabut token yang sedang digunakan.

**Response `200 OK`**

```json
{
  "message": "Logged out"
}
```

---

## Kategori

### Daftar Kategori

```
GET /api/v1/categories
```

Mengembalikan semua kategori yang aktif, diurutkan berdasarkan nama.

**Response `200 OK`**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Minuman",
      "slug": "minuman",
      "is_active": true
    }
  ]
}
```

---

## Produk

### Daftar Produk

```
GET /api/v1/products
```

Mengembalikan produk aktif dengan paginasi (20 per halaman).

**Query Parameters**

| Parameter   | Tipe    | Keterangan |
| ----------- | ------- | -------------------------------------------- |
| search      | string  | Cari berdasarkan nama atau SKU |
| barcode     | string  | Filter berdasarkan barcode (exact match) |
| category_id | integer | Filter berdasarkan kategori |
| page        | integer | Nomor halaman |

**Response `200 OK`**

```json
{
  "data": [
    {
      "id": 1,
      "sku": "SKU-0001",
      "barcode": "8991234567890",
      "name": "Kopi Hitam",
      "price": "15000.00",
      "unit": "pcs",
      "stock": 50,
      "category_id": 1,
      "image_url": "http://localhost/storage/products/kopi.jpg",
      "is_active": true
    }
  ],
  "links": { "..." },
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45
  }
}
```

---

### Detail Produk

```
GET /api/v1/products/{id}
```

**Response `200 OK`** — sama dengan item di daftar produk.

**Response `404 Not Found`** — produk tidak ditemukan.

---

## Metode Pembayaran

### Daftar Metode Pembayaran

```
GET /api/v1/payment-methods
```

Mengembalikan semua metode pembayaran yang aktif, diurutkan berdasarkan nama.

**Response `200 OK`**

```json
{
  "data": [
    {
      "id": 1,
      "code": "cash",
      "name": "Cash"
    },
    {
      "id": 2,
      "code": "qris",
      "name": "QRIS"
    }
  ]
}
```

---

## Pelanggan

### Daftar Pelanggan

```
GET /api/v1/customers
```

Mengembalikan pelanggan dengan paginasi (20 per halaman).

**Query Parameters**

| Parameter | Tipe   | Keterangan |
| --------- | ------ | ------------------------------------------ |
| search    | string | Cari berdasarkan nama atau nomor telepon |
| page      | integer | Nomor halaman |

**Response `200 OK`**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Andi Susanto",
      "phone": "08112345678",
      "email": "andi@example.com"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Tambah Pelanggan

```
POST /api/v1/customers
```

**Request Body**

| Field | Tipe   | Wajib | Keterangan |
| ----- | ------ | ----- | ---------- |
| name  | string | Ya    | Nama pelanggan (maks 255) |
| phone | string | Tidak | Nomor telepon (maks 32) |
| email | string | Tidak | Email pelanggan |
| note  | string | Tidak | Catatan (maks 255) |

**Response `201 Created`**

```json
{
  "data": {
    "id": 5,
    "name": "Budi Santoso",
    "phone": "08129876543",
    "email": null
  }
}
```

---

## Transaksi

### Daftar Transaksi

```
GET /api/v1/transactions
```

**Query Parameters**

| Parameter | Tipe    | Keterangan |
| --------- | ------- | --------------------------------------- |
| mine      | boolean | Jika `1`, hanya transaksi milik user yang login |
| status    | string  | Filter status: `completed`, `void`, `refunded` |
| page      | integer | Nomor halaman |

**Response `200 OK`**

```json
{
  "data": [
    {
      "id": 1,
      "invoice_number": "INV-20260612-ABCD",
      "status": "completed",
      "subtotal": "50000.00",
      "discount_total": "0.00",
      "tax_total": "0.00",
      "grand_total": "50000.00",
      "paid_amount": "100000.00",
      "change_amount": "50000.00",
      "customer_id": null,
      "payment_method_id": 1,
      "created_at": "2026-06-12T10:00:00+07:00"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Buat Transaksi

```
POST /api/v1/transactions
```

**Header Idempotency-Key** (opsional tapi direkomendasikan): mencegah duplikasi transaksi jika request diulang karena network error.

```
Idempotency-Key: <unique-string>
```

Jika key yang sama dikirim ulang, endpoint mengembalikan transaksi yang sudah ada dengan status `200` (bukan `201` baru).

**Request Body**

| Field                  | Tipe    | Wajib | Keterangan |
| ---------------------- | ------- | ----- | ---------- |
| payment_method_id      | integer | Ya    | ID metode pembayaran |
| paid_amount            | numeric | Ya    | Jumlah uang yang dibayar |
| items                  | array   | Ya    | Minimal 1 item |
| items[].product_id     | integer | Ya    | ID produk |
| items[].quantity       | integer | Ya    | Jumlah (min 1) |
| items[].discount       | numeric | Tidak | Diskon per baris (nominal) |
| customer_id            | integer | Tidak | ID pelanggan |
| discount_total         | numeric | Tidak | Diskon keseluruhan order |
| note                   | string  | Tidak | Catatan transaksi |

**Response `201 Created`** — transaksi baru berhasil dibuat.

**Response `200 OK`** — idempotency key cocok, mengembalikan transaksi yang sudah ada.

```json
{
  "data": {
    "id": 1,
    "invoice_number": "INV-20260612-ABCD",
    "status": "completed",
    "subtotal": "50000.00",
    "discount_total": "0.00",
    "tax_total": "5000.00",
    "grand_total": "55000.00",
    "paid_amount": "60000.00",
    "change_amount": "5000.00",
    "customer_id": null,
    "payment_method_id": 1,
    "created_at": "2026-06-12T10:00:00+07:00",
    "items": [
      {
        "product_id": 1,
        "product_name": "Kopi Hitam",
        "sku": "SKU-0001",
        "price": "15000.00",
        "quantity": 2,
        "discount": "0.00",
        "line_total": "30000.00"
      }
    ]
  }
}
```

**Response `422 Unprocessable Entity`** — validasi gagal atau stok tidak mencukupi.

```json
{
  "message": "Stok tidak mencukupi untuk SKU-0001. Tersedia: 1",
  "errors": {
    "items": ["Stok tidak mencukupi untuk SKU-0001. Tersedia: 1"]
  }
}
```

---

### Detail Transaksi

```
GET /api/v1/transactions/{id}
```

Mengembalikan detail transaksi beserta item dan data receipt untuk keperluan struk.

**Response `200 OK`** — sama dengan response buat transaksi, ditambah field `receipt`:

```json
{
  "data": {
    "id": 1,
    "invoice_number": "INV-20260612-ABCD",
    "...": "...",
    "items": [ "..." ],
    "receipt": {
      "store": {
        "name": "POS Store",
        "address": "Jl. Contoh No. 1",
        "footer": "Terima kasih!"
      },
      "invoice_number": "INV-20260612-ABCD",
      "date": "2026-06-12T10:00:00+07:00",
      "items": [ "..." ],
      "subtotal": "50000.00",
      "discount_total": "0.00",
      "tax_total": "5000.00",
      "grand_total": "55000.00",
      "paid_amount": "60000.00",
      "change_amount": "5000.00",
      "status": "completed"
    }
  }
}
```

---

### Void Transaksi

```
POST /api/v1/transactions/{id}/void
```

Membatalkan transaksi dan mengembalikan stok produk. Hanya transaksi dengan status `completed` yang bisa di-void.

**Otorisasi:** Hanya `owner` dan `manager`.

**Response `200 OK`** — transaksi berhasil di-void, status berubah menjadi `void`.

**Response `403 Forbidden`** — user tidak memiliki izin.

**Response `500 Server Error`** — transaksi sudah di-void atau di-refund sebelumnya.

---

### Refund Transaksi

```
POST /api/v1/transactions/{id}/refund
```

Melakukan refund penuh dan mengembalikan stok produk. Hanya transaksi dengan status `completed` yang bisa di-refund.

**Otorisasi:** Hanya `owner` dan `manager`.

**Response `200 OK`** — transaksi berhasil di-refund, status berubah menjadi `refunded`.

**Response `403 Forbidden`** — user tidak memiliki izin.

---

## Laporan

### Ringkasan Penjualan

```
GET /api/v1/reports/summary
```

Mengembalikan ringkasan penjualan untuk tanggal tertentu. Hanya menghitung transaksi dengan status `completed`.

**Query Parameters**

| Parameter | Tipe | Keterangan |
| --------- | ---- | --------------------------------------- |
| date      | date | Tanggal (format `YYYY-MM-DD`). Default: hari ini |

**Response `200 OK`**

```json
{
  "data": {
    "date": "2026-06-12",
    "transaction_count": 15,
    "total_sales": 750000,
    "average_order_value": 50000
  }
}
```

---

## Status HTTP

| Kode | Keterangan |
| ---- | ---------- |
| 200  | OK — request berhasil |
| 201  | Created — resource baru berhasil dibuat |
| 401  | Unauthorized — token tidak ada atau tidak valid |
| 403  | Forbidden — tidak memiliki izin |
| 404  | Not Found — resource tidak ditemukan |
| 422  | Unprocessable Entity — validasi gagal atau stok kurang |
| 429  | Too Many Requests — rate limit login terlampaui |
| 500  | Server Error — error tidak terduga |

---

## Contoh Penggunaan (curl)

```bash
# 1. Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"cashier@pos.test","password":"password","device_name":"mobile"}'

# 2. Simpan token dari response
TOKEN="1|abc123..."

# 3. Ambil daftar produk
curl http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer $TOKEN"

# 4. Buat transaksi
curl -X POST http://localhost:8000/api/v1/transactions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: order-$(date +%s)" \
  -d '{
    "payment_method_id": 1,
    "paid_amount": 100000,
    "items": [
      {"product_id": 1, "quantity": 2}
    ]
  }'

# 5. Void transaksi (manager/owner only)
curl -X POST http://localhost:8000/api/v1/transactions/1/void \
  -H "Authorization: Bearer $MANAGER_TOKEN"

# 6. Laporan penjualan hari ini
curl http://localhost:8000/api/v1/reports/summary \
  -H "Authorization: Bearer $TOKEN"
```
