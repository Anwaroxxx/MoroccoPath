<?php

namespace Database\Seeders;

use App\Models\BacBranch;
use App\Models\BacBranchAlias;
use Illuminate\Database\Seeder;

class BacBranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            // code, name, common source-data aliases (normalized per spec §7)
            ['SCIENCES_PHYSIQUES', 'Sciences Physiques (PC)', ['PC', 'Sciences Physiques et Chimiques']],
            ['SCIENCES_MATHEMATIQUES', 'Sciences Mathématiques', ['SM', 'Maths']],
            ['SVT', 'Sciences de la Vie et de la Terre', ['Sciences Expérimentales']],
            ['SCIENCES_ECONOMIQUES', 'Sciences Économiques', ['SE', 'Économie']],
            ['GESTION', 'Gestion Comptable', ['GC']],
            ['LETTRES', 'Lettres', ['Lettres Modernes', 'A4']],
            ['SCIENCES_HUMAINES', 'Sciences Humaines', ['SH']],
            ['TECHNOLOGIE', 'Sciences et Technologies', ['ST', 'STM', 'STE', 'STL']],
            ['ARTS', 'Arts Appliqués', ['AA']],
            ['PROFESSIONNEL', 'Baccalauréat Professionnel', ['BP', 'Bac Pro']],
            ['OTHER_BRANCH', 'Other branch', []],
        ];

        foreach ($branches as [$code, $name, $aliases]) {
            $branch = BacBranch::updateOrCreate(['code' => $code], ['name' => $name]);

            foreach ($aliases as $alias) {
                BacBranchAlias::updateOrCreate(
                    ['bac_branch_id' => $branch->id, 'alias' => $alias],
                );
            }
        }
    }
}
