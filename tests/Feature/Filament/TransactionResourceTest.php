<?php

namespace Tests\Feature\Filament;

use App\Enums\TransactionStatus;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_transactions_list(): void
    {
        $owner = User::factory()->owner()->create();
        Transaction::create([
            'invoice_number' => 'INV-X',
            'user_id' => $owner->id,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'status' => TransactionStatus::Completed,
        ]);

        $this->actingAs($owner)->get('/admin/transactions')->assertOk();
    }
}
