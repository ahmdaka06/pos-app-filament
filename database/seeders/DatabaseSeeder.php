<?php

namespace Database\Seeders;

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
    }
}
