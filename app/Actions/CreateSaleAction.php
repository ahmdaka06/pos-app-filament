<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StockService;
use App\Support\SaleTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSaleAction
{
    public function __construct(private readonly StockService $stock) {}

    public function execute(array $data, User $cashier, ?string $idempotencyKey): Transaction
    {
        if ($idempotencyKey) {
            $existing = Transaction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $store = StoreSetting::current();

        return DB::transaction(function () use ($data, $cashier, $idempotencyKey, $store) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
                ->get()
                ->keyBy('id');

            $itemsForTotals = [];
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $itemsForTotals[] = [
                    'price' => (float) $product->price,
                    'quantity' => (int) $item['quantity'],
                    'discount' => (float) ($item['discount'] ?? 0),
                ];
            }

            $totals = SaleTotals::compute(
                $itemsForTotals,
                (float) ($data['discount_total'] ?? 0),
                (float) $store->tax_percent,
                (float) $data['paid_amount'],
            );

            $transaction = Transaction::create([
                'invoice_number' => $this->generateInvoiceNumber($store->invoice_prefix),
                'user_id' => $cashier->id,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'],
                'status' => TransactionStatus::Completed,
                'subtotal' => $totals->subtotal,
                'discount_total' => $totals->discountTotal,
                'tax_total' => $totals->taxTotal,
                'grand_total' => $totals->grandTotal,
                'paid_amount' => $totals->paidAmount,
                'change_amount' => $totals->changeAmount,
                'idempotency_key' => $idempotencyKey,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $index => $item) {
                $product = $products[$item['product_id']];
                $line = $totals->lines[$index];

                $transaction->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'discount' => $line['discount'],
                    'line_total' => $line['line_total'],
                ]);

                $this->stock->applyMovement(
                    $product,
                    StockMovementType::Sale,
                    -1 * (int) $item['quantity'],
                    $transaction,
                    null,
                    $cashier,
                );
            }

            return $transaction;
        });
    }

    private function generateInvoiceNumber(string $prefix): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }
}
