<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case Void = 'void';
    case Refund = 'refund';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case Opname = 'opname';
}
