<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Laptop Pro 14',
                'description' => 'Laptop ringan untuk pekerjaan dan kebutuhan profesional.',
                'price' => 18999000,
                'category' => 'Elektronik',
                'status' => ProductStatus::Available,
            ],
            [
                'name' => 'Mouse Wireless',
                'description' => 'Mouse nirkabel dengan koneksi 2.4 GHz.',
                'price' => 249000,
                'category' => 'Aksesori',
                'status' => ProductStatus::Available,
            ],
            [
                'name' => 'Keyboard Mechanical',
                'description' => 'Keyboard mekanis dengan lampu latar.',
                'price' => 899000,
                'category' => 'Aksesori',
                'status' => ProductStatus::OutOfStock,
            ],
            [
                'name' => 'Headphone Bluetooth',
                'description' => 'Headphone nirkabel dengan peredam bising.',
                'price' => 1299000,
                'category' => 'Audio',
                'status' => ProductStatus::Available,
            ],
            [
                'name' => 'Monitor 27 Inch',
                'description' => 'Monitor IPS resolusi QHD untuk produktivitas.',
                'price' => 3799000,
                'category' => 'Elektronik',
                'status' => ProductStatus::OutOfStock,
            ],
            [
                'name' => 'Webcam HD',
                'description' => 'Webcam USB untuk rapat dan konferensi video.',
                'price' => 549000,
                'category' => 'Aksesori',
                'status' => ProductStatus::Available,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->create($product);
        }
    }
}
