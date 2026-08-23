<?php

namespace App\Services\Education;

/**
 * Immutable outcome of evaluating a student profile against one program.
 * The user must always understand WHY: reasons, failures, warnings and sources.
 */
final class EligibilityResult
{
    /**
     * @param  array<int, string>  $reasons
     * @param  array<int, string>  $matchedRequirements
     * @param  array<int, string>  $failedRequirements
     * @param  array<int, string>  $warnings
     * @param  array<int, array{source: string, url: string|null, verification_status: string, academic_year: string|null, last_verified_at: string|null}>  $sources
     */
    public function __construct(
        public readonly bool $eligible,
        public readonly ?int $score,
        public readonly array $reasons = [],
        public readonly array $matchedRequirements = [],
        public readonly array $failedRequirements = [],
        public readonly array $warnings = [],
        public readonly array $sources = [],
    ) {}

    /**
     * Shape shared by Inertia pages and the future mobile API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eligible' => $this->eligible,
            'score' => $this->score,
            'reasons' => $this->reasons,
            'matched_requirements' => $this->matchedRequirements,
            'failed_requirements' => $this->failedRequirements,
            'warnings' => $this->warnings,
            'sources' => $this->sources,
        ];
    }
}
