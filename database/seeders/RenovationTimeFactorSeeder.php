<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RenovationTimeFactorSeeder extends Seeder
{
    private const WINDOWS = ['nicht', 'bis_5', 'bis_10', 'bis_15', 'bis_20', 'ueber_20', 'weiss_nicht'];

    public function run(): void
    {
        $map = [
            'baeder_wc'         => [0, 1, 0.5, 0, 0, 0, 0],
            'innenausbau'       => [0, 1, 1, 0.5, 0, 0, 0],
            'fenster_tueren'    => [0, 1, 1, 0.5, 0, 0, 0],
            'heizung'           => [0, 1, 1, 1, 0, 0, 0],
            'leitungen'         => [0, 1, 1, 0, 0, 0, 0],
            'dach_waermeschutz' => [0, 1, 0.75, 0.5, 0.25, 0, 0],
            'aussenwaende'      => [0, 1, 0.75, 0.5, 0.25, 0, 0],
        ];

        $catIds = DB::table('renovation_categories')->pluck('id', 'key');
        $rows = [];
        foreach ($map as $key => $factors) {
            $id = $catIds[$key];
            foreach (self::WINDOWS as $i => $win) {
                $rows[] = [
                    'renovation_category_id' => $id,
                    'time_window_key'        => $win,
                    'factor'                 => $factors[$i],
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }
        }

        DB::table('renovation_time_factors')->upsert($rows, ['renovation_category_id', 'time_window_key'], ['factor', 'updated_at']);
    }
}
