<?php

namespace Tests\Feature\Admin;

use App\Models\Institution;
use App\Models\Program;
use App\Models\Source;
use App\Models\SourceReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec §33: admin functionality must never be reachable by guests or
 * regular users. Every admin route is checked here.
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/admin', '/admin/review-queue', '/admin/institutions', '/admin/programs', '/admin/sources'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_regular_users_are_forbidden(): void
    {
        $user = User::factory()->create();

        foreach (['/admin', '/admin/review-queue', '/admin/institutions', '/admin/programs', '/admin/sources'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_admins_can_access_all_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['/admin', '/admin/review-queue', '/admin/institutions', '/admin/programs', '/admin/sources'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_regular_users_cannot_change_verification_status(): void
    {
        $user = User::factory()->create();
        $reference = $this->makeReference();

        $this->actingAs($user)
            ->patch("/admin/source-references/{$reference->id}", ['verification_status' => 'verified'])
            ->assertForbidden();

        $this->assertSame('needs_review', $reference->refresh()->verification_status->value);
    }

    public function test_admin_can_verify_a_reference(): void
    {
        $admin = User::factory()->admin()->create();
        $reference = $this->makeReference();

        $this->assertNull($reference->last_verified_at);

        $this->actingAs($admin)
            ->patch("/admin/source-references/{$reference->id}", ['verification_status' => 'verified'])
            ->assertRedirect();

        $reference->refresh();
        $this->assertSame('verified', $reference->verification_status->value);
        $this->assertNotNull($reference->last_verified_at);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $reference = $this->makeReference();

        $this->actingAs($admin)
            ->from('/admin/review-queue')
            ->patch("/admin/source-references/{$reference->id}", ['verification_status' => 'made-up-status'])
            ->assertSessionHasErrors('verification_status');

        $this->assertSame('needs_review', $reference->refresh()->verification_status->value);
    }

    private function makeReference(): SourceReference
    {
        $institution = Institution::create([
            'name' => 'Admin test institution',
            'canonical_name' => 'Admin test institution',
            'slug' => 'admin-test-institution',
            'type' => 'coding_school',
            'status' => 'unknown',
        ]);

        Program::create([
            'institution_id' => $institution->id,
            'name' => 'Admin test program',
            'slug' => 'admin-test-program',
            'study_mode' => 'full_time',
            'status' => 'draft',
        ]);

        $source = Source::create([
            'name' => 'Admin test source',
            'slug' => 'admin-test-source',
            'type' => 'official_institution',
            'trust_level' => 2,
        ]);

        return SourceReference::create([
            'source_id' => $source->id,
            'referencable_type' => Institution::class,
            'referencable_id' => $institution->id,
            'academic_year' => '2026/2027',
            'verification_status' => 'needs_review',
        ]);
    }
}
