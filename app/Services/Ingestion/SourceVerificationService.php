<?php

namespace App\Services\Ingestion;

use App\Enums\VerificationStatus;
use App\Models\SourceReference;
use Carbon\CarbonImmutable;

/**
 * Keeps provenance honest over time (spec §18): a VERIFIED citation ages
 * into EXPIRED once its verification date passes the staleness window.
 * NEEDS_REVIEW / CONFLICTING / UNKNOWN are never touched automatically —
 * those transitions require a human.
 */
final class SourceVerificationService
{
    public function __construct(public readonly int $stalenessDays = 180) {}

    /**
     * @return array{expired: int}
     */
    public function refresh(CarbonImmutable $now): array
    {
        $cutoff = $now->copy()->subDays($this->stalenessDays);

        $expired = SourceReference::query()
            ->where('verification_status', VerificationStatus::Verified->value)
            ->where(function ($query) use ($cutoff): void {
                $query
                    ->where('last_verified_at', '<', $cutoff)
                    ->orWhereNull('last_verified_at');
            })
            ->update(['verification_status' => VerificationStatus::Expired->value]);

        return ['expired' => $expired];
    }
}
