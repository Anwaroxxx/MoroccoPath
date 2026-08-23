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
class Career extends Model
{
    /** @return HasMany<CareerPath, $this> */
    public function pathsTargeting(): HasMany
    {
        return $this->hasMany(CareerPath::class, 'target_career_id');
    }
}
