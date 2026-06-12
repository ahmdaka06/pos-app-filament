<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->canManageTransactions() ?? false;
    }

    public function view(User $user, User $model): bool
    {
        return $user->role?->canManageTransactions() ?? false;
    }

    public function create(User $user): bool
    {
        return $user->role?->canManageTransactions() ?? false;
    }

    public function update(User $user, User $model): bool
    {
        return $user->role?->canManageTransactions() ?? false;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role?->canManageTransactions() ?? false;
    }
}
