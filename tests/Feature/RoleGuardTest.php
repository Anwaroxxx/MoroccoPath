<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_does_not_have_admin_access(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->can('access-admin'));
        $this->assertTrue($user->cannot('access-admin'));
    }

    public function test_admin_bypasses_gates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->can('access-admin'));
        // Admins bypass every defined gate.
        $this->assertTrue($admin->can('some-undefined-gate'));
    }
}
