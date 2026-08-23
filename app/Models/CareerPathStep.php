<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $career_path_id
 * @property int|null $parent_step_id
 * @property int $position
 * @property string $title
 */
#[Fillable([
    'career_path_id',
    'parent_step_id',
    'position',
    'title',
    'description',
    'education_level_id',
    'program_id',
])]
class CareerPathStep extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<CareerPath, $this> */
    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(CareerPath::class);
    }

    /** @return BelongsTo<CareerPathStep, $this> */
    public function parentStep(): BelongsTo
    {
        return $this->belongsTo(CareerPathStep::class, 'parent_step_id');
    }

    /** @return HasMany<CareerPathStep, $this> */
    public function childSteps(): HasMany
    {
        return $this->hasMany(CareerPathStep::class, 'parent_step_id')
            ->orderBy('position');
    }

    /** @return BelongsTo<EducationLevel, $this> */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
