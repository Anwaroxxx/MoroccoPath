<?php

namespace App\Services\Education;

use App\Enums\StudyMode;
use App\Models\AlternativePath;
use App\Models\Campus;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Deterministic recommendation scoring on top of the authoritative
 * EligibilityEngine. It never overrides eligibility: programs the student
 * cannot access stay visible, explained, and paired with alternatives.
 *
 * Weighted dimensions (spec §16):
 *   education 30 · interests 25 · skills 20 · location 10 · budget 10 · career 5
 *
 * A dimension only counts when there is enough information to judge it
 * (profile side AND program side); the final score renormalizes across
 * evaluable dimensions so minimal profiles still get meaningful results.
 * With nothing evaluable, match_score is null rather than invented.
 */
final class RecommendationEngine
{
    private const WEIGHT_EDUCATION = 30;

    private const WEIGHT_INTERESTS = 25;

    private const WEIGHT_SKILLS = 20;

    private const WEIGHT_LOCATION = 10;

    private const WEIGHT_BUDGET = 10;

    private const WEIGHT_CAREER = 5;

    public function __construct(private readonly EligibilityEngine $eligibilityEngine) {}

    /**
     * Score and rank the given programs for one student.
     * Programs should come pre-filtered (e.g. published only, pagination).
     *
     * @param  Collection<int, Program>  $programs
     * @return SupportCollection<int, ProgramRecommendation>
     */
    public function recommend(StudentProfile $profile, Collection $programs): SupportCollection
    {
        $programs->loadMissing([
            'institution.campuses',
            'campus',
            'costs',
            'interests',
            'skills',
            'careers',
            'alternativePaths.alternativeProgram',
        ]);

        $recommendations = $programs
            ->map(fn (Program $program): ProgramRecommendation => $this->scoreProgram($profile, $program))
            ->values();

        return $recommendations->sort(function (ProgramRecommendation $a, ProgramRecommendation $b): int {
            $scoreA = $a->matchScore ?? -1;
            $scoreB = $b->matchScore ?? -1;
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }
            if ($a->eligible !== $b->eligible) {
                return $a->eligible ? -1 : 1;
            }

            return strcasecmp($a->program->name, $b->program->name);
        })->values();
    }

    private function scoreProgram(StudentProfile $profile, Program $program): ProgramRecommendation
    {
        $eligibility = $this->eligibilityEngine->evaluate($program, $profile);

        $scores = [
            'education' => ['weight' => self::WEIGHT_EDUCATION, 'score' => $this->educationScore($eligibility, $profile)],
            'interests' => ['weight' => self::WEIGHT_INTERESTS, 'score' => $this->taxonomyOverlap($profile->interestCodes, $program->interests->pluck('code')->all())],
            'skills' => ['weight' => self::WEIGHT_SKILLS, 'score' => $this->taxonomyOverlap($profile->skillCodes, $program->skills->pluck('code')->all())],
            'location' => ['weight' => self::WEIGHT_LOCATION, 'score' => $this->locationScore($profile, $program)],
            'budget' => ['weight' => self::WEIGHT_BUDGET, 'score' => $this->budgetScore($profile, $program)],
            'career' => ['weight' => self::WEIGHT_CAREER, 'score' => $this->taxonomyOverlap($profile->careerGoalCodes, $program->careers->pluck('code')->all())],
        ];

        $weightSum = 0;
        $weightedSum = 0.0;
        foreach ($scores as ['weight' => $weight, 'score' => $score]) {
            if ($score !== null) {
                $weightSum += $weight;
                $weightedSum += $weight * $score;
            }
        }

        $matchScore = $weightSum > 0 ? (int) round(($weightedSum / $weightSum) * 100) : null;

        [$reasons, $missing] = $this->explain($profile, $program, $scores, $eligibility);

        $alternatives = $eligibility->eligible
            ? []
            : $program->alternativePaths
                ->map(fn (AlternativePath $path): array => [
                    'slug' => $path->alternativeProgram->slug,
                    'name' => $path->alternativeProgram->name,
                ])
                ->values()
                ->all();

        return new ProgramRecommendation(
            program: $program,
            matchScore: $matchScore,
            eligible: $eligibility->eligible,
            reasons: $reasons,
            missingRequirements: $missing,
            alternatives: $alternatives,
            eligibility: $eligibility,
        );
    }

    /**
     * Education compatibility comes straight from the eligibility evaluation:
     * share of passed education-related conditions. Programs with no recorded
     * conditions count as compatible only when the student stated a level.
     */
    private function educationScore(EligibilityResult $eligibility, StudentProfile $profile): ?float
    {
        if ($eligibility->educationTotal !== null && $eligibility->educationTotal > 0) {
            return $eligibility->educationPassed / $eligibility->educationTotal;
        }

        $statedLevel = $profile->educationLevelCode !== null || $profile->qualificationCode !== null;

        return $statedLevel ? 1.0 : null;
    }

    /**
     * Share of the program's taxonomy tags present in the student's answers.
     * Not evaluable unless both sides provided data.
     *
     * @param  array<int, string>  $profileCodes
     * @param  array<int, string>  $programCodes
     */
    private function taxonomyOverlap(array $profileCodes, array $programCodes): ?float
    {
        if ($profileCodes === [] || $programCodes === []) {
            return null;
        }

        $matched = count(array_intersect(
            array_map(strtolower(...), $profileCodes),
            array_map(strtolower(...), $programCodes),
        ));

        return $matched / count($programCodes);
    }

    private function locationScore(StudentProfile $profile, Program $program): ?float
    {
        $hasGeoInput = $profile->city !== null || $profile->region !== null || $profile->willingToRelocate;

        if (! $hasGeoInput) {
            return null;
        }

        if ($profile->willingToRelocate) {
            return 1.0;
        }

        $candidates = $program->campus !== null
            ? collect([$program->campus])
            : $program->institution->campuses;

        foreach ($candidates as $campus) {
            if ($campus->city === $profile->city) {
                return 1.0;
            }
        }

        foreach ($candidates as $campus) {
            if ($campus->region === $profile->region) {
                return 0.7;
            }
        }

        // No physical location nearby: online learning removes most of the barrier.
        if (in_array($program->study_mode, [StudyMode::Online, StudyMode::Hybrid], true)) {
            return 0.8;
        }

        return 0.0;
    }

    private function budgetScore(StudentProfile $profile, Program $program): ?float
    {
        if ($profile->budgetMax === null || $program->costs->isEmpty()) {
            return null;
        }

        if ($program->costs->contains(fn ($cost): bool => $cost->is_free)) {
            return 1.0;
        }

        $minAmount = $program->costs
            ->map(fn ($cost): float => (float) ($cost->amount_min ?? $cost->amount_max ?? 0))
            ->min();
        $maxAmount = $program->costs
            ->map(fn ($cost): float => (float) ($cost->amount_max ?? $cost->amount_min ?? 0))
            ->max();

        if ($maxAmount <= $profile->budgetMax) {
            return 1.0;
        }

        return $minAmount <= $profile->budgetMax ? 0.5 : 0.0;
    }

    /**
     * Deterministic explanation lists, in weight order.
     *
     * @param  array<string, array{weight: int, score: float|null}>  $scores
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function explain(StudentProfile $profile, Program $program, array $scores, EligibilityResult $eligibility): array
    {
        $reasons = [];
        $missing = [];

        if (($s = $scores['education']['score']) !== null) {
            if ($s === 1.0) {
                $reasons[] = 'Your education fits the entry requirements.';
            } elseif ($s > 0.0) {
                $reasons[] = 'Your education partially meets the entry requirements.';
            } elseif (! $eligibility->eligible) {
                $reasons[] = 'Your current education does not reach the entry requirements yet.';
            }
        }

        if (($s = $scores['interests']['score']) !== null && $s > 0.0) {
            $reasons[] = $s === 1.0
                ? 'Matches everything you said interests you.'
                : 'Matches some of your listed interests.';
        }

        if (($s = $scores['skills']['score']) !== null && $s > 0.0) {
            $reasons[] = $s === 1.0
                ? 'Builds exactly on the skills you already have.'
                : 'Builds on some of your existing skills.';
        }

        if (($s = $scores['location']['score']) !== null && $s > 0.0) {
            $cityMatch = collect([$program->campus])->merge($program->institution->campuses)
                ->contains(fn (?Campus $campus): bool => $campus !== null && $campus->city === $profile->city);
            if ($cityMatch) {
                $reasons[] = sprintf('Offered in your city (%s).', $profile->city);
            } elseif ($profile->willingToRelocate) {
                $reasons[] = 'You are open to relocating for this program.';
            } else {
                $reasons[] = 'Offered in your region.';
            }
        } elseif ($scores['location']['score'] === 0.0) {
            $missing[] = 'Not offered in your area and requires being on site.';
        }

        if (($s = $scores['budget']['score']) !== null) {
            if ($s === 1.0) {
                $reasons[] = $program->costs->contains(fn ($cost): bool => $cost->is_free)
                    ? 'Free of charge.'
                    : 'Fits your stated budget.';
            } elseif ($s > 0.0) {
                $reasons[] = 'Partially within your stated budget.';
            } else {
                $missing[] = 'Costs exceed your stated budget.';
            }
        }

        if (($s = $scores['career']['score']) !== null && $s > 0.0) {
            $reasons[] = 'Leads towards the career you are aiming for.';
        }

        if (! $eligibility->eligible) {
            $missing = [...$eligibility->failedRequirements, ...$missing];
        }

        return [$reasons, $missing];
    }
}
