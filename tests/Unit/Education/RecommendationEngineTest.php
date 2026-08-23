<?php

namespace Tests\Unit\Education;

use App\Models\Campus;
use App\Models\Career;
use App\Models\Cost;
use App\Models\EducationLevel;
use App\Models\EligibilityRule;
use App\Models\Institution;
use App\Models\Interest;
use App\Models\Program;
use App\Models\Skill;
use App\Services\Education\EligibilityEngine;
use App\Services\Education\RecommendationEngine;
use App\Services\Education\StudentProfile;
use Database\Seeders\EducationLevelSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationEngine $engine;

    private int $institutionId;

    protected function setUp(): void
    {
        parent::setUp();

        (new EducationLevelSeeder)->run();
        (new TaxonomySeeder)->run();

        $this->engine = new RecommendationEngine(new EligibilityEngine);
        $this->institutionId = Institution::create([
            'name' => 'Rec test institution',
            'canonical_name' => 'Rec test institution',
            'slug' => 'rec-test-institution',
            'type' => 'coding_school',
            'status' => 'active',
        ])->id;
    }

    public function test_perfect_profile_scores_100(): void
    {
        $program = $this->makeProgram('perfect', campusCity: 'Casablanca');
        $this->levelMin($program, 'NIVEAU_BAC');
        $this->tagInterest($program, 'TECHNOLOGY');
        $this->tagSkill($program, 'PROGRAMMING');
        $this->tagCareer($program, 'JUNIOR_DEVELOPER');
        Cost::create(['program_id' => $program->id, 'cost_type' => 'tuition_annual', 'is_free' => true]);

        $result = $this->engine->recommend(new StudentProfile(
            educationLevelCode: 'NIVEAU_BAC',
            city: 'Casablanca',
            budgetMax: 0,
            interestCodes: ['TECHNOLOGY'],
            skillCodes: ['PROGRAMMING'],
            careerGoalCodes: ['JUNIOR_DEVELOPER'],
        ), $this->programs($program));

        $this->assertSame(100, $result->first()->matchScore);
        $this->assertTrue($result->first()->eligible);
        $this->assertContains('Free of charge.', $result->first()->reasons);
    }

    public function test_minimal_profile_renormalizes_across_known_dimensions(): void
    {
        $program = $this->makeProgram('bare');

        // Only education is evaluable and it passes -> 30/30 = 100.
        $stated = $this->engine->recommend(
            new StudentProfile(educationLevelCode: 'NIVEAU_BAC'),
            $this->programs($program),
        );
        $this->assertSame(100, $stated->first()->matchScore);

        // Nothing at all is known -> null rather than an invented score.
        $empty = $this->engine->recommend(new StudentProfile, $this->programs($program));
        $this->assertNull($empty->first()->matchScore);
    }

    public function test_partial_interest_overlap_produces_exact_weighted_score(): void
    {
        $program = $this->makeProgram('two-interests');
        $this->tagInterest($program, 'TECHNOLOGY');
        $this->tagInterest($program, 'DESIGN');

        // 1 of the program's 2 interest tags matched.
        $result = $this->engine->recommend(new StudentProfile(
            educationLevelCode: 'BAC',
            interestCodes: ['TECHNOLOGY'],
        ), $this->programs($program));

        // (30 * 1.0 + 25 * 0.5) / 55 = 77.27 -> 77
        $this->assertSame(77, $result->first()->matchScore);
    }

    public function test_location_tiers_city_region_relocate_online(): void
    {
        $onSite = $this->makeProgram('onsite', campusCity: 'Casablanca', campusRegion: 'Casablanca-Settat');

        // Same region, different city -> partial credit.
        $regionOnly = $this->engine->recommend(new StudentProfile(
            educationLevelCode: 'BAC',
            city: 'Mohammedia',
            region: 'Casablanca-Settat',
        ), $this->programs($onSite));
        // (30 + 10 * 0.7) / 40 = 92.5 -> 93
        $this->assertSame(93, $regionOnly->first()->matchScore);

        $relocate = $this->engine->recommend(new StudentProfile(
            educationLevelCode: 'BAC',
            willingToRelocate: true,
        ), $this->programs($onSite));
        $this->assertSame(100, $relocate->first()->matchScore);

        $online = $this->makeProgram('online-course', studyMode: 'online', campusCity: 'Casablanca');
        $farAway = $this->engine->recommend(new StudentProfile(
            educationLevelCode: 'BAC',
            city: 'Rabat',
        ), $this->programs($online));
        // (30 + 10 * 0.8) / 40 = 95
        $this->assertSame(95, $farAway->first()->matchScore);
    }

    public function test_budget_tiers(): void
    {
        $program = $this->makeProgram('paid');
        Cost::create([
            'program_id' => $program->id,
            'cost_type' => 'tuition_annual',
            'amount_min' => 500,
            'amount_max' => 800,
        ]);

        $tight = $this->engine->recommend(new StudentProfile(budgetMax: 600), $this->programs($program));
        // Budget is the only evaluable dimension: (10 * 0.5) / 10 = 50
        $this->assertSame(50, $tight->first()->matchScore);
        $this->assertContains('Partially within your stated budget.', $tight->first()->reasons);

        $comfortable = $this->engine->recommend(new StudentProfile(budgetMax: 1000), $this->programs($program));
        $this->assertSame(100, $comfortable->first()->matchScore);

        $tooLow = $this->engine->recommend(new StudentProfile(educationLevelCode: 'BAC', budgetMax: 200), $this->programs($program));
        // (30 + 0) / 40 = 75
        $this->assertSame(75, $tooLow->first()->matchScore);
        $this->assertContains('Costs exceed your stated budget.', $tooLow->first()->missingRequirements);
    }

    public function test_ineligible_program_stays_visible_with_alternatives(): void
    {
        $target = $this->makeProgram('target');
        $this->levelMin($target, 'BAC_PLUS_3');

        $alternative = $this->makeProgram('alt-path');
        $target->alternativePaths()->create([
            'alternative_program_id' => $alternative->id,
            'note' => 'Open access alternative.',
        ]);

        $result = $this->engine->recommend(
            new StudentProfile(educationLevelCode: 'BAC'),
            $this->programs($target),
        );

        $first = $result->first();
        $this->assertFalse($first->eligible);
        $this->assertSame(0, $first->matchScore);
        $this->assertStringContainsString(
            'or higher',
            implode(' ', $first->missingRequirements),
        );
        $this->assertSame([['slug' => 'rec-alt-path', 'name' => 'Rec alt-path']], $first->alternatives);
    }

    public function test_results_are_ranked_by_score_descending(): void
    {
        $high = $this->makeProgram('aaa-high');
        $this->tagInterest($high, 'TECHNOLOGY');

        $low = $this->makeProgram('zzz-low');
        $this->levelMin($low, 'BAC_PLUS_2');

        $result = $this->engine->recommend(new StudentProfile(
            educationLevelCode: 'BAC',
            interestCodes: ['TECHNOLOGY'],
        ), $this->programs($low, $high));

        $this->assertSame('rec-aaa-high', $result->first()->program->slug);
        $this->assertGreaterThan($result->last()->matchScore ?? 0, $result->first()->matchScore ?? 0);
    }

    /**
     * @param  array<int, Program>  $programs
     * @return Collection<int, Program>
     */
    private function programs(...$programs): Collection
    {
        return new Collection($programs);
    }

    private function makeProgram(
        string $slug,
        ?string $campusCity = null,
        ?string $campusRegion = null,
        string $studyMode = 'full_time',
    ): Program {
        $program = Program::create([
            'institution_id' => $this->institutionId,
            'name' => 'Rec '.$slug,
            'slug' => 'rec-'.$slug,
            'study_mode' => $studyMode,
            'status' => 'published',
        ]);

        if ($campusCity !== null) {
            Campus::create([
                'institution_id' => $this->institutionId,
                'name' => $slug.' campus',
                'city' => $campusCity,
                'region' => $campusRegion ?? $campusCity,
            ]);
        }

        return $program;
    }

    private function levelMin(Program $program, string $levelCode): EligibilityRule
    {
        $rule = EligibilityRule::create([
            'program_id' => $program->id,
            'condition_type' => 'education_level_min',
            'logic_group' => 'default',
        ]);
        $rule->educationLevels()->attach(
            EducationLevel::query()->where('code', $levelCode)->value('id'),
        );

        return $rule;
    }

    private function tagInterest(Program $program, string $code): void
    {
        $program->interests()->attach(Interest::query()->where('code', $code)->value('id'));
    }

    private function tagSkill(Program $program, string $code): void
    {
        $skill = Skill::query()->firstOrCreate(['code' => $code], ['name' => ucfirst(strtolower($code))]);
        $program->skills()->attach($skill->id);
    }

    private function tagCareer(Program $program, string $code): void
    {
        $career = Career::query()->firstOrCreate(['code' => $code], ['name' => ucwords(strtolower(str_replace('_', ' ', $code)))]);
        $program->careers()->attach($career->id);
    }
}
