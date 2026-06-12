<?php

namespace Tests\Feature\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_void_and_refund(): void
    {
        $manager = User::factory()->manager()->create();
        $tx = new Transaction;

        $this->assertTrue($manager->can('void', $tx));
        $this->assertTrue($manager->can('refund', $tx));
    }

    public function test_cashier_cannot_void_or_refund(): void
    {
        $cashier = User::factory()->cashier()->create();
        $tx = new Transaction;

        $this->assertFalse($cashier->can('void', $tx));
        $this->assertFalse($cashier->can('refund', $tx));
    }
}
