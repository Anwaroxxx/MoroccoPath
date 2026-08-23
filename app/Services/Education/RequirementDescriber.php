<?php

namespace App\Services\Education;

use App\Enums\EligibilityConditionType;
use App\Models\EligibilityRule;

/**
 * Turns eligibility conditions into plain, student-friendly sentences.
 * Kept separate from the engine so wording can evolve (and later be
 * localized) without touching evaluation logic.
 */
final class RequirementDescriber
{
    public static function describe(EligibilityRule $rule): string
    {
        $base = match ($rule->condition_type) {
            EligibilityConditionType::EducationLevelMin => self::levelMin($rule),
            EligibilityConditionType::EducationLevelAnyOf => self::levelAnyOf($rule),
            EligibilityConditionType::QualificationAnyOf => self::qualificationAnyOf($rule),
            EligibilityConditionType::BacBranchAnyOf => self::bacBranchAnyOf($rule),
            EligibilityConditionType::MaxAge => sprintf('Maximum age: %d.', (int) ($rule->parameters['max_age'] ?? 0)),
            EligibilityConditionType::MinGrade => sprintf(
                'Minimum grade: %s/20.',
                self::formatGrade($rule->parameters['min_grade'] ?? null),
            ),
            EligibilityConditionType::EntranceExam => 'Requires passing an entrance exam.',
            EligibilityConditionType::Interview => 'Requires an interview.',
            EligibilityConditionType::Competition => 'Admission is by competition (limited seats).',
            EligibilityConditionType::Other => $rule->name ?? 'Additional requirement applies.',
        };

        return $rule->negate
            ? 'Must NOT: '.lcfirst($base)
            : $base;
    }

    private static function levelMin(EligibilityRule $rule): string
    {
        $name = $rule->educationLevels->pluck('name')->first() ?? 'an unspecified level';

        return sprintf('Requires an education level of %s or higher.', $name);
    }

    private static function levelAnyOf(EligibilityRule $rule): string
    {
        $names = $rule->educationLevels->pluck('name')->implode(', ') ?: 'unspecified';

        return sprintf('Requires education level: %s.', $names);
    }

    private static function qualificationAnyOf(EligibilityRule $rule): string
    {
        $names = $rule->qualifications->pluck('name')->implode(' or ') ?: 'unspecified';

        return sprintf('Requires one of these qualifications: %s.', $names);
    }

    private static function bacBranchAnyOf(EligibilityRule $rule): string
    {
        $names = $rule->bacBranches->pluck('name')->implode(' or ');

        if ($names === '') {
            return 'Requires a Bac in an unspecified branch.';
        }

        return sprintf('Requires a Bac in %s.', $names);
    }

    private static function formatGrade(mixed $grade): string
    {
        if (! is_numeric($grade)) {
            return '?';
        }

        $formatted = rtrim(rtrim((string) (float) $grade, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
