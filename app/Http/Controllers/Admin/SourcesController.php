<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SourcesController extends Controller
{
    public function index(Request $request): Response
    {
        $sources = Source::query()
            ->withCount('references')
            ->orderBy('trust_level')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (Source $source): array => [
                'id' => $source->id,
                'name' => $source->name,
                'slug' => $source->slug,
                'type' => $source->typeEnum()->value,
                'type_label' => $source->typeEnum()->label(),
                'trust_level' => $source->trust_level,
                'website' => $source->website,
                'references_count' => $source->references_count,
            ]);

        return Inertia::render('admin/sources/index', [
            'sources' => $sources,
            'filters' => ['q' => (string) $request->string('q')],
        ]);
    }
}
