<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RenovationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'baeder_wc', 'label' => 'Bäder und WC-Anlagen', 'max_points' => 2],
            ['key' => 'innenausbau', 'label' => 'Innenausbau', 'max_points' => 2],
            ['key' => 'fenster_tueren', 'label' => 'Fenster und Außentüren', 'max_points' => 2],
            ['key' => 'heizung', 'label' => 'Heizung', 'max_points' => 2],
            ['key' => 'leitungen', 'label' => 'Leitungen', 'max_points' => 2],
            ['key' => 'dach_waermeschutz', 'label' => 'Dach / Wärmeschutz', 'max_points' => 4],
            ['key' => 'aussenwaende', 'label' => 'Außenwände / Dämmung', 'max_points' => 4],
        ];

        foreach ($rows as &$row) {
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table('renovation_categories')->upsert($rows, ['key'], ['label', 'max_points', 'updated_at']);
    }
}
