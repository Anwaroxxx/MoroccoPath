<?php

namespace App\Enums;

enum EducationLevelCategory: string
{
    case Academic = 'academic';
    case Professional = 'professional';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Academic',
            self::Professional => 'Professional',
        };
    }
}
