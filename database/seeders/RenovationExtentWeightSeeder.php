<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RenovationExtentWeightSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['extent_percent' => 0, 'weight' => 0.0],
            ['extent_percent' => 20, 'weight' => 0.2],
            ['extent_percent' => 40, 'weight' => 0.4],
            ['extent_percent' => 60, 'weight' => 0.6],
            ['extent_percent' => 80, 'weight' => 0.8],
            ['extent_percent' => 100, 'weight' => 1.0],
        ];

        foreach ($rows as &$row) {
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table('renovation_extent_weights')->upsert($rows, ['extent_percent'], ['weight', 'updated_at']);
    }
}
