<?php

namespace Tests\Feature\Api;

use App\Models\Campus;
use App\Models\Cost;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Interest;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Api test school',
            'canonical_name' => 'Api test school',
            'slug' => 'api-test-school',
            'type' => 'coding_school',
            'status' => 'active',
        ]);

        Campus::create([
            'institution_id' => $this->institution->id,
            'name' => 'HQ', 'city' => 'Casablanca', 'region' => 'Casablanca-Settat',
        ]);
    }

    private function makeProgram(string $slug, string $status = 'published'): Program
    {
        return Program::create([
            'institution_id' => $this->institution->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
            'study_mode' => 'full_time',
            'status' => $status,
        ]);
    }

    public function test_programs_index_returns_published_only_with_pagination(): void
    {
        $this->makeProgram('public-one');
        $this->makeProgram('hidden-draft', 'draft');

        $this->getJson('/api/v1/programs')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'name', 'study_mode', 'institution', 'city']],
                'meta' => ['current_page', 'last_page', 'total'],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'public-one');
    }

    public function test_program_detail_404s_for_drafts_and_serves_published(): void
    {
        $this->makeProgram('secret', 'draft');
        $published = $this->makeProgram('open');

        Cost::create(['program_id' => $published->id, 'cost_type' => 'tuition_annual', 'is_free' => true]);

        $this->getJson('/api/v1/programs/secret')->assertNotFound();

        $this->getJson('/api/v1/programs/open')
            ->assertOk()
            ->assertJsonPath('data.slug', 'open')
            ->assertJsonPath('data.is_free', true);
    }

    public function test_recommendations_require_authentication(): void
    {
        $this->postJson('/api/v1/recommendations')->assertUnauthorized();
        $this->getJson('/api/v1/recommendations')->assertUnauthorized();
    }

    public function test_profile_update_and_recommendations_flow_over_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $level = EducationLevel::query()->create([
            'code' => 'API_LVL', 'name' => 'Api level', 'category' => 'academic', 'rank' => 55,
        ]);
        $interest = Interest::query()->firstOrCreate(['code' => 'TECHNOLOGY'], ['name' => 'Technology']);

        $program = $this->makeProgram('api-ranked');
        $rule = $program->eligibilityRules()->create(['condition_type' => 'education_level_min']);
        $rule->educationLevels()->attach($level->id);
        $program->interests()->attach($interest->id);
        Cost::create(['program_id' => $program->id, 'cost_type' => 'tuition_annual', 'is_free' => true]);

        // Save the profile.
        $this->withToken($token)
            ->patchJson('/api/v1/me/profile', [
                'education_level_id' => $level->id,
                'city' => 'Casablanca',
                'budget_max' => 0,
                'interest_codes' => ['TECHNOLOGY'],
            ])
            ->assertOk()
            ->assertJsonPath('data.education_level_code', 'API_LVL');

        // Invalid taxonomy code is rejected.
        $this->withToken($token)
            ->patchJson('/api/v1/me/profile', ['interest_codes' => ['NOT_REAL']])
            ->assertUnprocessable();

        // Recommendations are ranked and explained.
        $this->withToken($token)
            ->postJson('/api/v1/recommendations')
            ->assertOk()
            ->assertJsonPath('data.0.program.slug', 'api-ranked')
            ->assertJsonPath('data.0.eligible', true)
            ->assertJsonStructure([
                'data' => [['match_score', 'reasons', 'missing_requirements', 'alternatives', 'eligibility']],
            ]);
    }

    public function test_recommendations_without_saved_profile_fail_with_guidance(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/recommendations')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No profile yet. Save one via PATCH /v1/me/profile first.');
    }
}

class AuthTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_issue_a_scoped_token(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'secret-password',
            'device_name' => 'android-phone',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'android-phone',
        ]);
    }

    public function test_bad_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'phone',
        ])->assertUnprocessable();
    }

    public function test_token_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phone');

        $this->withToken($token->plainTextToken)
            ->deleteJson('/api/v1/auth/token')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }
}
