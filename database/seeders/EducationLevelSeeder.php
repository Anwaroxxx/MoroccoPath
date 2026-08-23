<?php

namespace Database\Seeders;

use App\Enums\EducationLevelCategory;
use App\Models\EducationLevel;
use Illuminate\Database\Seeder;

class EducationLevelSeeder extends Seeder
{
    public function run(): void
    {
        // code, name, rank, bac_plus_years, category
        $levels = [
            ['NO_BAC', 'No Bac', 10, null, EducationLevelCategory::Academic],
            ['NIVEAU_BAC', 'Bac level (did not obtain the Bac)', 20, null, EducationLevelCategory::Academic],
            ['BAC', 'Baccalauréat', 30, 0, EducationLevelCategory::Academic],
            ['BAC_PLUS_1', 'Bac+1', 40, 1, EducationLevelCategory::Academic],
            ['BAC_PLUS_2', 'Bac+2', 50, 2, EducationLevelCategory::Academic],
            ['BAC_PLUS_3', 'Bac+3', 60, 3, EducationLevelCategory::Academic],
            ['BAC_PLUS_4', 'Bac+4', 70, 4, EducationLevelCategory::Academic],
            ['BAC_PLUS_5', 'Bac+5', 80, 5, EducationLevelCategory::Academic],
            ['MASTER', 'Master', 90, 5, EducationLevelCategory::Academic],
            ['DOCTORATE', 'Doctorate', 100, 8, EducationLevelCategory::Academic],
        ];

        foreach ($levels as [$code, $name, $rank, $bacPlusYears, $category]) {
            EducationLevel::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'rank' => $rank,
                    'bac_plus_years' => $bacPlusYears,
                    'category' => $category,
                ],
            );
        }
    }
}
