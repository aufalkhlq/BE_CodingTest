{
  "email": "test@example.com",
  "password": "password"
}
```

Respons berisi `access_token`. Token ini statis untuk simulasi, bukan autentikasi produksi. Token statis hanya dapat dipakai untuk endpoint baca (`GET /api/products`, detail, dan statistik):

```http
Authorization: Bearer <access_token>
```

Route tulis (`POST`, `PUT`, `PATCH`, `DELETE`) memakai `auth:sanctum`, sehingga memerlukan sesi Sanctum atau personal access token Sanctum. Karena login simulasi sengaja tetap mengembalikan token statis, nilai token dari `/api/login` **tidak** mengautentikasi route tulis. Untuk pengujian lokal, buat token Sanctum melalui Tinker setelah migrasi:

```powershell
php artisan tinker
```

```php
App\Models\User::where('email', 'test@example.com')
    ->firstOrFail()
    ->createToken('api')
    ->plainTextToken;
```

## Endpoint

| Method | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/api/login` | Login dan mendapatkan token statis |
| `GET` | `/api/products` | Daftar produk dengan pagination |
| `POST` | `/api/products` | Menambahkan produk |
| `GET` | `/api/products/{id}` | Melihat detail produk |
| `PUT`, `PATCH` | `/api/products/{id}` | Mengubah produk |
| `DELETE` | `/api/products/{id}` | Menghapus produk |
| `GET` | `/api/products/stats` | Statistik jumlah produk |

Endpoint baca memerlukan token bearer statis dari login; endpoint tulis memerlukan autentikasi Sanctum. Gunakan parameter berikut pada `GET /api/products`:

- `search`: mencari teks pada nama **atau** kategori.
- `status`: filter dengan nilai `available` atau `out_of_stock`.
- `page`: nomor halaman bawaan Laravel.
- `per_page`: jumlah item per halaman, antara 1 dan 100 (default 10).

Contoh:

```http
GET /api/products?search=elektronik&status=available&per_page=5
```

Contoh payload untuk membuat produk:

```json
{
  "name": "Kamera Digital",
  "description": "Kamera untuk kebutuhan sehari-hari.",
  "price": 2500000,
  "category": "Elektronik",
  "status": "available"
}
```

Payload produk menggunakan field `name`, `description`, `price`, `category`, dan `status`. Harga harus angka non-negatif, sedangkan status harus `available` atau `out_of_stock`. Respons daftar menggunakan format resource Laravel dengan `data`, `links`, dan `meta`.

## Pengujian

```powershell
php artisan test
```

Pengujian menggunakan database SQLite in-memory. Pastikan ekstensi `pdo_sqlite` aktif pada PHP CLI yang dipakai untuk menjalankan tes.
