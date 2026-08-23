<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A multi-step route towards a career ("Niveau Bac -> 1337 -> Developer").
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 */
#[Fillable(['slug', 'name', 'description', 'field_id', 'target_career_id'])]
class CareerPath extends Model
{
    /** @return HasMany<CareerPathStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(CareerPathStep::class)
            ->orderBy('position');
    }

    /** @return BelongsTo<Field, $this> */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /** @return BelongsTo<Career, $this> */
    public function targetCareer(): BelongsTo
    {
        return $this->belongsTo(Career::class, 'target_career_id');
    }
}
