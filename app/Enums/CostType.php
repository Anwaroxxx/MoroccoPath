<?php

namespace App\Enums;

enum CostType: string
{
    case Registration = 'registration';
    case TuitionAnnual = 'tuition_annual';
    case TuitionMonthly = 'tuition_monthly';
    case Materials = 'materials';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration fees',
            self::TuitionAnnual => 'Tuition (per year)',
            self::TuitionMonthly => 'Tuition (per month)',
            self::Materials => 'Materials',
            self::Other => 'Other costs',
        };
    }
}
