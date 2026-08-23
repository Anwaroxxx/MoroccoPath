<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrientationUpdateRequest;
use App\Models\BacBranch;
use App\Models\Career;
use App\Models\EducationLevel;
use App\Models\Interest;
use App\Models\Qualification;
use App\Models\Skill;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Guided onboarding questionnaire (spec §18) and its persistence.
 * Authenticated students only — the profile personalizes their results.
 */
class OrientationController extends Controller
{
    public function show(Request $request): Response
    {
        $profile = UserProfile::query()->where('user_id', $request->user()->id)->first();

        return Inertia::render('orientation', [
            'options' => [
                'education_levels' => EducationLevel::query()
                    ->orderBy('rank')
                    ->get(['id', 'code', 'name'])
                    ->map(fn ($level): array => ['id' => $level->id, 'name' => $level->name]),
                'qualifications' => Qualification::query()
                    ->orderBy('name')
                    ->get(['id', 'code', 'name'])
                    ->map(fn ($qualification): array => ['id' => $qualification->id, 'name' => $qualification->name]),
                'bac_branches' => BacBranch::query()
                    ->orderBy('name')
                    ->get(['code', 'name']),
                'interests' => Interest::query()->orderBy('name')->get(['code', 'name']),
                'skills' => Skill::query()->orderBy('name')->get(['code', 'name']),
                'careers' => Career::query()->orderBy('name')->get(['code', 'name']),
            ],
            'profile' => $profile !== null ? [
                'education_level_id' => $profile->education_level_id,
                'qualification_id' => $profile->qualification_id,
                'bac_branch_codes' => $profile->bac_branch_codes ?? [],
                'interest_codes' => $profile->interest_codes ?? [],
                'skill_codes' => $profile->skill_codes ?? [],
                'career_goal_codes' => $profile->career_goal_codes ?? [],
                'bac_grade' => $profile->bac_grade,
                'age' => $profile->age,
                'city' => $profile->city,
                'region' => $profile->region,
                'budget_max' => $profile->budget_max,
                'willing_to_relocate' => $profile->willing_to_relocate,
            ] : null,
        ]);
    }

    public function update(OrientationUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

        return redirect()
            ->route('results')
            ->with('success', 'Your profile has been saved.');
    }
}
