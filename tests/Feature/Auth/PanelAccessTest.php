<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_inactive_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_role_is_cast_to_enum(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $this->assertSame(UserRole::Manager, $user->role);
    }
}
