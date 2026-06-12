<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoidTransactionAction
{
    public function __construct(private readonly StockService $stock) {}

    public function execute(Transaction $transaction, User $actor): Transaction
    {
        if ($transaction->status !== TransactionStatus::Completed) {
            throw new RuntimeException('Only completed transactions can be voided.');
        }

        return DB::transaction(function () use ($transaction, $actor) {
            $transaction->loadMissing('items');

            foreach ($transaction->items as $item) {
                if ($item->product_id) {
                    $this->stock->applyMovement(
                        $item->product->refresh(),
                        StockMovementType::Void,
                        (int) $item->quantity,
                        $transaction,
                        'void',
                        $actor,
                    );
                }
            }

            $transaction->update([
                'status' => TransactionStatus::Void,
                'voided_at' => now(),
                'voided_by' => $actor->id,
            ]);

            return $transaction;
        });
    }
}
