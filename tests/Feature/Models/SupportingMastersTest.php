<?php

namespace Tests\Feature\Models;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportingMastersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_search_scope_matches_name_or_phone(): void
    {
        Customer::factory()->create(['name' => 'Andi', 'phone' => '0811']);
        Customer::factory()->create(['name' => 'Budi', 'phone' => '0822']);

        $this->assertCount(1, Customer::search('Andi')->get());
        $this->assertCount(1, Customer::search('0822')->get());
    }

    public function test_payment_method_active_scope(): void
    {
        PaymentMethod::factory()->create(['is_active' => true]);
        PaymentMethod::factory()->create(['is_active' => false]);

        $this->assertCount(1, PaymentMethod::active()->get());
    }

    public function test_store_setting_current_returns_single_row_cached(): void
    {
        StoreSetting::create(['store_name' => 'POS One']);

        $this->assertSame('POS One', StoreSetting::current()->store_name);
    }
}
