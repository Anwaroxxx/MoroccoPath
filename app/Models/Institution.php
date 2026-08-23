<?php

namespace App\Models;

use App\Enums\InstitutionStatus;
use App\Enums\InstitutionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $name
 * @property string $canonical_name
 * @property string $slug
 * @property InstitutionType $type
 * @property InstitutionStatus $status
 * @property string|null $external_identifier
 */
#[Fillable([
    'name',
    'canonical_name',
    'slug',
    'type',
    'description',
    'website',
    'phone',
    'email',
    'status',
    'external_identifier',
])]
class Institution extends Model
{
    protected function casts(): array
    {
        return [
            'type' => InstitutionType::class,
            'status' => InstitutionStatus::class,
        ];
    }

    /** @return HasMany<Campus, $this> */
    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }

    /** @return HasMany<InstitutionAlias, $this> */
    public function aliases(): HasMany
    {
        return $this->hasMany(InstitutionAlias::class);
    }

    /** @return HasMany<Program, $this> */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /** @return MorphMany<SourceReference, $this> */
    public function sourceReferences(): MorphMany
    {
        return $this->morphMany(SourceReference::class, 'referencable');
    }
}
