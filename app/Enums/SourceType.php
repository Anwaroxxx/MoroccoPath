<?php

namespace App\Enums;

/**
 * Source categories ordered by trust. trustLevel() returns the hierarchy rank:
 * 1 = most trusted (Moroccan government / ministry), 6 = least (other).
 * Eligibility must never rely solely on a source with a low trust level.
 */
enum SourceType: string
{
    case OfficialGovernment = 'official_government';
    case OfficialInstitution = 'official_institution';
    case OfficialDocument = 'official_document';
    case GovernmentOpenData = 'government_open_data';
    case RecognizedSecondarySource = 'recognized_secondary_source';
    case Other = 'other';

    public function trustLevel(): int
    {
        return match ($this) {
            self::OfficialGovernment => 1,
            self::OfficialInstitution => 2,
            self::OfficialDocument => 3,
            self::GovernmentOpenData => 4,
            self::RecognizedSecondarySource => 5,
            self::Other => 6,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OfficialGovernment => 'Official government / ministry',
            self::OfficialInstitution => 'Official institution',
            self::OfficialDocument => 'Official document',
            self::GovernmentOpenData => 'Government open data',
            self::RecognizedSecondarySource => 'Recognized secondary source',
            self::Other => 'Other',
        };
    }
}
