<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_stock_movements_list(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin/stock-movements')->assertOk();
    }
}
