<?php

namespace App\Http\Controllers;

use App\Enums\ProgramStatus;
use App\Models\Institution;
use App\Models\Program;
use App\Services\Education\ProgramDetailService;
use App\Services\Education\ProgramSearchService;
use App\Services\Education\RequirementDescriber;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public exploration (spec §21). Only published records are visible.
 */
class ExploreController extends Controller
{
    public function __construct(
        private readonly ProgramSearchService $search,
        private readonly ProgramDetailService $detailService,
    ) {}

    public function programs(Request $request): Response
    {
        return Inertia::render('programs/index', [
            'programs' => $this->search->paginate($request),
            'filters' => [
                'q' => (string) $request->string('q'),
                'city' => (string) $request->string('city'),
                'mode' => (string) $request->string('mode'),
                'interest' => (string) $request->string('interest'),
            ],
        ]);
    }

    public function programShow(Request $request, Program $program): Response
    {
        try {
            $payload = $this->detailService->detail($program);
        } catch (HttpResponseException) {
            abort(404);
        }

        return Inertia::render('programs/[id]', [
            'program' => $payload,
        ]);
    }

    public function compare(Request $request): Response
    {
        $slugs = collect(explode(',', (string) $request->query('programs', '')))
            ->map(fn ($slug) => trim((string) $slug))
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        $programs = Program::query()
            ->where('status', ProgramStatus::Published->value)
            ->whereIn('slug', $slugs)
            ->with([
                'institution:id,canonical_name',
                'campus:id,name,city',
                'costs',
                'careers:id,name',
                'eligibilityRules' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with(['educationLevels', 'qualifications', 'bacBranches'])
                    ->orderBy('sort_order'),
            ])
            ->get();

        $rows = $programs->map(fn (Program $program): array => [
            'name' => $program->name,
            'slug' => $program->slug,
            'institution' => $program->institution->canonical_name,
            'city' => $program->campus !== null
                ? $program->campus->city
                : $program->institution->campuses()->value('city'),
            'study_mode' => $program->study_mode->label(),
            'duration_label' => $program->duration_label,
            'is_free' => $program->costs->contains(fn ($cost): bool => $cost->is_free),
            'requirements' => $program->eligibilityRules
                ->filter(fn ($rule): bool => ! $rule->condition_type->isProcessCondition())
                ->map(fn ($rule): string => RequirementDescriber::describe($rule))
                ->values()
                ->all(),
            'careers' => $program->careers->pluck('name')->all(),
        ]);

        return Inertia::render('compare', [
            'selected' => $slugs->all(),
            'rows' => $rows,
            'catalog' => $this->search->search($request)->map(
                fn (Program $program): array => ['slug' => $program->slug, 'name' => $program->name],
            )->all(),
        ]);
    }

    public function institutions(Request $request): Response
    {
        return Inertia::render('institutions/index', [
            'institutions' => $this->search->institutions($request),
            'filters' => [
                'q' => (string) $request->string('q'),
                'city' => (string) $request->string('city'),
            ],
        ]);
    }

    public function institutionShow(Institution $institution): Response
    {
        if ($institution->programs()->where('status', ProgramStatus::Published->value)->doesntExist()) {
            abort(404);
        }

        $institution->load(['campuses']);

        return Inertia::render('institutions/[id]', [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->canonical_name,
                'slug' => $institution->slug,
                'description' => $institution->description,
                'website' => $institution->website,
                'campuses' => $institution->campuses
                    ->map(fn ($campus): array => [
                        'name' => $campus->name,
                        'city' => $campus->city,
                        'region' => $campus->region,
                        'address' => $campus->address,
                    ])
                    ->all(),
                'programs' => $institution->programs()
                    ->where('status', ProgramStatus::Published->value)
                    ->get(['id', 'slug', 'name', 'duration_label', 'study_mode'])
                    ->map(fn (Program $program): array => [
                        'slug' => $program->slug,
                        'name' => $program->name,
                        'duration_label' => $program->duration_label,
                        'study_mode' => $program->study_mode->value,
                    ])
                    ->all(),
            ],
        ]);
    }
}
