<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::firstOrCreate([], [
            'store_name' => 'Toko POS',
            'address' => 'Jl. Contoh No. 123, Jakarta',
            'currency' => 'IDR',
            'tax_percent' => 0,
            'invoice_prefix' => 'INV',
            'receipt_footer' => 'Terima kasih telah berbelanja',
        ]);
    }
}
