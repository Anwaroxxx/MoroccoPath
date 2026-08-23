<?php

namespace App\Models;

use App\Services\Education\StudentProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's self-declared situation (spec §17). Personal state — kept
 * separate from canonical reference data. Everything is optional so the
 * system works even when very little is known.
 *
 * @property int $user_id
 * @property array<int, string>|null $bac_branch_codes
 * @property array<int, string>|null $interest_codes
 * @property array<int, string>|null $skill_codes
 * @property array<int, string>|null $career_goal_codes
 */
#[Fillable([
    'user_id',
    'education_level_id',
    'qualification_id',
    'bac_branch_codes',
    'interest_codes',
    'skill_codes',
    'career_goal_codes',
    'bac_grade',
    'age',
    'city',
    'region',
    'budget_max',
    'willing_to_relocate',
    'preferred_study_mode',
])]
class UserProfile extends Model
{
    protected function casts(): array
    {
        return [
            'bac_branch_codes' => 'array',
            'interest_codes' => 'array',
            'skill_codes' => 'array',
            'career_goal_codes' => 'array',
            'bac_grade' => 'float',
            'age' => 'integer',
            'budget_max' => 'float',
            'willing_to_relocate' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<EducationLevel, $this> */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    /** @return BelongsTo<Qualification, $this> */
    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }

    /**
     * Maps the stored profile onto the engines' StudentProfile input.
     */
    public function toStudentProfile(): StudentProfile
    {
        return new StudentProfile(
            educationLevelCode: $this->educationLevel?->code,
            qualificationCode: $this->qualification?->code,
            bacBranchCodes: $this->bac_branch_codes ?? [],
            bacGrade: $this->bac_grade,
            age: $this->age,
            city: $this->city,
            region: $this->region,
            budgetMax: $this->budget_max,
            willingToRelocate: $this->willing_to_relocate,
            interestCodes: $this->interest_codes ?? [],
            skillCodes: $this->skill_codes ?? [],
            careerGoalCodes: $this->career_goal_codes ?? [],
        );
    }
}
