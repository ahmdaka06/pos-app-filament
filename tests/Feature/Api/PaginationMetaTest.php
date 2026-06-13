<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginated_response_has_custom_meta_shape(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(5)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.page', 1)
            ->assertJsonMissingPath('links')
            ->assertJsonMissingPath('meta.current_page');
    }
}
