<?php

namespace App\Enums;

enum ProgramVersionStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::Active => 'Active',
            self::Expired => 'Expired',
        };
    }
}
