<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_owner_and_manager_can_manage_transactions(): void
    {
        $this->assertTrue(UserRole::Owner->canManageTransactions());
        $this->assertTrue(UserRole::Manager->canManageTransactions());
    }

    public function test_cashier_cannot_manage_transactions(): void
    {
        $this->assertFalse(UserRole::Cashier->canManageTransactions());
    }
}
