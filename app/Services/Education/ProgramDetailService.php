<?php

namespace App\Services\Education;

use App\Enums\ProgramStatus;
use App\Models\Program;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds the canonical program-detail payload shared by the Inertia page
 * and the versioned API (spec §30). Draft/archived programs 404 here so
 * every client enforces the same visibility rules.
 */
final class ProgramDetailService
{
    /**
     * @return array<string, mixed>
     *
     * @throws HttpResponseException when the program is not publicly visible.
     */
    public function detail(Program $program): array
    {
        if ($program->status !== ProgramStatus::Published) {
            throw new HttpResponseException(response()->json(['message' => 'Not found.'], Response::HTTP_NOT_FOUND));
        }

        $program->load([
            'institution.campuses',
            'campus',
            'specialty.field',
            'costs',
            'versions' => fn ($query) => $query->orderByDesc('academic_year')->limit(1)->with('applicationPeriods'),
            'eligibilityRules' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['educationLevels', 'qualifications', 'bacBranches'])
                ->orderBy('sort_order'),
            'careers:id,code,name',
            'alternativePaths.alternativeProgram:id,slug,name,status',
        ]);

        $requirements = $program->eligibilityRules
            ->filter(fn ($rule): bool => ! $rule->condition_type->isProcessCondition());
        $processSteps = $program->eligibilityRules
            ->filter(fn ($rule): bool => $rule->condition_type->isProcessCondition());

        $version = $program->versions->first();

        return [
            'id' => $program->id,
            'name' => $program->name,
            'slug' => $program->slug,
            'description' => $program->description,
            'study_mode' => $program->study_mode->value,
            'duration_label' => $program->duration_label,
            'language' => $program->language,
            'institution_name' => $program->institution->canonical_name,
            'institution_slug' => $program->institution->slug,
            'city' => $program->campus !== null
                ? $program->campus->city
                : $program->institution->campuses->pluck('city')->first(),
            'is_free' => $program->costs->contains(fn ($cost): bool => $cost->is_free),
            'costs' => $program->costs->map(fn ($cost): array => [
                'type' => $cost->cost_type->value,
                'label' => $cost->cost_type->label(),
                'amount_min' => $cost->amount_min,
                'amount_max' => $cost->amount_max,
                'currency' => $cost->currency,
                'is_free' => $cost->is_free,
                'academic_year' => $cost->academic_year,
            ])->all(),
            'version' => $version !== null ? [
                'academic_year' => $version->academic_year,
                'status' => $version->status->value,
                'admission_information' => $version->admission_information,
                'application_start' => $version->application_start?->toDateString(),
                'application_end' => $version->application_end?->toDateString(),
            ] : null,
            'requirements' => $requirements
                ->map(fn ($rule): string => RequirementDescriber::describe($rule))
                ->values()
                ->all(),
            'process_steps' => $processSteps
                ->map(fn ($rule): string => RequirementDescriber::describe($rule))
                ->values()
                ->all(),
            'careers' => $program->careers->pluck('name')->all(),
            'alternatives' => $program->alternativePaths
                ->filter(fn ($path): bool => $path->alternativeProgram?->status === ProgramStatus::Published)
                ->map(fn ($path): array => ['slug' => $path->alternativeProgram->slug, 'name' => $path->alternativeProgram->name])
                ->values()
                ->all(),
        ];
    }
}
