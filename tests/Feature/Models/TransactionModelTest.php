<?php

namespace Tests\Feature\Models;

use App\Enums\TransactionStatus;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_cast_to_enum_and_mine_scope_filters_by_user(): void
    {
        $cashier = User::factory()->cashier()->create();
        $other = User::factory()->cashier()->create();
        $pm = PaymentMethod::factory()->create();

        Transaction::create([
            'invoice_number' => 'INV-1',
            'user_id' => $cashier->id,
            'payment_method_id' => $pm->id,
            'status' => 'completed',
        ]);
        Transaction::create([
            'invoice_number' => 'INV-2',
            'user_id' => $other->id,
            'payment_method_id' => $pm->id,
            'status' => 'completed',
        ]);

        $mine = Transaction::mine($cashier->id)->get();

        $this->assertCount(1, $mine);
        $this->assertSame(TransactionStatus::Completed, $mine->first()->status);
    }
}
