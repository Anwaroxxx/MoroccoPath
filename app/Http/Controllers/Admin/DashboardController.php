<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Program;
use App\Models\Source;
use App\Models\SourceReference;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin dashboard: platform health plus the data review queue.
 * Route group is protected by auth + verified + can:access-admin.
 */
class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $queue = collect(VerificationStatus::cases())
            ->map(fn (VerificationStatus $status): array => [
                'status' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => SourceReference::query()->where('verification_status', $status->value)->count(),
            ])
            ->all();

        return Inertia::render('admin/index', [
            'stats' => [
                'institutions' => Institution::query()->count(),
                'programs' => Program::query()->count(),
                'sources' => Source::query()->count(),
                'published_programs' => Program::query()->where('status', 'published')->count(),
            ],
            'queue' => $queue,
        ]);
    }
}
