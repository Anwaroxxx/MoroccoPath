<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "If program X is out of reach, program Y is a realistic alternative."
 *
 * @property int $id
 * @property int $program_id
 * @property int $alternative_program_id
 */
#[Fillable(['program_id', 'alternative_program_id', 'note', 'sort_order'])]
class AlternativePath extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /** @return BelongsTo<Program, $this> */
    public function alternativeProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'alternative_program_id');
    }
}
