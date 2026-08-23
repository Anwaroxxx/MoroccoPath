<?php

namespace App\Services\Ingestion;

use App\Enums\InstitutionType;
use App\Enums\StudyMode;
use Illuminate\Support\Facades\Validator;

/**
 * Fail-closed validation gate: nothing reaches production tables unless it
 * passes its rule set (spec §23). Rejected records are reported, never
 * partially inserted.
 */
final class RecordValidator
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed> the validated data
     */
    public function validateInstitution(array $attributes): array
    {
        return Validator::validate($attributes, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'canonical_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_column(InstitutionType::cases(), 'value'))],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validateCampus(array $attributes): array
    {
        return Validator::validate($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validateProgram(array $attributes): array
    {
        return Validator::validate($attributes, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'study_mode' => ['nullable', 'in:'.implode(',', array_column(StudyMode::cases(), 'value'))],
            'duration_months' => ['nullable', 'integer', 'between:1,144'],
            'duration_label' => ['nullable', 'string', 'max:64'],
            'language' => ['nullable', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:5000'],
            'admission_information' => ['nullable', 'string', 'max:2000'],
            'academic_year' => ['nullable', 'regex:/^\d{4}\/\d{4}$/'],
        ]);
    }
}
