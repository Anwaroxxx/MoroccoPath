<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single translated field of a canonical record (e.g. Program.name in ar).
 *
 * @property int $id
 * @property string $locale
 * @property string $field
 * @property string $value
 */
#[Fillable(['translatable_type', 'translatable_id', 'locale', 'field', 'value'])]
class Translation extends Model
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
