<?php

namespace App\Services\Education;

use App\Enums\ProgramStatus;
use App\Models\Institution;
use App\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Public search over verified/published records. Shared by Inertia pages
 * and the future mobile API (spec §30). Draft and archived programs are
 * never exposed here — publication is an explicit administrative decision.
 */
final class ProgramSearchService
{
    /**
     * @return Collection<int, Program>
     */
    public function search(Request $request, int $perPage = 12): Collection
    {
        return $this->programQuery($request)
            ->orderBy('name')
            ->limit($perPage)
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Program>
     */
    public function paginate(Request $request, int $perPage = 12): LengthAwarePaginator
    {
        return $this->programQuery($request)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Builder<Program>
     */
    private function programQuery(Request $request): Builder
    {
        return Program::query()
            ->where('status', ProgramStatus::Published->value)
            ->with(['institution:id,canonical_name,slug', 'campus:id,name,city', 'interests:id,code,name'])
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.str_replace('%', '\%', (string) $request->string('q')).'%';
                $query->where(fn (Builder $q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->when($request->filled('city'), fn (Builder $query) => $query
                ->whereHas('campus', fn (Builder $q) => $q->where('city', $request->string('city')))
                ->orWhereHas('institution.campuses', fn (Builder $q) => $q->where('city', $request->string('city'))))
            ->when($request->filled('mode'), fn (Builder $query) => $query->where(
                'study_mode',
                (string) $request->string('mode'),
            ))
            ->when($request->filled('interest'), fn (Builder $query) => $query->whereHas(
                'interests',
                fn (Builder $q) => $q->where('code', (string) $request->string('interest')),
            ));
    }

    /**
     * @return Collection<int, Institution>
     */
    public function institutions(Request $request, int $perPage = 12): Collection
    {
        return Institution::query()
            ->whereHas('programs', fn (Builder $q) => $q->where('status', ProgramStatus::Published->value))
            ->withCount(['programs' => fn (Builder $q) => $q->where('status', ProgramStatus::Published->value)])
            ->with('campuses:id,institution_id,name,city,region')
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.str_replace('%', '\%', (string) $request->string('q')).'%';
                $query->where(fn (Builder $q) => $q
                    ->where('canonical_name', 'like', $term)
                    ->orWhere('description', 'like', $term));
            })
            ->when($request->filled('city'), fn (Builder $query) => $query->whereHas(
                'campuses',
                fn (Builder $q) => $q->where('city', $request->string('city')),
            ))
            ->orderBy('canonical_name')
            ->limit($perPage)
            ->get();
    }
}
