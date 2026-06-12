<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return true;
    }

    public function void(User $user, Transaction $transaction): bool
    {
        return $user->role->canManageTransactions();
    }

    public function refund(User $user, Transaction $transaction): bool
    {
        return $user->role->canManageTransactions();
    }
}
