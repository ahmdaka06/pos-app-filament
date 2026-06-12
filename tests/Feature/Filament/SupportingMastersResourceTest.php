<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportingMastersResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_customers(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin/customers')->assertOk();
    }

    public function test_owner_can_list_payment_methods(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin/payment-methods')->assertOk();
    }

    public function test_owner_can_access_store_settings(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin/manage-store-settings')->assertOk();
    }
}
