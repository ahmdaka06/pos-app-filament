<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Cashier = 'cashier';

    public function canManageTransactions(): bool
    {
        return in_array($this, [self::Owner, self::Manager], true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
