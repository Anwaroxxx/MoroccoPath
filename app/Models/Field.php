<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
#[Fillable(['code', 'name', 'description'])]
class Field extends Model
{
    /** @return HasMany<Specialty, $this> */
    public function specialties(): HasMany
    {
        return $this->hasMany(Specialty::class);
    }

    /** @return HasMany<CareerPath, $this> */
    public function careerPaths(): HasMany
    {
        return $this->hasMany(CareerPath::class);
    }
}
