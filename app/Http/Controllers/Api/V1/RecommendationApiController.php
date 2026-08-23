<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\UserProfile;
use App\Services\Education\RecommendationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Personalized recommendations over the API. Requires a Sanctum token:
 * results are account-bound, so anonymous callers get 401.
 */
class RecommendationApiController extends Controller
{
    public function __construct(private readonly RecommendationEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $profile = UserProfile::query()->where('user_id', $request->user()->id)->first();

        if ($profile === null) {
            return response()->json([
                'message' => 'No profile yet. Save one via PATCH /v1/me/profile first.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $recommendations = $this->engine
            ->recommend($profile->toStudentProfile(), Program::query()->where('status', 'published')->get())
            ->values()
            ->map(fn ($recommendation): array => $recommendation->toArray());

        return response()->json(['data' => $recommendations]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Mobile onboarding parity with the web questionnaire.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $codes = fn (string $table): Exists => Rule::exists($table, 'code');

        $validated = $request->validate([
            'education_level_id' => ['nullable', Rule::exists('education_levels', 'id')],
            'qualification_id' => ['nullable', Rule::exists('qualifications', 'id')],
            'bac_branch_codes' => ['nullable', 'array', 'max:5'],
            'bac_branch_codes.*' => [$codes('bac_branches')],
            'bac_grade' => ['nullable', 'numeric', 'between:0,20'],
            'age' => ['nullable', 'integer', 'between:10,80'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'willing_to_relocate' => ['nullable', 'boolean'],
            'interest_codes' => ['nullable', 'array', 'max:12'],
            'interest_codes.*' => [$codes('interests')],
            'skill_codes' => ['nullable', 'array', 'max:15'],
            'skill_codes.*' => [$codes('skills')],
            'career_goal_codes' => ['nullable', 'array', 'max:5'],
            'career_goal_codes.*' => [$codes('careers')],
        ]);

        UserProfile::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'user_id' => $request->user()->id,
                ...$validated,
                'bac_branch_codes' => $validated['bac_branch_codes'] ?? [],
                'interest_codes' => $validated['interest_codes'] ?? [],
                'skill_codes' => $validated['skill_codes'] ?? [],
                'career_goal_codes' => $validated['career_goal_codes'] ?? [],
            ],
        );

        $profile = UserProfile::query()->where('user_id', $request->user()->id)->first();

        return response()->json([
            'data' => array_merge($profile->toArray(), [
                'education_level_code' => $profile->educationLevel?->code,
                'qualification_code' => $profile->qualification?->code,
            ]),
        ]);
    }

    public function showProfile(Request $request): JsonResponse
    {
        $profile = UserProfile::query()->where('user_id', $request->user()->id)->first();

        if ($profile === null) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => array_merge($profile->toArray(), [
            'education_level_code' => $profile->educationLevel?->code,
            'qualification_code' => $profile->qualification?->code,
        ])]);
    }
}
