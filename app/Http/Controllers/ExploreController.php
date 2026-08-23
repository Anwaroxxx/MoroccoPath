<?php

namespace App\Http\Controllers;

use App\Enums\ProgramStatus;
use App\Models\Institution;
use App\Models\Program;
use App\Services\Education\ProgramSearchService;
use App\Services\Education\RequirementDescriber;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public exploration (spec §21). Only published records are visible.
 */
class ExploreController extends Controller
{
    public function __construct(private readonly ProgramSearchService $search) {}

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
        abort_unless($program->status === ProgramStatus::Published, 404);

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

        return Inertia::render('programs/[id]', [
            'program' => [
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
                ]),
                'version' => (($version = $program->versions->first()) !== null) ? [
                    'academic_year' => $version->academic_year,
                    'status' => $version->status->value,
                    'admission_information' => $version->admission_information,
                    'application_start' => $version->application_start?->toDateString(),
                    'application_end' => $version->application_end?->toDateString(),
                ] : null,
                'requirements' => $program->eligibilityRules
                    ->filter(fn ($rule): bool => ! $rule->condition_type->isProcessCondition())
                    ->map(fn ($rule): string => RequirementDescriber::describe($rule))
                    ->values()
                    ->all(),
                'process_steps' => $program->eligibilityRules
                    ->filter(fn ($rule): bool => $rule->condition_type->isProcessCondition())
                    ->map(fn ($rule): string => RequirementDescriber::describe($rule))
                    ->values()
                    ->all(),
                'careers' => $program->careers->pluck('name')->all(),
                'alternatives' => $program->alternativePaths
                    ->filter(fn ($path): bool => $path->alternativeProgram?->status === ProgramStatus::Published)
                    ->map(fn ($path): array => ['slug' => $path->alternativeProgram->slug, 'name' => $path->alternativeProgram->name])
                    ->values()
                    ->all(),
            ],
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
