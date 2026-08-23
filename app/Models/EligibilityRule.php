<?php

namespace App\Models;

use App\Enums\EligibilityConditionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One atomic eligibility condition attached to a program.
 *
 * @property int $id
 * @property int $program_id
 * @property EligibilityConditionType $condition_type
 * @property bool $negate
 * @property string $logic_group
 * @property array<string, mixed>|null $parameters
 */
#[Fillable([
    'program_id',
    'name',
    'condition_type',
    'negate',
    'logic_group',
    'parameters',
    'sort_order',
    'is_active',
])]
class EligibilityRule extends Model
{
    protected function casts(): array
    {
        return [
            'condition_type' => EligibilityConditionType::class,
            'negate' => 'boolean',
            'parameters' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsToMany<EducationLevel, $this> */
    public function educationLevels(): BelongsToMany
    {
        return $this->belongsToMany(
            EducationLevel::class,
            'eligibility_rule_education_levels',
        );
    }

    /** @return BelongsToMany<Qualification, $this> */
    public function qualifications(): BelongsToMany
    {
        return $this->belongsToMany(
            Qualification::class,
            'eligibility_rule_qualifications',
        );
    }

    /** @return BelongsToMany<BacBranch, $this> */
    public function bacBranches(): BelongsToMany
    {
        return $this->belongsToMany(
            BacBranch::class,
            'eligibility_rule_bac_branches',
        );
    }
}
