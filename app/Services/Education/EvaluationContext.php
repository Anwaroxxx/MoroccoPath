<?php

namespace App\Services\Education;

use App\Models\BacBranch;
use App\Models\EducationLevel;
use App\Models\Qualification;

/**
 * Resolves a StudentProfile's free-form codes into database-backed
 * taxonomy entities once, so every condition compares against the same facts.
 */
final class EvaluationContext
{
    public readonly ?EducationLevel $educationLevel;

    public readonly ?Qualification $qualification;

    /**
     * Bac branch codes validated against the canonical table.
     *
     * @var array<int, string>
     */
    public readonly array $bacBranchCodes;

    /**
     * The rank of whatever the student holds: their declared level, or the
     * academic equivalent of their professional qualification, whichever is higher.
     */
    public readonly int $effectiveRank;

    public function __construct(public readonly StudentProfile $profile)
    {
        $this->educationLevel = $profile->educationLevelCode !== null
            ? EducationLevel::query()->where('code', $profile->educationLevelCode)->first()
            : null;

        $this->qualification = $profile->qualificationCode !== null
            ? Qualification::query()->where('code', $profile->qualificationCode)->first()
            : null;

        // Keep only codes that exist in the canonical branch table.
        $this->bacBranchCodes = $profile->bacBranchCodes === []
            ? []
            : BacBranch::query()
                ->whereIn('code', $profile->bacBranchCodes)
                ->pluck('code')
                ->all();

        $levelRank = $this->educationLevel === null
            ? PHP_INT_MIN
            : $this->educationLevel->rank;

        $equivalentLevel = $this->qualification?->equivalentLevel;
        $qualificationRank = $equivalentLevel === null
            ? PHP_INT_MIN
            : $equivalentLevel->rank;

        $this->effectiveRank = max($levelRank, $qualificationRank);
    }

    public function hasAnyLevelInformation(): bool
    {
        return $this->educationLevel !== null || $this->qualification !== null;
    }
}
