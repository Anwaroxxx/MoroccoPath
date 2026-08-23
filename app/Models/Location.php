<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $city
 * @property string $region
 */
#[Fillable(['city', 'region'])]
class Location extends Model
{
    /** @return HasMany<Campus, $this> */
    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}
