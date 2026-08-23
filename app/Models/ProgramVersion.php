<?php

namespace App\Models;

use App\Enums\ProgramVersionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Academic-year snapshot of a program. Historical facts are never overwritten.
 *
 * @property int $id
 * @property int $program_id
 * @property string $academic_year
 * @property ProgramVersionStatus $status
 * @property Carbon|null $application_start
 * @property Carbon|null $application_end
 */
#[Fillable([
    'program_id',
    'academic_year',
    'duration_months',
    'status',
    'admission_information',
    'application_start',
    'application_end',
])]
class ProgramVersion extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ProgramVersionStatus::class,
            'duration_months' => 'integer',
            'application_start' => 'date',
            'application_end' => 'date',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return HasMany<ApplicationPeriod, $this> */
    public function applicationPeriods(): HasMany
    {
        return $this->hasMany(ApplicationPeriod::class);
    }
}
