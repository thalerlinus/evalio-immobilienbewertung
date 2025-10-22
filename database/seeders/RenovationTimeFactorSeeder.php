<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RenovationTimeFactorSeeder extends Seeder
{
    private const WINDOWS = ['nicht', 'bis_5', 'bis_10', 'bis_15', 'bis_20', 'ueber_20', 'weiss_nicht'];

    public function run(): void
    {
        $baseFactors = [
            'nicht' => 0.0,
            'bis_5' => 1.0,
            'bis_10' => 0.8,
            'bis_15' => 0.6,
            'bis_20' => 0.4,
            'ueber_20' => 0.2,
            'weiss_nicht' => 0.0,
        ];

        $categoryFactors = [
            'baeder_wc' => $baseFactors,
            'innenausbau' => $baseFactors,
            'fenster_tueren' => $baseFactors,
            'heizung' => $baseFactors,
            'leitungen' => $baseFactors,
            'dach_waermeschutz' => array_merge($baseFactors, [
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.3,
                'ueber_20' => 0.15,
            ]),
            'aussenwaende' => array_merge($baseFactors, [
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.3,
                'ueber_20' => 0.15,
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
