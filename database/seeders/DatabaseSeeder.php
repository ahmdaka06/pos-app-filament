<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StoreSettingSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            PaymentMethodSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
