<?php

namespace App\Models;

use App\Enums\AdmissionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How candidates get admitted to a program (dossier, concours, exam...).
 *
 * @property int $id
 * @property int $program_id
 * @property string $type
 * @property string $title
 */
#[Fillable([
    'program_id',
    'type',
    'title',
    'description',
    'sort_order',
    'is_active',
])]
class AdmissionRule extends Model
{
    protected function casts(): array
    {
        return [
            'type' => AdmissionType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
