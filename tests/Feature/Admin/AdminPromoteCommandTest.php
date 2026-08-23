<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromoteCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotes_a_regular_user_to_admin(): void
    {
        $user = User::factory()->create(['email' => 'future-admin@example.com']);

        $this->artisan('admin:promote', ['email' => 'future-admin@example.com'])
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $user->refresh()->role);
    }

    public function test_fails_for_unknown_email(): void
    {
        $this->artisan('admin:promote', ['email' => 'nobody@example.com'])
            ->assertFailed();
    }

    public function test_is_idempotent_for_existing_admins(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'already@example.com']);

        $this->artisan('admin:promote', ['email' => 'already@example.com'])
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }
}
