<?php

namespace App\Enums;

/**
 * Institution categories. Trust hierarchy and display labels are defined here,
 * never hardcoded at call sites.
 */
enum InstitutionType: string
{
    case PublicUniversity = 'public_university';
    case PrivateUniversity = 'private_university';
    case PublicSchool = 'public_school';
    case PrivateSchool = 'private_school';
    case Ofppt = 'ofppt';
    case CodingSchool = 'coding_school';
    case AlternativeEducation = 'alternative_education';
    case VocationalTraining = 'vocational_training';
    case Ngo = 'ngo';
    case CompanyProgram = 'company_program';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PublicUniversity => 'Public university',
            self::PrivateUniversity => 'Private university',
            self::PublicSchool => 'Public school',
            self::PrivateSchool => 'Private school',
            self::Ofppt => 'OFPPT',
            self::CodingSchool => 'Coding school',
            self::AlternativeEducation => 'Alternative education',
            self::VocationalTraining => 'Vocational training',
            self::Ngo => 'NGO',
            self::CompanyProgram => 'Company program',
            self::Other => 'Other',
        };
    }
}
