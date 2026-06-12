<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'cash', 'name' => 'Cash'],
            ['code' => 'transfer', 'name' => 'Bank Transfer'],
            ['code' => 'qris', 'name' => 'QRIS'],
            ['code' => 'debit', 'name' => 'Kartu Debit'],
            ['code' => 'credit', 'name' => 'Kartu Kredit'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['code' => $method['code']],
                ['name' => $method['name'], 'is_active' => true],
            );
        }
    }
}
