<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CareerPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Career-graph endpoints (spec §19): paths with nested step trees.
 */
class CareerPathApiController extends Controller
{
    public function index(): JsonResponse
    {
        $paths = CareerPath::query()
            ->withCount('steps')
            ->with(['field:id,code,name', 'targetCareer:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (CareerPath $path): array => [
                'slug' => $path->slug,
                'name' => $path->name,
                'description' => $path->description,
                'field' => $path->field?->name,
                'target_career' => $path->targetCareer?->name,
                'steps_count' => $path->steps_count,
            ]);

        return response()->json(['data' => $paths]);
    }

    public function show(CareerPath $careerPath): JsonResponse
    {
        $careerPath->load([
            'field:id,code,name',
            'targetCareer:id,name',
            'steps' => fn ($query) => $query
                ->with(['educationLevel:id,code,name', 'program:id,slug,name,status'])
                ->orderBy('position'),
        ]);

        $byParent = $careerPath->steps->groupBy(fn ($step) => $step->parent_step_id ?? 0);

        $buildTree = function (int $parentId = 0) use (&$buildTree, $byParent): array {
            return $byParent
                ->get($parentId, collect())
                ->map(fn ($step): array => [
                    'id' => $step->id,
                    'title' => $step->title,
                    'description' => $step->description,
                    'education_level' => $step->educationLevel?->name,
                    'program_slug' => ($step->program !== null && Str::is($step->program->status->value, 'published'))
                        ? $step->program->slug
                        : null,
                    'children' => $buildTree($step->id),
                ])
                ->values()
                ->all();
        };

        return response()->json([
            'data' => [
                'slug' => $careerPath->slug,
                'name' => $careerPath->name,
                'description' => $careerPath->description,
                'target_career' => $careerPath->targetCareer?->name,
                'entry_points' => $buildTree(),
            ],
        ]);
    }
}
