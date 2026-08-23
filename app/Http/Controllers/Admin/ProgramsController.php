<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\SourceReference;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramsController extends Controller
{
    public function index(Request $request): Response
    {
        $statusFilter = $request->enum('verification', VerificationStatus::class);

        $programs = Program::query()
            ->with(['institution:id,canonical_name,slug', 'campus:id,name,city'])
            ->with(['sourceReferences' => fn ($query) => $query->with('source')->latest('id')->limit(1)])
            ->with(['versions' => fn ($query) => $query->orderByDesc('academic_year')->limit(1)])
            ->when($statusFilter !== null, fn ($query) => $query->whereHas(
                'sourceReferences',
                fn ($q) => $q->where('verification_status', $statusFilter->value),
            ))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace('%', '\%', (string) $request->string('q')).'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (Program $program): array => [
                'id' => $program->id,
                'name' => $program->name,
                'slug' => $program->slug,
                'status' => $program->status->value,
                'study_mode' => $program->study_mode->value,
                'duration_label' => $program->duration_label,
                'institution' => $program->institution->canonical_name,
                'city' => $program->campus?->city,
                'academic_year' => $program->versions->first()?->academic_year,
                'version_status' => $program->versions->first()?->status->value,
                'last_reference' => $this->lastReference($program),
            ]);

        return Inertia::render('admin/programs/index', [
            'programs' => $programs,
            'filters' => [
                'q' => (string) $request->string('q'),
                'verification' => $statusFilter?->value,
            ],
            'statuses' => collect(VerificationStatus::cases())
                ->map(fn (VerificationStatus $s): array => ['value' => $s->value, 'label' => $s->label()])
                ->all(),
        ]);
    }

    /**
     * @return array{source: string, status: string, academic_year: string|null, last_verified_at: string|null}|null
     */
    private function lastReference(Program $program): ?array
    {
        /** @var SourceReference|null $reference */
        $reference = $program->sourceReferences->first();

        if ($reference === null) {
            return null;
        }

        return [
            'source' => $reference->source->name,
            'status' => $reference->verification_status->value,
            'academic_year' => $reference->academic_year,
            'last_verified_at' => $reference->last_verified_at?->toDateString(),
        ];
    }
}
