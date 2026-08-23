<?php

namespace App\Enums;

/**
 * Atomic condition types supported by the eligibility engine.
 *
 * Pivot-backed types (ANY_OF) read their candidate values from the
 * eligibility_rule_* pivot tables; parameter-backed types read from
 * the rule's `parameters` JSON column.
 */
enum EligibilityConditionType: string
{
    /** User's effective education level must be at least the pivoted level. */
    case EducationLevelMin = 'education_level_min';

    /** User's education level is one of the pivoted levels (exact match). */
    case EducationLevelAnyOf = 'education_level_any_of';

    /** User holds one of the pivoted qualifications. */
    case QualificationAnyOf = 'qualification_any_of';

    /** User's Bac branch is one of the pivoted branches. */
    case BacBranchAnyOf = 'bac_branch_any_of';

    /** parameters.max_age — user's age must not exceed it. */
    case MaxAge = 'max_age';

    /** parameters.min_grade — user's grade (out of 20) must reach it. */
    case MinGrade = 'min_grade';

    /** Informational: admission requires an entrance exam. */
    case EntranceExam = 'entrance_exam';

    /** Informational: admission requires an interview. */
    case Interview = 'interview';

    /** Informational: admission is by competition/numerus clausus. */
    case Competition = 'competition';

    /** Free-form condition, never auto-evaluated — surfaced for review. */
    case Other = 'other';

    /**
     * Process conditions describe how to apply, not who may apply.
     * They never block eligibility and are excluded from scoring.
     */
    public function isProcessCondition(): bool
    {
        return match ($this) {
            self::EntranceExam, self::Interview, self::Competition, self::Other => true,
            default => false,
        };
    }
}
