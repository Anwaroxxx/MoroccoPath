<?php

namespace Tests\Unit\Ingestion;

use App\Enums\InstitutionStatus;
use App\Enums\ProgramVersionStatus;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\InstitutionAlias;
use App\Models\Interest;
use App\Models\Program;
use App\Models\ProgramVersion;
use App\Models\Skill;
use App\Models\SourceReference;
use App\Services\Ingestion\DeduplicationService;
use App\Services\Ingestion\FileFetcher;
use App\Services\Ingestion\IngestionPipeline;
use App\Services\Ingestion\InstitutionNormalizationService;
use App\Services\Ingestion\JsonPayloadParser;
use App\Services\Ingestion\ProgramNormalizationService;
use App\Services\Ingestion\RecordValidator;
use App\Services\Ingestion\SourceDefinition;
use Database\Seeders\EducationLevelSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = __DIR__.'/../../fixtures/ingestion/sample-payload.json';

    protected function setUp(): void
    {
        parent::setUp();

        (new EducationLevelSeeder)->run();
        (new TaxonomySeeder)->run();

        Skill::query()->firstOrCreate(['code' => 'PROGRAMMING'], ['name' => 'Programming']);
    }

    private function pipeline(bool $dryRun = false): IngestionPipeline
    {
        return new IngestionPipeline(
            definition: SourceDefinition::load('ofppt'),
            fetcher: new FileFetcher(self::FIXTURE),
            parser: new JsonPayloadParser,
            validator: new RecordValidator,
            deduplication: new DeduplicationService(new InstitutionNormalizationService),
            programNormalizer: new ProgramNormalizationService,
            institutionNormalizer: new InstitutionNormalizationService,
            dryRun: $dryRun,
            yearOverride: null,
        );
    }

    public function test_merges_alias_variant_and_stores_records_with_provenance(): void
    {
        $report = $this->pipeline()->run();

        // "ISTA Casa" merged into the canonical institution; the invalid
        // empty-name record was rejected.
        $this->assertSame(1, Institution::query()->count());
        $this->assertSame(1, $report->institutionsCreated);
        $this->assertSame(1, $report->institutionsMerged);
        $this->assertCount(1, $report->rejected);

        $institution = Institution::query()->sole();
        $this->assertSame('Institut Spécialisé de Technologie Appliquée Casablanca', $institution->canonical_name);
        $this->assertTrue(InstitutionAlias::query()
            ->where('institution_id', $institution->id)
            ->where('alias', 'ISTA Casa')
            ->exists());
        $this->assertSame(InstitutionStatus::Unknown, $institution->status);

        // Campus stored.
        $this->assertSame(1, Campus::query()->count());

        // Program with version, taxonomy and provenance.
        $program = Program::query()->sole();
        $this->assertStringContainsString('developpement-digital', $program->slug);
        $this->assertSame(24, $program->duration_months);
        $this->assertTrue($program->interests->pluck('code')->contains('TECHNOLOGY'));
        $this->assertTrue($program->skills->pluck('code')->contains('PROGRAMMING'));

        $version = ProgramVersion::query()->where('program_id', $program->id)->sole();
        $this->assertSame('2026/2027', $version->academic_year);
        $this->assertSame(ProgramVersionStatus::Active, $version->status);

        $references = SourceReference::query()->get();
        $this->assertSame(2, $references->count()); // institution + program
        $references->each(fn (SourceReference $reference) => $this->assertSame('needs_review', $reference->verification_status->value));
    }

    public function test_rerun_is_idempotent(): void
    {
        $this->pipeline()->run();
        $second = $this->pipeline()->run();

        // Both payload records resolve onto the same canonical entity.
        $this->assertSame(0, $second->institutionsCreated);
        $this->assertSame(2, $second->institutionsMerged);
        $this->assertSame(0, $second->programsCreated);
        $this->assertSame(1, $second->programsUpdated);
        $this->assertSame(1, Program::query()->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $report = $this->pipeline(dryRun: true)->run();

        $this->assertSame(1, $report->institutionsCreated);
        $this->assertSame(0, Institution::query()->count());
        $this->assertSame(0, Program::query()->count());
        $this->assertSame(0, SourceReference::query()->count());
    }

    public function test_unknown_taxonomy_codes_are_skipped_without_failing(): void
    {
        Interest::query()->where('code', 'TECHNOLOGY')->delete();

        $report = $this->pipeline()->run();

        $this->assertSame(1, Program::query()->count());
        $this->assertSame(0, DB::table('program_interests')->count());
        $this->assertCount(0, array_filter(
            $report->rejected,
            fn (array $rejection): bool => str_contains((string) $rejection['key'], 'Développement'),
        ));
    }
}
