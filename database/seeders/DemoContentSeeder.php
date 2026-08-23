<?php

namespace Database\Seeders;

use App\Enums\InstitutionStatus;
use App\Enums\InstitutionType;
use App\Enums\ProgramStatus;
use App\Enums\ProgramVersionStatus;
use App\Enums\SourceType;
use App\Enums\StudyMode;
use App\Enums\VerificationStatus;
use App\Models\Campus;
use App\Models\Career;
use App\Models\CareerPath;
use App\Models\CareerPathStep;
use App\Models\EducationLevel;
use App\Models\Field;
use App\Models\Institution;
use App\Models\Interest;
use App\Models\Program;
use App\Models\ProgramVersion;
use App\Models\Skill;
use App\Models\Source;
use App\Models\SourceReference;
use Illuminate\Database\Seeder;

/**
 * DEMO CONTENT ONLY (spec §29).
 *
 * Everything seeded here is clearly labeled "[DEMO]" and demonstrates the
 * shape of the data model. It must never be presented as real Moroccan
 * information. Real records arrive through the verified ingestion pipeline.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $demoSource = Source::updateOrCreate(
            ['slug' => 'demo-source'],
            [
                'name' => '[DEMO] Unofficial demonstration source',
                'type' => SourceType::Other->value,
                'trust_level' => SourceType::Other->trustLevel(),
                'description' => 'Demonstration source used to illustrate provenance tracking. Not a real authority.',
            ],
        );

        $institution = Institution::updateOrCreate(
            ['slug' => 'demo-coding-school'],
            [
                'name' => '[DEMO] École de Développement Digital',
                'canonical_name' => '[DEMO] École de Développement Digital',
                'type' => InstitutionType::CodingSchool->value,
                'status' => InstitutionStatus::Unknown->value,
                'description' => 'Demonstration institution illustrating institutions, campuses and programs.',
            ],
        );

        Campus::updateOrCreate(
            ['institution_id' => $institution->id, 'name' => '[DEMO] Casablanca campus'],
            [
                'city' => 'Casablanca',
                'region' => 'Casablanca-Settat',
                'is_primary' => true,
            ],
        );

        Field::query()->where('code', 'TECHNOLOGY')->firstOrFail();

        $program = Program::updateOrCreate(
            ['slug' => 'demo-developpement-digital'],
            [
                'institution_id' => $institution->id,
                'specialty_id' => null,
                'name' => '[DEMO] Développement Digital',
                'description' => 'Demonstration program: free coding training open to Bac-level students.',
                'education_level_id' => EducationLevel::query()->where('code', 'TECHNICIEN_SPECIALISE')->value('id')
                    ?? EducationLevel::query()->where('code', 'BAC_PLUS_1')->value('id'),
                'duration_months' => 24,
                'duration_label' => '2 ans',
                'study_mode' => StudyMode::FullTime->value,
                'language' => 'fr',
                'status' => ProgramStatus::Published->value,
            ],
        );

        ProgramVersion::updateOrCreate(
            ['program_id' => $program->id, 'academic_year' => '2026/2027'],
            [
                'status' => ProgramVersionStatus::Active->value,
                'duration_months' => 24,
                'admission_information' => '[DEMO] Selection after application review.',
            ],
        );

        // Provenance example: unofficial demo source -> needs official verification.
        SourceReference::updateOrCreate(
            [
                'source_id' => $demoSource->id,
                'referencable_type' => $program::class,
                'referencable_id' => $program->id,
                'academic_year' => '2026/2027',
            ],
            [
                'source_url' => 'https://example.org/demo-program',
                'source_title' => '[DEMO] Program fact sheet',
                'verification_status' => VerificationStatus::NeedsReview->value,
                'last_verified_at' => '2026-08-23',
            ],
        );

        // Eligibility: NIVEAU_BAC minimum AND age <= 30, plus entrance-exam info.
        $levelMin = $program->eligibilityRules()->updateOrCreate(
            [
                'condition_type' => 'education_level_min',
                'logic_group' => 'default',
            ],
            [
                'name' => 'Minimum level: Niveau Bac',
                'negate' => false,
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
        $niveauBacId = EducationLevel::query()->where('code', 'NIVEAU_BAC')->value('id');
        if ($niveauBacId !== null) {
            $levelMin->educationLevels()->syncWithoutDetaching([$niveauBacId]);
        }

        $program->eligibilityRules()->updateOrCreate(
            [
                'condition_type' => 'max_age',
                'logic_group' => 'default',
            ],
            [
                'name' => 'Maximum age 30',
                'parameters' => ['max_age' => 30],
                'sort_order' => 2,
                'is_active' => true,
            ],
        );

        $program->eligibilityRules()->updateOrCreate(
            [
                'condition_type' => 'entrance_exam',
                'logic_group' => 'default',
            ],
            [
                'name' => 'Entrance exam information',
                'sort_order' => 3,
                'is_active' => true,
            ],
        );

        // Link taxonomy so discovery can find this program.
        $technology = Interest::query()->where('code', 'TECHNOLOGY')->value('id');
        if ($technology !== null) {
            $program->interests()->syncWithoutDetaching([$technology]);
        }

        $programming = Skill::query()->updateOrCreate(['code' => 'PROGRAMMING'], ['name' => 'Programming']);
        $git = Skill::query()->updateOrCreate(['code' => 'GIT'], ['name' => 'Git']);
        $program->skills()->syncWithoutDetaching([$programming->id, $git->id]);

        $this->seedDemoCareerPath($program);
    }

    /**
     * [DEMO] career graph illustrating multiple entry points converging on
     * one target career (spec §20). Labeled DEMO — not a real Moroccan path.
     */
    private function seedDemoCareerPath(Program $program): void
    {
        $career = Career::query()->firstOrCreate(
            ['code' => 'SOFTWARE_DEVELOPER'],
            ['name' => '[DEMO] Software Developer'],
        );
        $field = Field::query()->where('code', 'TECHNOLOGY')->first();
        $niveauBac = EducationLevel::query()->where('code', 'NIVEAU_BAC')->first();
        $bac = EducationLevel::query()->where('code', 'BAC')->first();

        $path = CareerPath::query()->updateOrCreate(
            ['slug' => 'demo-software-developer'],
            [
                'name' => '[DEMO] Become a Software Developer',
                'description' => 'Demonstration path with two entry points. Replace with verified paths via ingestion.',
                'field_id' => $field?->id,
                'target_career_id' => $career->id,
            ],
        );

        // Entry point A: without the Bac.
        $a1 = CareerPathStep::query()->create([
            'career_path_id' => $path->id, 'position' => 1,
            'title' => '[DEMO] Start: Niveau Bac',
            'education_level_id' => $niveauBac?->id,
        ]);
        $a2 = CareerPathStep::query()->create([
            'career_path_id' => $path->id, 'parent_step_id' => $a1->id, 'position' => 2,
            'title' => '[DEMO] Free coding school (2 years)',
            'program_id' => $program->id,
        ]);
        CareerPathStep::query()->create([
            'career_path_id' => $path->id, 'parent_step_id' => $a2->id, 'position' => 3,
            'title' => '[DEMO] Junior developer',
        ]);

        // Entry point B: through university.
        $b1 = CareerPathStep::query()->create([
            'career_path_id' => $path->id, 'position' => 2,
            'title' => '[DEMO] Start: Bac',
            'education_level_id' => $bac?->id,
        ]);
        $b2 = CareerPathStep::query()->create([
            'career_path_id' => $path->id, 'parent_step_id' => $b1->id, 'position' => 3,
            'title' => '[DEMO] Licence en informatique',
        ]);
        CareerPathStep::query()->create([
            'career_path_id' => $path->id, 'parent_step_id' => $b2->id, 'position' => 4,
            'title' => '[DEMO] Junior developer',
        ]);
    }
}
