<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $field_id
 * @property string $code
 * @property string $name
 */
#[Fillable(['field_id', 'code', 'name', 'description'])]
class Specialty extends Model
{
    /** @return BelongsTo<Field, $this> */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /** @return HasMany<Program, $this> */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
