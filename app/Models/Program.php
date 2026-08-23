<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use App\Enums\StudyMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A concrete educational opportunity at an institution.
 *
 * @property int $id
 * @property int $institution_id
 * @property string $name
 * @property string $slug
 * @property StudyMode $study_mode
 * @property ProgramStatus $status
 */
#[Fillable([
    'institution_id',
    'specialty_id',
    'campus_id',
    'name',
    'slug',
    'description',
    'education_level_id',
    'duration_months',
    'duration_label',
    'study_mode',
    'language',
    'status',
])]
class Program extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ProgramStatus::class,
            'study_mode' => StudyMode::class,
            'duration_months' => 'integer',
        ];
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /** @return BelongsTo<Specialty, $this> */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /** @return BelongsTo<Campus, $this> */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /** @return BelongsTo<EducationLevel, $this> */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    /** @return HasMany<ProgramVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ProgramVersion::class);
    }

    /** @return HasMany<EligibilityRule, $this> */
    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(EligibilityRule::class);
    }

    /** @return HasMany<AdmissionRule, $this> */
    public function admissionRules(): HasMany
    {
        return $this->hasMany(AdmissionRule::class);
    }

    /** @return HasMany<Cost, $this> */
    public function costs(): HasMany
    {
        return $this->hasMany(Cost::class);
    }

    /** @return HasMany<AlternativePath, $this> */
    public function alternativePaths(): HasMany
    {
        return $this->hasMany(AlternativePath::class, 'program_id');
    }

    /** @return MorphMany<SourceReference, $this> */
    public function sourceReferences(): MorphMany
    {
        return $this->morphMany(SourceReference::class, 'referencable');
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'program_skills');
    }

    /** @return BelongsToMany<Interest, $this> */
    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'program_interests');
    }

    /** @return BelongsToMany<Career, $this> */
    public function careers(): BelongsToMany
    {
        return $this->belongsToMany(Career::class, 'program_careers');
    }
}
