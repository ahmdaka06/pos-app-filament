<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Completed = 'completed';
    case Void = 'void';
    case Refunded = 'refunded';
}
