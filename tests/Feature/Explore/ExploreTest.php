<?php

namespace Tests\Feature\Explore;

use App\Enums\ProgramStatus;
use App\Models\Campus;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Interest;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec §21/§29: public pages show only PUBLISHED records. Drafts stay
 * invisible until an administrator publishes them.
 */
class ExploreTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Public test school',
            'canonical_name' => 'Public test school',
            'slug' => 'public-test-school',
            'type' => 'coding_school',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'Main campus',
            'city' => 'Casablanca',
            'region' => 'Casablanca-Settat',
        ]);
    }

    private function makeProgram(string $slug, string $status): Program
    {
        return Program::create([
            'institution_id' => $this->institution->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
            'study_mode' => 'full_time',
            'status' => $status,
        ]);
    }

    public function test_guests_can_browse_published_programs(): void
    {
        $this->makeProgram('visible-program', 'published');
        $this->makeProgram('hidden-program', 'draft');

        $response = $this->get('/programs');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('programs/index')
                ->where('programs.data.0.slug', 'visible-program')
                ->missing('programs.data.1'),
        );
    }

    public function test_draft_program_detail_is_not_found(): void
    {
        $this->makeProgram('secret-draft', 'draft');

        $this->get('/programs/secret-draft')->assertNotFound();
    }

    public function test_published_program_detail_shows_requirements_and_costs(): void
    {
        $program = $this->makeProgram('open-program', 'published');
        $rule = $program->eligibilityRules()->create([
            'condition_type' => 'education_level_min',
            'logic_group' => 'default',
        ]);
        $rule->educationLevels()->attach(
            EducationLevel::query()->create([
                'code' => 'TEST_LEVEL',
                'name' => 'Test level',
                'category' => 'academic',
                'rank' => 55,
            ])->id,
        );

        $this->get('/programs/open-program')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('programs/[id]')
                    ->where('program.requirements.0', fn ($value) => str_contains((string) $value, 'or higher')),
            );
    }

    public function test_program_search_filters_by_interest(): void
    {
        Interest::query()->firstOrCreate(['code' => 'TECHNOLOGY'], ['name' => 'Technology']);

        $tech = $this->makeProgram('tech-program', 'published');
        $tech->interests()->attach(Interest::query()->where('code', 'TECHNOLOGY')->value('id'));
        $this->makeProgram('other-program', 'published');

        $this->get('/programs?interest=TECHNOLOGY')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->where('programs.data.0.slug', 'tech-program')
                    ->missing('programs.data.1'),
            );
    }

    public function test_institutions_directory_lists_only_those_with_published_programs(): void
    {
        $this->get('/institutions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('institutions', 0));

        $this->makeProgram('now-published', 'published');

        $this->get('/institutions')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->has('institutions', 1)
                    ->where('institutions.0.name', 'Public test school'),
            );
    }

    public function test_admin_can_publish_a_draft_program_and_it_becomes_public(): void
    {
        $admin = User::factory()->admin()->create();
        $program = $this->makeProgram('soon-public', 'draft');

        $this->actingAs($admin)
            ->patch("/admin/programs/{$program->id}/status", ['status' => 'published'])
            ->assertRedirect();

        $this->assertSame(ProgramStatus::Published, $program->refresh()->status);
        $this->get('/programs')->assertSee('Soon-public');
    }

    public function test_regular_users_cannot_publish_programs(): void
    {
        $user = User::factory()->create();
        $program = $this->makeProgram('stays-draft', 'draft');

        $this->actingAs($user)
            ->patch("/admin/programs/{$program->id}/status", ['status' => 'published'])
            ->assertForbidden();

        $this->assertSame(ProgramStatus::Draft, $program->refresh()->status);
    }
}
