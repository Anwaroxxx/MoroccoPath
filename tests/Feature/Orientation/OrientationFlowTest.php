<?php

namespace Tests\Feature\Orientation;

use App\Models\Campus;
use App\Models\Cost;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Interest;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrientationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_orientation(): void
    {
        $this->get('/orientation')->assertRedirect('/login');
        $this->get('/results')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_questionnaire_with_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/orientation')
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('orientation')
                    ->has('options.education_levels')
                    ->has('options.interests')
                    ->where('profile', null),
            );
    }

    public function test_results_redirect_to_orientation_when_no_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/results')->assertRedirect('/orientation');
    }

    public function test_saving_profile_persists_and_shows_ranked_results(): void
    {
        $user = User::factory()->create();
        $level = EducationLevel::query()->create([
            'code' => 'LVL_TEST', 'name' => 'Test level', 'category' => 'academic', 'rank' => 55,
        ]);
        $interest = Interest::query()->firstOrCreate(['code' => 'TECHNOLOGY'], ['name' => 'Technology']);

        $institution = Institution::create([
            'name' => 'Flow test school', 'canonical_name' => 'Flow test school',
            'slug' => 'flow-test-school', 'type' => 'coding_school', 'status' => 'active',
        ]);
        Campus::create([
            'institution_id' => $institution->id, 'name' => 'HQ',
            'city' => 'Casablanca', 'region' => 'Casablanca-Settat',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id, 'name' => 'Web Dev Flow',
            'slug' => 'web-dev-flow', 'study_mode' => 'full_time', 'status' => 'published',
        ]);
        $rule = $program->eligibilityRules()->create(['condition_type' => 'education_level_min']);
        $rule->educationLevels()->attach($level->id);
        $program->interests()->attach($interest->id);
        Cost::create(['program_id' => $program->id, 'cost_type' => 'tuition_annual', 'is_free' => true]);

        $this->actingAs($user)
            ->patch('/orientation', [
                'education_level_id' => $level->id,
                'city' => 'Casablanca',
                'budget_max' => 0,
                'interest_codes' => ['TECHNOLOGY'],
                'willing_to_relocate' => false,
            ])
            ->assertRedirect('/results');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'education_level_id' => $level->id,
            'city' => 'Casablanca',
        ]);

        $response = $this->actingAs($user)->get('/results');

        $response->assertOk()->assertInertia(
            fn ($page) => $page
                ->component('results')
                ->where('recommendations.0.program.slug', 'web-dev-flow')
                ->where('recommendations.0.eligible', true)
                ->where('recommendations.0.reasons.0', 'Your education fits the entry requirements.'),
        );
    }

    public function test_invalid_payload_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/orientation')
            ->patch('/orientation', ['age' => 250, 'bac_grade' => 55])
            ->assertSessionHasErrors(['age', 'bac_grade']);
    }
}
