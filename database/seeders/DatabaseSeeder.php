<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Role-guarded admin area relies on this account (change the password
        // immediately in any shared environment).
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->call([
            EducationLevelSeeder::class,
            QualificationSeeder::class,
            BacBranchSeeder::class,
            TaxonomySeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
