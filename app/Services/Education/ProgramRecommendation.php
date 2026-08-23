<?php

namespace App\Services\Education;

use App\Models\Program;

/**
 * One scored recommendation: the program, why it fits (or not),
 * what is missing, and realistic alternatives when out of reach.
 */
final class ProgramRecommendation
{
    /**
     * @param  array<int, string>  $reasons
     * @param  array<int, string>  $missingRequirements
     * @param  array<int, array{slug: string, name: string}>  $alternatives
     */
    public function __construct(
        public readonly Program $program,
        public readonly ?int $matchScore,
        public readonly bool $eligible,
        public readonly array $reasons,
        public readonly array $missingRequirements,
        public readonly array $alternatives,
        public readonly EligibilityResult $eligibility,
    ) {}

    /**
     * Shape shared by Inertia pages and the future mobile API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $campus = $this->program->campus;

        return [
            'program' => [
                'id' => $this->program->id,
                'slug' => $this->program->slug,
                'name' => $this->program->name,
                'study_mode' => $this->program->study_mode->value,
                'duration_label' => $this->program->duration_label,
                'institution_name' => $this->program->institution->canonical_name,
                'city' => $campus->city ?? $this->program->institution->campuses->first()?->city,
                'is_free' => $this->program->costs->contains(fn ($cost): bool => $cost->is_free),
            ],
            'match_score' => $this->matchScore,
            'eligible' => $this->eligible,
            'reasons' => $this->reasons,
            'missing_requirements' => $this->missingRequirements,
            'alternatives' => $this->alternatives,
            'eligibility' => $this->eligibility->toArray(),
        ];
    }
}
