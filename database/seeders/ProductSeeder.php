<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $makanan = Category::where('slug', 'makanan')->first()?->id;
        $minuman = Category::where('slug', 'minuman')->first()?->id;
        $snack = Category::where('slug', 'snack')->first()?->id;
        $rokok = Category::where('slug', 'rokok')->first()?->id;
        $sembako = Category::where('slug', 'sembako')->first()?->id;
        $atk = Category::where('slug', 'alat-tulis')->first()?->id;

        $products = [
            // Makanan
            ['category_id' => $makanan, 'name' => 'Nasi Goreng', 'price' => 15000, 'sku' => 'MKN-001', 'stock' => 50],
            ['category_id' => $makanan, 'name' => 'Mie Goreng', 'price' => 12000, 'sku' => 'MKN-002', 'stock' => 50],
            ['category_id' => $makanan, 'name' => 'Nasi Uduk', 'price' => 10000, 'sku' => 'MKN-003', 'stock' => 40],
            ['category_id' => $makanan, 'name' => 'Nasi Kuning', 'price' => 10000, 'sku' => 'MKN-004', 'stock' => 30],
            ['category_id' => $makanan, 'name' => 'Lontong Sayur', 'price' => 12000, 'sku' => 'MKN-005', 'stock' => 25],
            ['category_id' => $makanan, 'name' => 'Bubur Ayam', 'price' => 10000, 'sku' => 'MKN-006', 'stock' => 20],
            ['category_id' => $makanan, 'name' => 'Soto Ayam', 'price' => 13000, 'sku' => 'MKN-007', 'stock' => 20],
            ['category_id' => $makanan, 'name' => 'Bakso', 'price' => 15000, 'sku' => 'MKN-008', 'stock' => 30],

            // Minuman
            ['category_id' => $minuman, 'name' => 'Es Teh', 'price' => 5000, 'sku' => 'MNM-001', 'stock' => 100],
            ['category_id' => $minuman, 'name' => 'Es Jeruk', 'price' => 7000, 'sku' => 'MNM-002', 'stock' => 80],
            ['category_id' => $minuman, 'name' => 'Kopi Hitam', 'price' => 8000, 'sku' => 'MNM-003', 'stock' => 60],
            ['category_id' => $minuman, 'name' => 'Kopi Susu', 'price' => 10000, 'sku' => 'MNM-004', 'stock' => 60],
            ['category_id' => $minuman, 'name' => 'Air Mineral', 'price' => 3000, 'sku' => 'MNM-005', 'stock' => 200],
            ['category_id' => $minuman, 'name' => 'Jus Alpukat', 'price' => 12000, 'sku' => 'MNM-006', 'stock' => 30],
            ['category_id' => $minuman, 'name' => 'Jus Mangga', 'price' => 12000, 'sku' => 'MNM-007', 'stock' => 30],
            ['category_id' => $minuman, 'name' => 'Teh Botol', 'price' => 5000, 'sku' => 'MNM-008', 'stock' => 100],

            // Snack
            ['category_id' => $snack, 'name' => 'Kentang Goreng', 'price' => 10000, 'sku' => 'SNK-001', 'stock' => 40],
            ['category_id' => $snack, 'name' => 'Pisang Goreng', 'price' => 8000, 'sku' => 'SNK-002', 'stock' => 35],
            ['category_id' => $snack, 'name' => 'Tahu Goreng', 'price' => 5000, 'sku' => 'SNK-003', 'stock' => 40],
            ['category_id' => $snack, 'name' => 'Tempe Goreng', 'price' => 5000, 'sku' => 'SNK-004', 'stock' => 40],
            ['category_id' => $snack, 'name' => 'Roti Bakar', 'price' => 12000, 'sku' => 'SNK-005', 'stock' => 25],
            ['category_id' => $snack, 'name' => 'Cireng', 'price' => 7000, 'sku' => 'SNK-006', 'stock' => 30],
            ['category_id' => $snack, 'name' => 'Batagor', 'price' => 10000, 'sku' => 'SNK-007', 'stock' => 25],
            ['category_id' => $snack, 'name' => 'Siomay', 'price' => 10000, 'sku' => 'SNK-008', 'stock' => 25],

            // Rokok
            ['category_id' => $rokok, 'name' => 'Sampoerna Mild', 'price' => 35000, 'sku' => 'ROK-001', 'stock' => 50],
            ['category_id' => $rokok, 'name' => 'Dji Sam Soe', 'price' => 40000, 'sku' => 'ROK-002', 'stock' => 30],
            ['category_id' => $rokok, 'name' => 'Gudang Garam', 'price' => 30000, 'sku' => 'ROK-003', 'stock' => 40],
            ['category_id' => $rokok, 'name' => 'Marlboro', 'price' => 45000, 'sku' => 'ROK-004', 'stock' => 25],
            ['category_id' => $rokok, 'name' => 'L.A. Lights', 'price' => 32000, 'sku' => 'ROK-005', 'stock' => 35],
            ['category_id' => $rokok, 'name' => 'Esse', 'price' => 30000, 'sku' => 'ROK-006', 'stock' => 20],

            // Sembako
            ['category_id' => $sembako, 'name' => 'Beras 1kg', 'price' => 15000, 'sku' => 'SEM-001', 'stock' => 100],
            ['category_id' => $sembako, 'name' => 'Gula Pasir 1kg', 'price' => 14000, 'sku' => 'SEM-002', 'stock' => 80],
            ['category_id' => $sembako, 'name' => 'Minyak Goreng 1L', 'price' => 18000, 'sku' => 'SEM-003', 'stock' => 60],
            ['category_id' => $sembako, 'name' => 'Tepung Terigu 1kg', 'price' => 12000, 'sku' => 'SEM-004', 'stock' => 50],
            ['category_id' => $sembako, 'name' => 'Telur 1kg', 'price' => 28000, 'sku' => 'SEM-005', 'stock' => 40],
            ['category_id' => $sembako, 'name' => 'Susu Kental Manis', 'price' => 12000, 'sku' => 'SEM-006', 'stock' => 60],
            ['category_id' => $sembako, 'name' => 'Kopi Bubuk', 'price' => 15000, 'sku' => 'SEM-007', 'stock' => 40],
            ['category_id' => $sembako, 'name' => 'Kecap Manis', 'price' => 8000, 'sku' => 'SEM-008', 'stock' => 45],

            // Alat Tulis
            ['category_id' => $atk, 'name' => 'Pulpen', 'price' => 3000, 'sku' => 'ATK-001', 'stock' => 200],
            ['category_id' => $atk, 'name' => 'Pensil', 'price' => 2000, 'sku' => 'ATK-002', 'stock' => 150],
            ['category_id' => $atk, 'name' => 'Penghapus', 'price' => 2000, 'sku' => 'ATK-003', 'stock' => 100],
            ['category_id' => $atk, 'name' => 'Buku Tulis', 'price' => 5000, 'sku' => 'ATK-004', 'stock' => 100],
            ['category_id' => $atk, 'name' => 'Spidol', 'price' => 8000, 'sku' => 'ATK-005', 'stock' => 50],
            ['category_id' => $atk, 'name' => 'Lem Kertas', 'price' => 5000, 'sku' => 'ATK-006', 'stock' => 40],
            ['category_id' => $atk, 'name' => 'Gunting', 'price' => 10000, 'sku' => 'ATK-007', 'stock' => 30],
            ['category_id' => $atk, 'name' => 'Isolasi', 'price' => 7000, 'sku' => 'ATK-008', 'stock' => 35],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $product['category_id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'cost_price' => round($product['price'] * 0.7),
                    'is_active' => true,
                    'unit' => 'pcs',
                    'track_stock' => true,
                    'allow_backorder' => false,
                    'reorder_level' => 10,
                ],
            );
        }
    }
}
