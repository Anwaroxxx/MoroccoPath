<?php

namespace App\Http\Requests;

use App\Enums\StudyMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class OrientationUpdateRequest extends FormRequest
{
    /**
     * Every field is optional (spec §17) — the system must work even when
     * the student knows very little. But whatever IS provided must be valid.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $codes = fn (string $table): Exists => Rule::exists($table, 'code');

        return [
            'education_level_id' => ['nullable', Rule::exists('education_levels', 'id')],
            'qualification_id' => ['nullable', Rule::exists('qualifications', 'id')],
            'bac_branch_codes' => ['nullable', 'array', 'max:5'],
            'bac_branch_codes.*' => [Rule::exists('bac_branches', 'code')],
            'bac_grade' => ['nullable', 'numeric', 'between:0,20'],
            'age' => ['nullable', 'integer', 'between:10,80'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'willing_to_relocate' => ['nullable', 'boolean'],
            'preferred_study_mode' => ['nullable', Rule::enum(StudyMode::class)],
            'interest_codes' => ['nullable', 'array', 'max:12'],
            'interest_codes.*' => [$codes('interests')],
            'skill_codes' => ['nullable', 'array', 'max:15'],
            'skill_codes.*' => [$codes('skills')],
            'career_goal_codes' => ['nullable', 'array', 'max:5'],
            'career_goal_codes.*' => [$codes('careers')],
        ];
    }
}
