<?php

namespace App\Models;

use App\Enums\CostType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $program_id
 * @property string|null $academic_year
 * @property CostType $cost_type
 * @property float|null $amount_min
 * @property float|null $amount_max
 * @property string $currency
 * @property bool $is_free
 */
#[Fillable([
    'program_id',
    'academic_year',
    'cost_type',
    'amount_min',
    'amount_max',
    'currency',
    'is_free',
    'notes',
])]
class Cost extends Model
{
    protected function casts(): array
    {
        return [
            'cost_type' => CostType::class,
            'amount_min' => 'float',
            'amount_max' => 'float',
            'is_free' => 'boolean',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
