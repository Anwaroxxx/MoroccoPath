<?php

namespace App\Enums;

enum AdmissionType: string
{
    case Dossier = 'dossier';
    case Concours = 'concours';
    case EntranceExam = 'entrance_exam';
    case Interview = 'interview';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Dossier => 'Application file',
            self::Concours => 'National competition',
            self::EntranceExam => 'Entrance exam',
            self::Interview => 'Interview',
            self::Other => 'Other',
        };
    }
}
