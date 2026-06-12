<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'barcode' => fake()->unique()->ean13(),
            'name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 1000, 100000),
            'cost_price' => fake()->randomFloat(2, 500, 50000),
            'unit' => 'pcs',
            'stock' => fake()->numberBetween(0, 100),
            'reorder_level' => 5,
            'track_stock' => true,
            'allow_backorder' => false,
            'is_active' => true,
        ];
    }
}
