<?php

namespace Tests\Unit\Support;

use App\Support\SaleTotals;
use Tests\TestCase;

class SaleTotalsTest extends TestCase
{
    public function test_computes_totals_with_line_discounts_order_discount_and_tax(): void
    {
        $items = [
            ['price' => 10000, 'quantity' => 2, 'discount' => 0],
            ['price' => 5000, 'quantity' => 1, 'discount' => 1000],
        ];

        $totals = SaleTotals::compute($items, discountTotal: 0, taxPercent: 10, paidAmount: 30000);

        $this->assertSame(24000.0, $totals->subtotal);
        $this->assertSame(2400.0, $totals->taxTotal);
        $this->assertSame(26400.0, $totals->grandTotal);
        $this->assertSame(3600.0, $totals->changeAmount);
    }

    public function test_line_total_never_negative_and_change_clamped(): void
    {
        $items = [['price' => 1000, 'quantity' => 1, 'discount' => 5000]];

        $totals = SaleTotals::compute($items, discountTotal: 0, taxPercent: 0, paidAmount: 0);

        $this->assertSame(0.0, $totals->subtotal);
        $this->assertSame(0.0, $totals->changeAmount);
    }
}
