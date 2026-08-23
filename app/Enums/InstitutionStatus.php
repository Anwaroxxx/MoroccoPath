<?php

namespace App\Enums;

enum InstitutionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Unknown => 'Unknown',
        };
    }
}
