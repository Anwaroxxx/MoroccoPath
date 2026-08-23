<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\SourceReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Review queue: every record whose provenance needs human verification.
 * The PATCH action is the only way verification status changes — and it is
 * restricted to administrators by the route group middleware.
 */
class ReviewQueueController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->enum('status', VerificationStatus::class) ?? VerificationStatus::NeedsReview;

        $references = SourceReference::query()
            ->where('verification_status', $status->value)
            ->with(['source', 'referencable'])
            ->latest('id')
            ->paginate(20)
            ->through(fn (SourceReference $reference): array => [
                'id' => $reference->id,
                'record_type' => class_basename($reference->referencable_type),
                'record_name' => $this->referencableName($reference),
                'source_name' => $reference->source->name,
                'source_trust' => $reference->source->trust_level,
                'source_url' => $reference->source_url,
                'academic_year' => $reference->academic_year,
                'last_verified_at' => $reference->last_verified_at?->toDateString(),
                'verification_status' => $reference->verification_status->value,
            ]);

        return Inertia::render('admin/review-queue', [
            'status' => $status->value,
            'tabs' => collect(VerificationStatus::cases())
                ->map(fn (VerificationStatus $s): array => ['value' => $s->value, 'label' => $s->label()])
                ->all(),
            'references' => $references,
        ]);
    }

    public function update(Request $request, SourceReference $source_reference): RedirectResponse
    {
        $validated = $request->validate([
            'verification_status' => ['required', Rule::enum(VerificationStatus::class)],
        ]);

        $source_reference->update([
            'verification_status' => $validated['verification_status'],
            // A status change by an administrator counts as a human check.
            'last_verified_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Verification status updated.');
    }

    private function referencableName(SourceReference $reference): string
    {
        $record = $reference->referencable;

        if ($record === null) {
            return '(deleted record)';
        }

        return match ($reference->referencable_type) {
            Program::class => (string) ($record->name ?? ''),
            default => (string) ($record->canonical_name ?? $record->name ?? ''),
        };
    }
}
