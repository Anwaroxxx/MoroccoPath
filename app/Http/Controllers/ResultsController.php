<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\UserProfile;
use App\Services\Education\RecommendationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Personalized results (spec §14–16): ranked programs with WHY / WHY NOT,
 * missing requirements and alternatives. Eligibility stays authoritative;
 * the recommendation engine only ranks and explains.
 */
class ResultsController extends Controller
{
    public function __construct(private readonly RecommendationEngine $engine) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $profile = UserProfile::query()->where('user_id', $request->user()->id)->first();

        if ($profile === null) {
            return redirect()->route('orientation');
        }

        $recommendations = $this->engine
            ->recommend($profile->toStudentProfile(), Program::query()->where('status', 'published')->get())
            ->values()
            ->map(fn ($recommendation): array => $recommendation->toArray());

        return Inertia::render('results', [
            'recommendations' => $recommendations,
        ]);
    }
}
