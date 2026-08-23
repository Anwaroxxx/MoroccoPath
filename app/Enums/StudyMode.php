<?php

namespace App\Enums;

enum StudyMode: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Evening = 'evening';
    case Online = 'online';
    case Hybrid = 'hybrid';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full time',
            self::PartTime => 'Part time',
            self::Evening => 'Evening classes',
            self::Online => 'Online',
            self::Hybrid => 'Hybrid',
            self::Other => 'Other',
        };
    }
}
