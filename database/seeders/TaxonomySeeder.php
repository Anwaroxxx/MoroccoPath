<?php

namespace Database\Seeders;

use App\Models\Field;
use App\Models\Interest;
use Illuminate\Database\Seeder;

/**
 * Seeds the interest taxonomy (spec §18) and the academic field taxonomy
 * sharing the same canonical codes so they can be cross-referenced later.
 */
class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['TECHNOLOGY', 'Technology'],
            ['BUSINESS', 'Business'],
            ['HEALTHCARE', 'Healthcare'],
            ['ENGINEERING', 'Engineering'],
            ['MECHANICS', 'Mechanics'],
            ['CONSTRUCTION', 'Construction'],
            ['DESIGN', 'Design'],
            ['AGRICULTURE', 'Agriculture'],
            ['TOURISM', 'Tourism'],
            ['FINANCE', 'Finance'],
            ['EDUCATION', 'Education'],
            ['COMMUNICATION', 'Communication'],
        ];

        foreach ($entries as [$code, $name]) {
            Field::updateOrCreate(['code' => $code], ['name' => $name]);
            Interest::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
