<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@example.com'],
            ['name' => 'Siti Rahayu', 'phone' => '081234567891', 'email' => 'siti@example.com'],
            ['name' => 'Ahmad Hidayat', 'phone' => '081234567892', 'email' => 'ahmad@example.com'],
            ['name' => 'Dewi Lestari', 'phone' => '081234567893', 'email' => 'dewi@example.com'],
            ['name' => 'Rudi Hartono', 'phone' => '081234567894', 'email' => 'rudi@example.com'],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(
                ['phone' => $customer['phone']],
                $customer,
            );
        }
    }
}
