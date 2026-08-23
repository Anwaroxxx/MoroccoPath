<?php

namespace App\Services\Education;

use App\Enums\EligibilityConditionType;
use App\Enums\ProgramVersionStatus;
use App\Enums\SourceType;
use App\Enums\VerificationStatus;
use App\Models\BacBranch;
use App\Models\EducationLevel;
use App\Models\EligibilityRule;
use App\Models\Program;
use App\Models\Qualification;
use App\Models\SourceReference;
use Illuminate\Support\Collection;

/**
 * Deterministic eligibility evaluator.
 *
 * Rule semantics:
 *  - Each rule row is one atomic condition; its pivoted values are alternatives (OR).
 *  - Rows sharing a logic_group are AND-ed together.
 *  - Distinct logic_groups are OR-ed: at least one full group must pass.
 *  - A negated row inverts a single condition.
 *
 * Fail-closed policy: conditions that cannot be evaluated (missing student
 * data or misconfigured rules) count as failed and raise a warning.
 * Process conditions (exam/interview/competition) inform but never block.
 */
final class EligibilityEngine
{
    public function evaluate(Program $program, StudentProfile $profile): EligibilityResult
    {
        $sources = $this->describeSources($program);
        /** @var array<int, string> $warnings */
        $warnings = [];

        // Gate 1: an expired academic-year snapshot closes applications.
        $expiredReason = $this->expiredVersionReason($program);
        if ($expiredReason !== null) {
            return new EligibilityResult(
                eligible: false,
                score: null,
                reasons: [$expiredReason],
                warnings: $warnings,
                sources: $sources,
            );
        }

        // Gate 2: conflicting official sources must never be silently resolved.
        if ($this->hasConflictingSources($program)) {
            return new EligibilityResult(
                eligible: false,
                score: null,
                reasons: ['Information about this program conflicts between official sources and needs review.'],
                warnings: ['This record is flagged CONFLICTING and awaits administrator review.'],
                sources: $sources,
            );
        }

        /** @var Collection<int, EligibilityRule> $rules */
        $rules = $program->eligibilityRules()
            ->where('is_active', true)
            ->with(['educationLevels', 'qualifications', 'bacBranches'])
            ->orderBy('sort_order')
            ->get();

        if ($rules->isEmpty()) {
            return new EligibilityResult(
                eligible: true,
                score: null,
                warnings: array_values(array_unique([
                    'No verified eligibility criteria are recorded for this program yet.',
                    ...$this->sourceTrustWarning($program),
                ])),
                sources: $sources,
            );
        }

        $context = new EvaluationContext($profile);

        $matched = [];
        $failed = [];
        $warnings = [];
        $scoreTotal = 0;
        $scorePassed = 0;
        $educationPassed = 0;
        $educationTotal = 0;
        $anyGroupPasses = false;

        foreach ($rules->groupBy(fn (EligibilityRule $rule): string => $rule->logic_group) as $groupRules) {
            $groupPasses = true;

            foreach ($groupRules as $rule) {
                $outcome = $this->evaluateRule($rule, $context);
                // A negated row inverts its own condition (NOT).
                $passed = $rule->negate ? ! $outcome->passed : $outcome->passed;

                if ($outcome->note !== null) {
                    $warnings[] = $outcome->note;
                }

                if ($rule->condition_type->isProcessCondition()) {
                    continue;
                }

                if ($this->isEducationCondition($rule)) {
                    $educationTotal++;
                    if ($passed) {
                        $educationPassed++;
                    }
                }

                $sentence = RequirementDescriber::describe($rule);
                $scoreTotal++;

                if ($passed) {
                    $matched[] = $sentence;
                    $scorePassed++;
                } else {
                    $failed[] = $sentence;
                    $groupPasses = false;
                }
            }

            if ($groupPasses) {
                $anyGroupPasses = true;
            }
        }

        $warnings = [
            ...$warnings,
            ...$this->processRequirementWarnings($rules),
            ...$this->sourceTrustWarning($program),
        ];

        return new EligibilityResult(
            eligible: $anyGroupPasses,
            score: $scoreTotal > 0 ? (int) round(($scorePassed / $scoreTotal) * 100) : null,
            reasons: $this->buildReasons($anyGroupPasses, $failed),
            matchedRequirements: $matched,
            failedRequirements: $failed,
            warnings: array_values(array_unique($warnings)),
            sources: $sources,
            educationPassed: $educationTotal > 0 ? $educationPassed : null,
            educationTotal: $educationTotal > 0 ? $educationTotal : null,
        );
    }

    private function isEducationCondition(EligibilityRule $rule): bool
    {
        return in_array($rule->condition_type, [
            EligibilityConditionType::EducationLevelMin,
            EligibilityConditionType::EducationLevelAnyOf,
            EligibilityConditionType::QualificationAnyOf,
            EligibilityConditionType::BacBranchAnyOf,
        ], true);
    }

    /**
     * Evaluate one atomic condition against the student context.
     * The returned note (when set) explains why evaluation was impossible.
     */
    private function evaluateRule(EligibilityRule $rule, EvaluationContext $context): ConditionOutcome
    {
        return match ($rule->condition_type) {
            EligibilityConditionType::EducationLevelMin => $this->evaluateLevelMin($rule, $context),
            EligibilityConditionType::EducationLevelAnyOf => $this->evaluateLevelAnyOf($rule, $context),
            EligibilityConditionType::QualificationAnyOf => $this->evaluateQualificationAnyOf($rule, $context),
            EligibilityConditionType::BacBranchAnyOf => $this->evaluateBacBranchAnyOf($rule, $context),
            EligibilityConditionType::MaxAge => $this->evaluateMaxAge($rule, $context),
            EligibilityConditionType::MinGrade => $this->evaluateMinGrade($rule, $context),
            EligibilityConditionType::EntranceExam,
            EligibilityConditionType::Interview,
            EligibilityConditionType::Competition,
            EligibilityConditionType::Other => new ConditionOutcome(passed: true),
        };
    }

    private function evaluateLevelMin(EligibilityRule $rule, EvaluationContext $context): ConditionOutcome
    {
        if (! $context->hasAnyLevelInformation()) {
            return ConditionOutcome::unevaluable('Your education level was not provided, so this requirement could not be checked.');
        }

        $requiredRank = $rule->educationLevels->min(fn (EducationLevel $level): int => $level->rank);

        if ($requiredRank === null) {
            return ConditionOutcome::unevaluable('This requirement is misconfigured (no education level attached).');
        }

        return new ConditionOutcome(passed: $context->effectiveRank >= $requiredRank);
    }

    private function evaluateLevelAnyOf(EligibilityRule $rule, EvaluationContext $context): ConditionOutcome
    {
        if (! $context->hasAnyLevelInformation()) {
            return ConditionOutcome::unevaluable('Your education level was not provided, so this requirement could not be checked.');
        }

        if ($rule->educationLevels->isEmpty()) {
            return ConditionOutcome::unevaluable('This requirement is misconfigured (no education level attached).');
        }

        $acceptedRanks = $rule->educationLevels
            ->map(fn (EducationLevel $level): int => $level->rank);

        return new ConditionOutcome(passed: $acceptedRanks->contains($context->effectiveRank));
    }

    private function evaluateQualificationAnyOf(EligibilityRule $rule, EvaluationContext $context): ConditionOutcome
    {
        if ($rule->qualifications->isEmpty()) {
            return ConditionOutcome::unevaluable('This requirement is misconfigured (no qualification attached).');
        }

        if ($context->qualification === null) {
            return new ConditionOutcome(passed: false);
        }

        $qualificationCode = $context->qualification->code;

        return new ConditionOutcome(passed: $rule->qualifications
            ->contains(fn (Qualification $q): bool => $q->code === $qualificationCode));
    }

    private function evaluateBacBranchAnyOf(EligibilityRule $rule, EvaluationContext $context): ConditionOutcome
    {
        if ($rule->bacBranches->isEmpty()) {
            return ConditionOutcome::unevaluable('This requirement is misconfigured (no Bac branch attached).');
        }

        if ($context->bacBranchCodes === []) {
            return new ConditionOutcome(passed: false);
        }

        return new ConditionOutcome(passed: $rule->bacBranches
            ->contains(fn (BacBranch $branch): bool => in_array($branch->code, $context->bacBranchCodes, true)));
    }

    private function evaluateMaxAge(EligibilityRule $rule, EvaluationContext $context): ConditionOutcome
    {
        $maxAge = $rule->parameters['max_age'] ?? null;

        if (! is_numeric($maxAge)) {
            return ConditionOutcome::unevaluable('This age requirement is misconfigured.');
        }

        if ($context->profile->age === null) {
            return ConditionOutcome::unevaluable('Your age was not provided, so this requirement could not be checked.');
        }

        return new ConditionOutcome(passed: $context->profile->age <= (int) $maxAge);
    }

    private function evaluateMinGrade(EligibilityRule $rule, EvaluationContext $context): ConditionOutcome
    {
        $minGrade = $rule->parameters['min_grade'] ?? null;

        if (! is_numeric($minGrade)) {
            return ConditionOutcome::unevaluable('This grade requirement is misconfigured.');
        }

        if ($context->profile->bacGrade === null) {
            return ConditionOutcome::unevaluable('Your grade was not provided, so this requirement could not be checked.');
        }

        return new ConditionOutcome(passed: $context->profile->bacGrade >= (float) $minGrade);
    }

    /**
     * @param  array<int, string>  $failed
     * @return array<int, string>
     */
    private function buildReasons(bool $eligible, array $failed): array
    {
        if ($eligible) {
            return ['Your profile matches the published requirements for this program.'];
        }

        return [
            'Your profile does not currently meet the requirements below.',
            ...$failed,
        ];
    }

    private function expiredVersionReason(Program $program): ?string
    {
        $latest = $program->versions()
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->first();

        if ($latest !== null && $latest->status === ProgramVersionStatus::Expired) {
            return sprintf(
                'Applications for this program are closed: the %s edition has expired.',
                $latest->academic_year,
            );
        }

        return null;
    }

    private function hasConflictingSources(Program $program): bool
    {
        return $program->sourceReferences()
            ->where('verification_status', VerificationStatus::Conflicting->value)
            ->exists();
    }

    /**
     * Warn when a program's facts rest solely on low-trust sources.
     *
     * @return array<int, string>
     */
    private function sourceTrustWarning(Program $program): array
    {
        $references = $program->sourceReferences()->with('source')->get();

        if ($references->isEmpty()) {
            return [];
        }

        $hasOfficialBacking = $references->contains(
            fn ($reference): bool => $reference->source !== null
                && $reference->source->trust_level <= SourceType::GovernmentOpenData->trustLevel(),
        );

        return $hasOfficialBacking
            ? []
            : ['This information comes only from unofficial sources and still needs official verification.'];
    }

    /**
     * @param  Collection<int, EligibilityRule>  $rules
     * @return array<int, string>
     */
    private function processRequirementWarnings($rules): array
    {
        return $rules
            ->filter(fn (EligibilityRule $rule): bool => $rule->condition_type->isProcessCondition())
            ->map(fn (EligibilityRule $rule): string => RequirementDescriber::describe($rule))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{source: string, url: string|null, verification_status: string, academic_year: string|null, last_verified_at: string|null}>
     */
    private function describeSources(Program $program): array
    {
        return $program->sourceReferences()
            ->with('source')
            ->orderBy('id')
            ->get()
            ->map(fn (SourceReference $reference): array => [
                'source' => $reference->source->name,
                'url' => $reference->source_url,
                'verification_status' => $reference->verification_status->value,
                'academic_year' => $reference->academic_year,
                'last_verified_at' => $reference->last_verified_at?->toDateString(),
            ])
            ->all();
    }
}
