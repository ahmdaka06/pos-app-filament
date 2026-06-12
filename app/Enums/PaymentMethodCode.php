<?php

namespace App\Enums;

enum PaymentMethodCode: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Qris = 'qris';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return array_map(fn (self $case) => $case->label(), self::cases());
    }
}
