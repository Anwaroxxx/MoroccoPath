<?php

namespace App\Services\Education;

/**
 * Immutable snapshot of a student's situation fed into the eligibility
 * and recommendation engines. Codes reference seeded taxonomy tables;
 * everything except the education level is optional so the system can
 * work even when very little is known.
 */
final class StudentProfile
{
    /**
     * @param  array<int, string>  $bacBranchCodes
     */
    public function __construct(
        public readonly ?string $educationLevelCode = null,
        public readonly ?string $qualificationCode = null,
        public readonly array $bacBranchCodes = [],
        public readonly ?float $bacGrade = null,
        public readonly ?int $age = null,
        public readonly ?string $city = null,
        public readonly ?string $region = null,
        public readonly ?float $budgetMax = null,
        public readonly bool $willingToRelocate = false,
    ) {}
}
