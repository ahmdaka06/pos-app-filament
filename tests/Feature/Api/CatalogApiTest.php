<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_listing_is_paginated_and_searchable(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Kopi Hitam']);
        Product::factory()->create(['name' => 'Teh Manis']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products?search=Kopi')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'price', 'stock']], 'meta'])
            ->assertJsonMissingPath('links')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.limit', 20)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.total_pages', 1);
    }

    public function test_categories_listing_requires_auth(): void
    {
        $this->getJson('/api/v1/categories')->assertStatus(401);
    }

    public function test_can_create_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/customers', ['name' => 'Andi', 'phone' => '0811'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Andi');
    }
}
