<?php

namespace Tests\Unit\Education;

use App\Enums\ProgramVersionStatus;
use App\Enums\VerificationStatus;
use App\Models\BacBranch;
use App\Models\EducationLevel;
use App\Models\EligibilityRule;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramVersion;
use App\Models\Qualification;
use App\Models\Source;
use App\Models\SourceReference;
use App\Services\Education\EligibilityEngine;
use App\Services\Education\StudentProfile;
use Database\Seeders\BacBranchSeeder;
use Database\Seeders\EducationLevelSeeder;
use Database\Seeders\QualificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EligibilityEngineTest extends TestCase
{
    use RefreshDatabase;

    private EligibilityEngine $engine;

    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        (new EducationLevelSeeder)->run();
        (new QualificationSeeder)->run();
        (new BacBranchSeeder)->run();

        $this->engine = new EligibilityEngine;
        $this->program = $this->makeProgram();
    }

    public static function levelCodesProvider(): array
    {
        return [
            'niveau bac' => ['NIVEAU_BAC'],
            'bac' => ['BAC'],
            'bac+3' => ['BAC_PLUS_3'],
        ];
    }

    public function test_no_rules_is_eligible_with_warning(): void
    {
        $result = $this->engine->evaluate($this->program, new StudentProfile);

        $this->assertTrue($result->eligible);
        $this->assertNull($result->score);
        $this->assertContains('No verified eligibility criteria are recorded for this program yet.', $result->warnings);
    }

    public function test_requires_bac_rejects_no_bac(): void
    {
        $this->levelMin('BAC');

        $result = $this->engine->evaluate($this->program, new StudentProfile(educationLevelCode: 'NO_BAC'));

        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('or higher', implode(' ', $result->failedRequirements));
    }

    public function test_niveau_bac_minimum_accepts_and_rejects_correctly(): void
    {
        $this->levelMin('NIVEAU_BAC');

        $accepted = $this->engine->evaluate($this->program, new StudentProfile(educationLevelCode: 'NIVEAU_BAC'));
        $rejected = $this->engine->evaluate($this->program, new StudentProfile(educationLevelCode: 'NO_BAC'));

        $this->assertTrue($accepted->eligible);
        $this->assertSame(100, $accepted->score);
        $this->assertFalse($rejected->eligible);
    }

    #[DataProvider('levelCodesProvider')]
    public function test_min_condition_is_inclusive(string $studentLevel): void
    {
        $this->levelMin('NIVEAU_BAC');

        $result = $this->engine->evaluate($this->program, new StudentProfile(educationLevelCode: $studentLevel));

        $this->assertTrue($result->eligible, "{$studentLevel} should satisfy a NIVEAU_BAC minimum.");
    }

    public function test_correct_bac_branch_passes(): void
    {
        $this->branchAnyOf(['SCIENCES_PHYSIQUES']);

        $result = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'BAC', bacBranchCodes: ['SCIENCES_PHYSIQUES']),
        );

        $this->assertTrue($result->eligible);
        $this->assertNotEmpty($result->matchedRequirements);
    }

    public function test_wrong_bac_branch_fails_with_explanation(): void
    {
        $this->branchAnyOf(['SCIENCES_PHYSIQUES']);

        $result = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'BAC', bacBranchCodes: ['LETTRES']),
        );

        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('Sciences Physiques', implode(' ', $result->failedRequirements));
        $this->assertStringContainsString('does not currently meet', implode(' ', $result->reasons));
    }

    public function test_higher_level_satisfies_lower_minimum(): void
    {
        $this->levelMin('BAC_PLUS_2');

        $result = $this->engine->evaluate($this->program, new StudentProfile(educationLevelCode: 'BAC_PLUS_3'));

        $this->assertTrue($result->eligible);
    }

    public function test_exact_level_requirement_rejects_lower_level(): void
    {
        $this->levelAnyOf(['BAC_PLUS_2']);

        $result = $this->engine->evaluate($this->program, new StudentProfile(educationLevelCode: 'BAC'));

        $this->assertFalse($result->eligible);
    }

    public function test_professional_qualification_satisfies_qualification_rule(): void
    {
        $this->qualificationAnyOf(['TECHNICIEN', 'QUALIFICATION']);

        $result = $this->engine->evaluate($this->program, new StudentProfile(qualificationCode: 'TECHNICIEN'));

        $this->assertTrue($result->eligible);
    }

    public function test_professional_qualification_equivalence_satisfies_level_minimum(): void
    {
        // DUT is seeded as equivalent to BAC+2.
        $this->levelMin('BAC_PLUS_2');

        $result = $this->engine->evaluate($this->program, new StudentProfile(qualificationCode: 'DUT'));

        $this->assertTrue($result->eligible);
    }

    public function test_age_limit_blocks_older_candidates(): void
    {
        $this->levelMin('NIVEAU_BAC');
        $this->maxAge(30);

        $result = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'NIVEAU_BAC', age: 34),
        );

        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('Maximum age: 30', implode(' ', $result->failedRequirements));
    }

    public function test_age_limit_allows_younger_candidates(): void
    {
        $this->levelMin('NIVEAU_BAC');
        $this->maxAge(30);

        $result = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'NIVEAU_BAC', age: 19),
        );

        $this->assertTrue($result->eligible);
        $this->assertSame(100, $result->score);
    }

    public function test_missing_age_fails_closed_and_warns(): void
    {
        $this->maxAge(25);

        $result = $this->engine->evaluate($this->program, new StudentProfile);

        $this->assertFalse($result->eligible);
        $this->assertContains(
            'Your age was not provided, so this requirement could not be checked.',
            $result->warnings,
        );
    }

    public function test_min_grade_blocks_weak_and_accepts_strong_grades(): void
    {
        $this->minGrade(12);

        $weak = $this->engine->evaluate(
            $this->program,
            new StudentProfile(bacGrade: 9.5),
        );
        $strong = $this->engine->evaluate(
            $this->program,
            new StudentProfile(bacGrade: 14.25),
        );

        $this->assertFalse($weak->eligible);
        $this->assertStringContainsString('12/20', implode(' ', $weak->failedRequirements));
        $this->assertTrue($strong->eligible);
    }

    public function test_and_conditions_require_every_condition(): void
    {
        // NIVEAU_BAC AND age <= 30 in the same logic group.
        $this->levelMin('NIVEAU_BAC');
        $this->maxAge(30);

        $bothPass = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'BAC', age: 22),
        );
        $oneFails = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'BAC', age: 45),
        );

        $this->assertTrue($bothPass->eligible);
        $this->assertSame(100, $bothPass->score);
        $this->assertFalse($oneFails->eligible);
        $this->assertCount(1, $oneFails->failedRequirements);
        $this->assertSame(50, $oneFails->score);
    }

    public function test_or_conditions_pass_when_any_group_matches(): void
    {
        // Group A: TECHNICIEN. Group B: Bac + Sciences Physiques.
        $this->qualificationAnyOf(['TECHNICIEN'], group: 'via_technicien');
        $this->levelMin('BAC', group: 'via_bac');
        $this->branchAnyOf(['SCIENCES_PHYSIQUES'], group: 'via_bac');

        $technicienOnly = $this->engine->evaluate(
            $this->program,
            new StudentProfile(qualificationCode: 'TECHNICIEN'),
        );
        $bacWithBranch = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'BAC', bacBranchCodes: ['SCIENCES_PHYSIQUES']),
        );
        $neither = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'NO_BAC'),
        );

        $this->assertTrue($technicienOnly->eligible);
        $this->assertTrue($bacWithBranch->eligible);
        $this->assertFalse($neither->eligible);
    }

    public function test_negate_inverts_a_single_condition(): void
    {
        $this->branchAnyOf(['LETTRES'], negate: true, group: 'default');
        $this->levelMin('BAC', group: 'default');

        $lettreStudent = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'BAC', bacBranchCodes: ['LETTRES']),
        );
        $scienceStudent = $this->engine->evaluate(
            $this->program,
            new StudentProfile(educationLevelCode: 'BAC', bacBranchCodes: ['SVT']),
        );

        $this->assertFalse($lettreStudent->eligible);
        $this->assertTrue($scienceStudent->eligible);
    }

    public function test_expired_program_version_is_not_eligible(): void
    {
        ProgramVersion::create([
            'program_id' => $this->program->id,
            'academic_year' => '2024/2025',
            'status' => ProgramVersionStatus::Expired,
        ]);

        $result = $this->engine->evaluate($this->program, new StudentProfile);

        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('closed', implode(' ', $result->reasons));
    }

    public function test_conflicting_source_blocks_eligibility(): void
    {
        $source = Source::create([
            'name' => 'Test official source',
            'slug' => 'test-official',
            'type' => 'official_government',
            'trust_level' => 1,
        ]);
        SourceReference::create([
            'source_id' => $source->id,
            'referencable_type' => $this->program::class,
            'referencable_id' => $this->program->id,
            'verification_status' => VerificationStatus::Conflicting->value,
        ]);

        $result = $this->engine->evaluate($this->program, new StudentProfile);

        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('conflicts between official sources', implode(' ', $result->reasons));
    }

    public function test_result_exposes_sources_with_provenance(): void
    {
        $source = Source::create([
            'name' => 'OFPPT (test fixture)',
            'slug' => 'ofppt-fixture',
            'type' => 'official_institution',
            'trust_level' => 2,
        ]);
        SourceReference::create([
            'source_id' => $source->id,
            'referencable_type' => $this->program::class,
            'referencable_id' => $this->program->id,
            'academic_year' => '2026/2027',
            'verification_status' => VerificationStatus::Verified->value,
            'last_verified_at' => '2026-08-23',
        ]);

        $result = $this->engine->evaluate($this->program, new StudentProfile);
        $shape = $result->toArray();

        $this->assertCount(1, $result->sources);
        $this->assertSame('OFPPT (test fixture)', $result->sources[0]['source']);
        $this->assertSame('verified', $result->sources[0]['verification_status']);
        $this->assertSame('2026/2027', $result->sources[0]['academic_year']);
        $this->assertSame('2026-08-23', $result->sources[0]['last_verified_at']);
        $this->assertArrayHasKey('eligible', $shape);
        $this->assertArrayHasKey('score', $shape);
        $this->assertArrayHasKey('failed_requirements', $shape);
    }

    public function test_misconfigured_empty_rule_fails_closed_with_warning(): void
    {
        EligibilityRule::create([
            'program_id' => $this->program->id,
            'condition_type' => 'bac_branch_any_of',
            'logic_group' => 'default',
        ]);

        $result = $this->engine->evaluate($this->program, new StudentProfile);

        $this->assertFalse($result->eligible);
        $this->assertNotEmpty(array_filter(
            $result->warnings,
            fn (string $warning): bool => str_contains($warning, 'misconfigured'),
        ));
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function makeProgram(): Program
    {
        $institution = Institution::create([
            'name' => 'Engine test institution',
            'canonical_name' => 'Engine test institution',
            'slug' => 'engine-test-institution',
            'type' => 'coding_school',
            'status' => 'active',
        ]);

        return Program::create([
            'institution_id' => $institution->id,
            'name' => 'Engine test program',
            'slug' => 'engine-test-program',
            'study_mode' => 'full_time',
            'status' => 'published',
        ]);
    }

    private function levelMin(string $levelCode, string $group = 'default'): EligibilityRule
    {
        $rule = EligibilityRule::create([
            'program_id' => $this->program->id,
            'condition_type' => 'education_level_min',
            'logic_group' => $group,
        ]);
        $levelId = EducationLevel::query()->where('code', $levelCode)->value('id');
        $rule->educationLevels()->attach($levelId);

        return $rule;
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function levelAnyOf(array $codes, string $group = 'default'): EligibilityRule
    {
        $rule = EligibilityRule::create([
            'program_id' => $this->program->id,
            'condition_type' => 'education_level_any_of',
            'logic_group' => $group,
        ]);
        $ids = EducationLevel::query()->whereIn('code', $codes)->pluck('id');
        $rule->educationLevels()->attach($ids);

        return $rule;
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function qualificationAnyOf(array $codes, string $group = 'default'): EligibilityRule
    {
        $rule = EligibilityRule::create([
            'program_id' => $this->program->id,
            'condition_type' => 'qualification_any_of',
            'logic_group' => $group,
        ]);
        $ids = Qualification::query()->whereIn('code', $codes)->pluck('id');
        $rule->qualifications()->attach($ids);

        return $rule;
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function branchAnyOf(array $codes, string $group = 'default', bool $negate = false): EligibilityRule
    {
        $rule = EligibilityRule::create([
            'program_id' => $this->program->id,
            'condition_type' => 'bac_branch_any_of',
            'logic_group' => $group,
            'negate' => $negate,
        ]);
        $ids = BacBranch::query()->whereIn('code', $codes)->pluck('id');
        $rule->bacBranches()->attach($ids);

        return $rule;
    }

    private function maxAge(int $maxAge, string $group = 'default'): EligibilityRule
    {
        return EligibilityRule::create([
            'program_id' => $this->program->id,
            'condition_type' => 'max_age',
            'logic_group' => $group,
            'parameters' => ['max_age' => $maxAge],
        ]);
    }

    private function minGrade(float $minGrade, string $group = 'default'): EligibilityRule
    {
        return EligibilityRule::create([
            'program_id' => $this->program->id,
            'condition_type' => 'min_grade',
            'logic_group' => $group,
            'parameters' => ['min_grade' => $minGrade],
        ]);
    }
}
