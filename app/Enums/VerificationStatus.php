<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Verified = 'verified';
    case NeedsReview = 'needs_review';
    case Expired = 'expired';
    /** Two official sources disagree — must be surfaced to admins, never silently resolved. */
    case Conflicting = 'conflicting';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::NeedsReview => 'Needs review',
            self::Expired => 'Expired',
            self::Conflicting => 'Conflicting',
            self::Unknown => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Verified => 'green',
            self::NeedsReview => 'amber',
            self::Expired => 'gray',
            self::Conflicting => 'red',
            self::Unknown => 'gray',
        };
    }
}
