<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A window in which candidates may apply for a specific program version.
 *
 * @property int $id
 * @property int $program_version_id
 */
#[Fillable([
    'program_version_id',
    'starts_at',
    'ends_at',
    'intake_label',
    'notes',
])]
class ApplicationPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    /** @return BelongsTo<ProgramVersion, $this> */
    public function programVersion(): BelongsTo
    {
        return $this->belongsTo(ProgramVersion::class);
    }
}
