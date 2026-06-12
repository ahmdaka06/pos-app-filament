<?php

namespace App\Support;

class SaleTotals
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discountTotal,
        public readonly float $taxTotal,
        public readonly float $grandTotal,
        public readonly float $paidAmount,
        public readonly float $changeAmount,
        public readonly array $lines,
    ) {}

    public static function compute(array $items, float $discountTotal, float $taxPercent, float $paidAmount): self
    {
        $lines = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $price = (float) $item['price'];
            $quantity = (int) $item['quantity'];
            $discount = (float) ($item['discount'] ?? 0);

            $lineTotal = max(0.0, ($price * $quantity) - $discount);
            $subtotal += $lineTotal;

            $lines[] = [
                'price' => $price,
                'quantity' => $quantity,
                'discount' => $discount,
                'line_total' => $lineTotal,
            ];
        }

        $taxedBase = max(0.0, $subtotal - $discountTotal);
        $taxTotal = round($taxedBase * ($taxPercent / 100), 2);
        $grandTotal = max(0.0, $taxedBase + $taxTotal);
        $changeAmount = max(0.0, $paidAmount - $grandTotal);

        return new self(
            subtotal: round($subtotal, 2),
            discountTotal: round($discountTotal, 2),
            taxTotal: $taxTotal,
            grandTotal: round($grandTotal, 2),
            paidAmount: round($paidAmount, 2),
            changeAmount: round($changeAmount, 2),
            lines: $lines,
        );
    }
}
