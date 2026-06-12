<?php

namespace Tests\Feature\Api;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_counts_only_completed_today(): void
    {
        $user = User::factory()->create();
        $pm = PaymentMethod::factory()->create();

        Transaction::create(['invoice_number' => 'A', 'user_id' => $user->id, 'payment_method_id' => $pm->id, 'status' => 'completed', 'grand_total' => 10000]);
        Transaction::create(['invoice_number' => 'B', 'user_id' => $user->id, 'payment_method_id' => $pm->id, 'status' => 'void', 'grand_total' => 99999]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.transaction_count', 1)
            ->assertJsonPath('data.total_sales', 10000);
    }
}
