<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RenovationTimeFactorSeeder extends Seeder
{
    private const WINDOWS = ['nicht', 'weiss_nicht', 'bis_5', 'bis_10', 'bis_15', 'bis_20', 'ueber_20'];

    public function run(): void
    {
        $zeroForAll = array_fill_keys(self::WINDOWS, 0.0);

        $categoryFactors = [
            'baeder_wc' => array_replace($zeroForAll, [
                'bis_5' => 1.0,
                'bis_10' => 0.5,
            ]),
            'innenausbau' => array_replace($zeroForAll, [
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 1.0,
                'bis_20' => 0.5,
            ]),
            'fenster_tueren' => array_replace($zeroForAll, [
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 0.5,
            ]),
            'heizung' => array_replace($zeroForAll, [
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 0.5,
            ]),
            'leitungen' => array_replace($zeroForAll, [
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 1.0,
                'bis_20' => 0.5,
            ]),
            'dach_waermeschutz' => array_replace($zeroForAll, [
                'bis_5' => 1.0,
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.25,
            ]),
            'aussenwaende' => array_replace($zeroForAll, [
                'bis_5' => 1.0,
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.25,
            ]),
        ];

        $catIds = DB::table('renovation_categories')->pluck('id', 'key');
        $rows = [];
        foreach ($categoryFactors as $key => $factorsByWindow) {
            $id = $catIds[$key];
            foreach (self::WINDOWS as $win) {
                $rows[] = [
                    'renovation_category_id' => $id,
                    'time_window_key'        => $win,
                    'factor'                 => $factorsByWindow[$win] ?? 0.0,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }
        }

        DB::table('renovation_time_factors')->upsert($rows, ['renovation_category_id', 'time_window_key'], ['factor', 'updated_at']);
    }
}
