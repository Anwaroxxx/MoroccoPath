<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $institution_id
 * @property string $name
 * @property string $city
 * @property string $region
 */
#[Fillable([
    'institution_id',
    'location_id',
    'name',
    'city',
    'region',
    'address',
    'latitude',
    'longitude',
    'website',
    'is_primary',
])]
class Campus extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
