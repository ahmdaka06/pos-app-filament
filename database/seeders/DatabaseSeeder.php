<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->owner()->create([
            'name' => 'Owner',
            'email' => 'owner@pos.test',
        ]);

        User::factory()->manager()->create([
            'name' => 'Manager',
            'email' => 'manager@pos.test',
        ]);

        User::factory()->cashier()->create([
            'name' => 'Cashier',
            'email' => 'cashier@pos.test',
        ]);

        PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Cash']);
        PaymentMethod::firstOrCreate(['code' => 'transfer'], ['name' => 'Bank Transfer']);
        PaymentMethod::firstOrCreate(['code' => 'qris'], ['name' => 'QRIS']);

        StoreSetting::firstOrCreate([], [
            'store_name' => 'POS Store',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'invoice_prefix' => 'INV',
        ]);
    }
}
