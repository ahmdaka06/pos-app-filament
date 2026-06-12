<?php

namespace Tests\Unit\Enums;

use App\Enums\StockMovementType;
use Tests\TestCase;

class StockMovementTypeTest extends TestCase
{
    public function test_has_expected_cases(): void
    {
        $this->assertSame('sale', StockMovementType::Sale->value);
        $this->assertSame('void', StockMovementType::Void->value);
        $this->assertSame('refund', StockMovementType::Refund->value);
        $this->assertSame('adjustment', StockMovementType::Adjustment->value);
    }
}
