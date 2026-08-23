<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\Qualification;
use Illuminate\Database\Seeder;

class QualificationSeeder extends Seeder
{
    public function run(): void
    {
        $qualifications = [
            // code, name, equivalent academic level code
            ['QUALIFICATION', 'Qualification (vocational)', 'NIVEAU_BAC'],
            ['TECHNICIEN', 'Technicien', 'BAC'],
            ['TECHNICIEN_SPECIALISE', 'Technicien Spécialisé', 'BAC'],
            ['DUT', 'DUT', 'BAC_PLUS_2'],
            ['BTS', 'BTS', 'BAC_PLUS_2'],
            ['DEUG', 'DEUG', 'BAC_PLUS_2'],
            ['DEUST', 'DEUST', 'BAC_PLUS_2'],
            ['LICENCE', 'Licence', 'BAC_PLUS_3'],
            ['LICENCE_PRO', 'Licence Professionnelle', 'BAC_PLUS_3'],
            ['MASTER_DIPLOMA', 'Master (diploma)', 'BAC_PLUS_5'],
            ['ENGINEER', 'Ingénieur d\'État', 'BAC_PLUS_5'],
            ['OTHER_QUALIFICATION', 'Other qualification', null],
        ];

        foreach ($qualifications as [$code, $name, $equivalentCode]) {
            Qualification::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'equivalent_level_id' => $equivalentCode !== null
                        ? EducationLevel::query()->where('code', $equivalentCode)->value('id')
                        : null,
                ],
            );
        }
    }
}
