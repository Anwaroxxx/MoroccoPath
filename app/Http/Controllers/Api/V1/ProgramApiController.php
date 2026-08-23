<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\Education\ProgramDetailService;
use App\Services\Education\ProgramSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public read-only program endpoints (spec §19).
 */
class ProgramApiController extends Controller
{
    public function __construct(
        private readonly ProgramSearchService $search,
        private readonly ProgramDetailService $detail,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $programs = $this->search->paginate($request, perPage: (int) min(50, max(1, (int) $request->query('per_page', '15'))));

        return response()->json([
            'data' => collect($programs->items())->map(fn (Program $program): array => [
                'id' => $program->id,
                'slug' => $program->slug,
                'name' => $program->name,
                'study_mode' => $program->study_mode->value,
                'duration_label' => $program->duration_label,
                'institution' => $program->institution->canonical_name,
                'city' => $program->campus !== null
                    ? $program->campus->city
                    : $program->institution->campuses->first()?->city,
                'interests' => $program->interests->pluck('code')->all(),
            ]),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'total' => $programs->total(),
            ],
        ]);
    }

    public function show(Program $program): JsonResponse
    {
        return response()->json([
            'data' => $this->detail->detail($program),
        ]);
    }
}
