<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Provenance citation attached to any important record.
 * Answers: where did this come from, when verified, which academic year, still valid?
 *
 * @property int $id
 * @property int $source_id
 * @property string|null $source_url
 * @property string|null $source_title
 * @property Carbon|null $published_at
 * @property Carbon|null $last_verified_at
 * @property string|null $academic_year
 * @property VerificationStatus $verification_status
 */
#[Fillable([
    'source_id',
    'referencable_type',
    'referencable_id',
    'source_url',
    'source_title',
    'published_at',
    'last_verified_at',
    'academic_year',
    'verification_status',
    'notes',
])]
class SourceReference extends Model
{
    protected function casts(): array
    {
        return [
            'verification_status' => VerificationStatus::class,
            'published_at' => 'date',
            'last_verified_at' => 'date',
        ];
    }

    /** @return BelongsTo<Source, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** @return MorphTo<Model, $this> */
    public function referencable(): MorphTo
    {
        return $this->morphTo();
    }
}
