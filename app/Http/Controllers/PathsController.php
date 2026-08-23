<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Career graph exploration (spec §19, §19/§15): browse multi-step paths
 * towards careers, in both directions.
 */
class PathsController extends Controller
{
    public function index(Request $request): Response
    {
        $paths = CareerPath::query()
            ->with(['field:id,code,name', 'targetCareer:id,name', 'steps'])
            ->withCount('steps')
            ->orderBy('name')
            ->get()
            ->map(fn (CareerPath $path): array => [
                'id' => $path->id,
                'slug' => $path->slug,
                'name' => $path->name,
                'description' => $path->description,
                'field' => $path->field?->name,
                'target_career' => $path->targetCareer?->name,
                'steps_count' => $path->steps_count,
            ]);

        return Inertia::render('paths/index', [
            'paths' => $paths,
            'filters' => ['q' => (string) $request->string('q')],
        ]);
    }

    public function show(CareerPath $careerPath): Response
    {
        $careerPath->load([
            'field:id,code,name',
            'targetCareer:id,name',
            'steps' => fn ($query) => $query
                ->with(['educationLevel:id,code,name', 'program:id,slug,name,status'])
                ->orderBy('position'),
        ]);

        // Build nested trees from the flat step list (multiple roots allowed).
        $byParent = $careerPath->steps->groupBy(fn ($step) => $step->parent_step_id ?? 0);

        $buildTree = function (int $parentId = 0) use (&$buildTree, $byParent): array {
            return $byParent
                ->get($parentId, collect())
                ->map(fn ($step): array => [
                    'id' => $step->id,
                    'title' => $step->title,
                    'description' => $step->description,
                    'education_level' => $step->educationLevel?->name,
                    'program' => (($program = $step->program) !== null) ? [
                        'slug' => $program->slug,
                        'name' => $program->name,
                        'published' => $program->status->value === 'published',
                    ] : null,
                    'children' => $buildTree($step->id),
                ])
                ->values()
                ->all();
        };

        return Inertia::render('paths/[id]', [
            'path' => [
                'id' => $careerPath->id,
                'slug' => $careerPath->slug,
                'name' => $careerPath->name,
                'description' => $careerPath->description,
                'field' => $careerPath->field?->name,
                'target_career' => $careerPath->targetCareer?->name,
                'steps' => $buildTree(),
            ],
        ]);
    }
}
