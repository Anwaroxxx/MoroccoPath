<?php

namespace App\Models;

use App\Enums\SourceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An organization that publishes verifiable facts about education in Morocco.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property int $trust_level
 * @property string|null $website
 */
#[Fillable(['name', 'slug', 'type', 'trust_level', 'website', 'description'])]
class Source extends Model
{
    public function typeEnum(): SourceType
    {
        return SourceType::from($this->type);
    }

    /** @return HasMany<SourceReference, $this> */
    public function references(): HasMany
    {
        return $this->hasMany(SourceReference::class);
    }
}
