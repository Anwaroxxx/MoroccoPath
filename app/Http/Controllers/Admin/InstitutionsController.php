<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SourceReference;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionsController extends Controller
{
    public function index(Request $request): Response
    {
        $institutions = Institution::query()
            ->withCount(['campuses', 'programs'])
            ->with(['sourceReferences' => fn ($query) => $query->with('source')->latest('id')->limit(1)])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace('%', '\%', (string) $request->string('q')).'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('canonical_name', 'like', $term)
                    ->orWhere('slug', 'like', $term));
            })
            ->orderBy('canonical_name')
            ->paginate(20)
            ->through(fn (Institution $institution): array => [
                'id' => $institution->id,
                'name' => $institution->canonical_name,
                'slug' => $institution->slug,
                'type' => $institution->type->value,
                'status' => $institution->status->value,
                'campuses_count' => $institution->campuses_count,
                'programs_count' => $institution->programs_count,
                'external_identifier' => $institution->external_identifier,
                'last_reference' => $this->lastReference($institution),
            ]);

        return Inertia::render('admin/institutions/index', [
            'institutions' => $institutions,
            'filters' => ['q' => (string) $request->string('q')],
        ]);
    }

    /**
     * @return array{source: string, status: string, academic_year: string|null, last_verified_at: string|null}|null
     */
    private function lastReference(Institution $institution): ?array
    {
        /** @var SourceReference|null $reference */
        $reference = $institution->sourceReferences->first();

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
