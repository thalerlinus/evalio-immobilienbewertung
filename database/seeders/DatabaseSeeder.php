<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Evalio data
        $this->call([
            AdminUserSeeder::class,
            PropertyTypeSeeder::class,
            GaPricingSeeder::class,
            RenovationCategorySeeder::class,
            RenovationExtentWeightSeeder::class,
            RenovationTimeFactorSeeder::class,
            ScoreFormulaSeeder::class,
            ContactSettingSeeder::class,
        ]);

    }
}
