<?php

namespace App\Services\Ingestion;

use App\Enums\EligibilityConditionType;
use App\Enums\ProgramVersionStatus;
use App\Enums\SourceType;
use App\Enums\VerificationStatus;
use App\Models\BacBranch;
use App\Models\Campus;
use App\Models\Cost;
use App\Models\EducationLevel;
use App\Models\EligibilityRule;
use App\Models\Institution;
use App\Models\Interest;
use App\Models\Program;
use App\Models\ProgramVersion;
use App\Models\Qualification;
use App\Models\Skill;
use App\Models\Source;
use App\Models\SourceReference;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Orchestrates one ingestion run:
 *   fetch -> parse -> normalize -> validate -> deduplicate -> store
 *
 * Safety properties (spec §23–25, §29):
 *  - validation is fail-closed; rejects are reported, never half-inserted
 *  - the whole batch runs in a transaction
 *  - scraped values only fill EMPTY columns — verified data is never overwritten
 *  - every stored record gets a SourceReference stamped NEEDS_REVIEW
 *  - dry-run mode performs everything except writes
 */
final class IngestionPipeline
{
    public function __construct(
        private readonly SourceDefinition $definition,
        private readonly Fetcher $fetcher,
        private readonly JsonPayloadParser $parser,
        private readonly RecordValidator $validator,
        private readonly DeduplicationService $deduplication,
        private readonly ProgramNormalizationService $programNormalizer,
        private readonly InstitutionNormalizationService $institutionNormalizer,
        private readonly bool $dryRun = false,
        private readonly ?string $yearOverride = null,
    ) {}

    /**
     * @throws Throwable
     */
    public function run(): IngestionReport
    {
        if ($this->dryRun) {
            // Execute everything, then discard: proves the batch would work
            // without persisting a single row.
            DB::beginTransaction();

            try {
                return $this->process($this->fetcher->fetch($this->definition));
            } finally {
                DB::rollBack();
            }
        }

        return DB::transaction(fn (): IngestionReport => $this->process($this->fetcher->fetch($this->definition)));
    }

    private function process(string $body): IngestionReport
    {
        $report = new IngestionReport;
        $records = $this->parser->parse($body);
        $source = $this->ensureSource();

        foreach ($records as $record) {
            try {
                $this->ingestInstitution($record, $source, $report);
            } catch (ValidationException $exception) {
                $report->reject(
                    $record->name !== '' ? $record->name : '(unnamed record)',
                    Arr::flatten($exception->errors()),
                );
            }
        }

        return $report;
    }

    private function ingestInstitution(NormalizedInstitution $record, Source $source, IngestionReport $report): void
    {
        $names = $record->allNames();
        $canonical = $this->institutionNormalizer->canonicalName($names);

        // Gate 1: institution base fields are validated before anything is stored.
        $validated = $this->validator->validateInstitution([
            'name' => TextNormalizer::clean($record->name),
            'canonical_name' => $canonical !== '' ? $canonical : TextNormalizer::clean($record->name),
            'slug' => $this->institutionNormalizer->slug($canonical !== '' ? $canonical : $record->name),
            'type' => $record->type,
            'website' => $record->website,
            'phone' => $record->phone,
            'email' => $record->email,
        ]);

        [$institution, $created] = $this->deduplication->resolveInstitution($record);

        $created ? $report->institutionsCreated++ : $report->institutionsMerged++;

        // Scraped values may only fill empty columns (never overwrite verified data).
        $this->fillEmpty($institution, [
            'website' => $record->website,
            'phone' => $record->phone,
            'email' => $record->email,
            'description' => $record->description,
        ]);

        $this->attachProvenance($institution, $source, $this->resolveYear(null), $record->sourceUrl);

        foreach ($record->campuses as $campus) {
            $attributes = $campus->toArray();
            $validated = $this->validator->validateCampus(['institution_id' => $institution->id, ...$attributes]);

            Campus::query()->updateOrCreate(
                ['institution_id' => $institution->id, 'name' => $validated['name'], 'city' => $validated['city']],
                array_diff_key($validated, ['institution_id' => true, 'name' => true, 'city' => true]),
            );
            $report->campusesUpserted++;
        }

        foreach ($record->programs as $programRecord) {
            $this->ingestProgram($institution, $programRecord, $source, $report);
        }
    }

    private function ingestProgram(Institution $institution, NormalizedProgram $record, Source $source, IngestionReport $report): void
    {
        $year = $this->resolveYear($record->academicYear);

        $slug = $this->programNormalizer->slug($institution->slug, $record);

        $validated = $this->validator->validateProgram([
            'name' => $record->name,
            'slug' => $slug,
            'study_mode' => $record->studyMode,
            'duration_months' => $record->durationMonths,
            'duration_label' => $record->durationLabel,
            'language' => $record->language,
            'description' => $record->description,
            'admission_information' => $record->admissionInformation,
            'academic_year' => $year,
        ]);

        $existing = $record->externalIdentifier !== null
            ? Program::query()
                ->where('institution_id', $institution->id)
                ->where('external_identifier', $record->externalIdentifier)
                ->first()
            : null;

        $program = $existing ?? Program::query()->where('slug', $slug)->where('institution_id', $institution->id)->first();

        if ($program === null) {
            $program = Program::create([
                'institution_id' => $institution->id,
                'name' => $validated['name'],
                'slug' => $this->uniqueProgramSlug($slug),
                'external_identifier' => $record->externalIdentifier,
                'description' => $validated['description'],
                'duration_months' => $validated['duration_months'],
                'duration_label' => $validated['duration_label'],
                'study_mode' => $validated['study_mode'] ?? 'full_time',
                'language' => $validated['language'],
                'education_level_id' => $this->levelIdFor($record),
                'status' => 'draft',
            ]);
            $report->programsCreated++;
        } else {
            // academic_year belongs to ProgramVersion, not the program row.
            $this->fillEmpty($program, collect($validated)->except(['slug', 'academic_year'])->all());
            $report->programsUpdated++;
        }

        $this->attachTaxonomy($program, $record);

        ProgramVersion::query()->updateOrCreate(
            ['program_id' => $program->id, 'academic_year' => $year],
            [
                'status' => ProgramVersionStatus::Active,
                'duration_months' => $validated['duration_months'],
                'admission_information' => $validated['admission_information'] ?? null,
            ],
        );

        foreach ($record->costs as $cost) {
            Cost::query()->updateOrCreate(
                [
                    'program_id' => $program->id,
                    'academic_year' => $year,
                    'cost_type' => (string) Arr::get($cost, 'cost_type', 'other'),
                ],
                [
                    'amount_min' => Arr::get($cost, 'amount_min'),
                    'amount_max' => Arr::get($cost, 'amount_max'),
                    'currency' => Arr::get($cost, 'currency', 'MAD'),
                    'is_free' => (bool) Arr::get($cost, 'is_free', false),
                ],
            );
        }

        $this->storeEligibilityRules($program->id, $record);

        $this->attachProvenance($program, $source, $year, $record->sourceUrl);
    }

    /**
     * Replaces the rule set for this program with the payload's rules.
     * Rules referencing unknown taxonomy codes are rejected wholesale —
     * a half-stored rule set would silently change eligibility.
     */
    private function storeEligibilityRules(int $programId, NormalizedProgram $record): void
    {
        if ($record->rules === []) {
            return;
        }

        $prepared = [];

        foreach ($record->rules as $rule) {
            $type = EligibilityConditionType::tryFrom((string) Arr::get($rule, 'condition_type', ''));

            if ($type === null) {
                throw new RuntimeException(sprintf(
                    'Program [%s]: unknown eligibility condition type [%s].',
                    $record->name,
                    (string) Arr::get($rule, 'condition_type', ''),
                ));
            }

            $levelIds = [];
            foreach ((array) Arr::get($rule, 'levels', []) as $code) {
                $id = EducationLevel::query()->where('code', $code)->value('id');
                if ($id === null) {
                    throw new RuntimeException(sprintf('Program [%s]: unknown education level code [%s].', $record->name, (string) $code));
                }
                $levelIds[] = $id;
            }

            $branchIds = [];
            foreach ((array) Arr::get($rule, 'bac_branches', []) as $code) {
                $id = BacBranch::query()->where('code', $code)->value('id');
                if ($id === null) {
                    throw new RuntimeException(sprintf('Program [%s]: unknown Bac branch code [%s].', $record->name, (string) $code));
                }
                $branchIds[] = $id;
            }

            $qualificationIds = [];
            foreach ((array) Arr::get($rule, 'qualifications', []) as $code) {
                $id = Qualification::query()->where('code', $code)->value('id');
                if ($id === null) {
                    throw new RuntimeException(sprintf('Program [%s]: unknown qualification code [%s].', $record->name, (string) $code));
                }
                $qualificationIds[] = $id;
            }

            $prepared[] = [$type, $levelIds, $branchIds, $qualificationIds, $rule];
        }

        // All rules validated: safe to replace.
        EligibilityRule::query()->where('program_id', $programId)->delete();

        foreach ($prepared as [$type, $levelIds, $branchIds, $qualificationIds, $rule]) {
            /** @var EligibilityConditionType $type */
            /** @var array<int, int> $levelIds */
            /** @var array<int, int> $branchIds */
            /** @var array<int, int> $qualificationIds */
            /** @var array<string, mixed> $rule */
            $created = EligibilityRule::create([
                'program_id' => $programId,
                'name' => Arr::get($rule, 'name') !== null ? TextNormalizer::clean((string) $rule['name']) : null,
                'condition_type' => $type,
                'negate' => (bool) Arr::get($rule, 'negate', false),
                'logic_group' => (string) Arr::get($rule, 'logic_group', 'default'),
                'parameters' => Arr::get($rule, 'parameters'),
                'is_active' => true,
            ]);

            if ($levelIds !== []) {
                $created->educationLevels()->syncWithoutDetaching($levelIds);
            }
            if ($branchIds !== []) {
                $created->bacBranches()->syncWithoutDetaching($branchIds);
            }
            if ($qualificationIds !== []) {
                $created->qualifications()->syncWithoutDetaching($qualificationIds);
            }
        }
    }

    private function attachProvenance(Institution|Program $model, Source $source, string $year, ?string $sourceUrl = null): void
    {
        SourceReference::query()->updateOrCreate(
            [
                'source_id' => $source->id,
                'referencable_type' => $model::class,
                'referencable_id' => $model->id,
                'academic_year' => $year,
            ],
            [
                'verification_status' => VerificationStatus::NeedsReview,
                'source_url' => $sourceUrl,
                // last_verified_at stays NULL: ingestion never self-verifies.
            ],
        );
    }

    private function attachTaxonomy(Program $program, NormalizedProgram $record): void
    {
        if ($record->interests !== []) {
            $ids = Interest::query()->whereIn('code', $record->interests)->pluck('id');
            $program->interests()->syncWithoutDetaching($ids);
        }

        if ($record->skills !== []) {
            $ids = Skill::query()->whereIn('code', $record->skills)->pluck('id');
            $program->skills()->syncWithoutDetaching($ids);
        }
    }

    private function ensureSource(): Source
    {
        $type = SourceType::tryFrom($this->definition->sourceType()) ?? SourceType::Other;

        return Source::query()->updateOrCreate(
            ['slug' => $this->definition->slug()],
            [
                'name' => $this->definition->name(),
                'type' => $type->value,
                'trust_level' => $type->trustLevel(),
                'website' => $this->definition->website(),
            ],
        );
    }

    private function resolveYear(?string $fromRecord): string
    {
        return $this->yearOverride
            ?? ($fromRecord !== null && $fromRecord !== '' ? $fromRecord : $this->definition->defaultAcademicYear());
    }

    private function levelIdFor(NormalizedProgram $record): ?int
    {
        return $record->levelCode !== null
            ? EducationLevel::query()->where('code', $record->levelCode)->value('id')
            : null;
    }

    /**
     * Sets attributes only where the stored value is still empty.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function fillEmpty(Institution|Program $model, array $attributes): void
    {
        $updates = [];

        foreach ($attributes as $key => $value) {
            if ($value !== null && $value !== '' && ($model->{$key} === null || $model->{$key} === '')) {
                $updates[$key] = $value;
            }
        }

        if ($updates !== []) {
            $model->forceFill($updates)->save();
        }
    }

    private function uniqueProgramSlug(string $base): string
    {
        if (! Program::query()->where('slug', $base)->exists()) {
            return $base;
        }

        throw new RuntimeException("Program slug collision for [{$base}]; refusing to guess.");
    }
}
