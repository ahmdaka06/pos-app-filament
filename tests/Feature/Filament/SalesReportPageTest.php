<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SalesReportPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'owner', 'is_active' => true]);
    }

    public function test_authenticated_user_can_access_sales_report_page(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/sales-report')
            ->assertOk()
            ->assertSee('Sales Report');
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get('/admin/sales-report')
            ->assertRedirect();
    }

    public function test_page_shows_date_filter_and_summary_cards(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/sales-report')
            ->assertOk()
            ->assertSee('Total Transactions')
            ->assertSee('Total Revenue')
            ->assertSee('Average Transaction')
            ->assertSee('Export CSV');
    }
}
