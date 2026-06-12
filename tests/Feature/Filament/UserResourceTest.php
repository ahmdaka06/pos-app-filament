<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_users(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin/users')->assertOk();
    }

    public function test_cashier_cannot_list_users(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/admin/users')->assertForbidden();
    }

    public function test_owner_can_create_user(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin/users/create')->assertOk();
    }
}
