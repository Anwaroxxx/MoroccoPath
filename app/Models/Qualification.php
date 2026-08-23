<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A named diploma (TECHNICIEN, BTS, LICENCE...) mapped to its academic equivalent.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 */
#[Fillable(['code', 'name', 'equivalent_level_id'])]
class Qualification extends Model
{
    /** @return BelongsTo<EducationLevel, $this> */
    public function equivalentLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'equivalent_level_id');
    }
}
