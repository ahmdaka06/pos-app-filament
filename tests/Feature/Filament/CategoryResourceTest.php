<?php

namespace Tests\Feature\Filament;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_categories_page(): void
    {
        $owner = User::factory()->owner()->create();
        Category::factory()->count(3)->create();

        $this->actingAs($owner)
            ->get('/admin/categories')
            ->assertOk();
    }
}
