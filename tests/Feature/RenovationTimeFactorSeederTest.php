<?php

namespace Tests\Feature;

use Database\Seeders\RenovationCategorySeeder;
use Database\Seeders\RenovationTimeFactorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenovationTimeFactorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_time_factor_matrix_matches_expectation(): void
    {
        $this->seed(RenovationCategorySeeder::class);
        $this->seed(RenovationTimeFactorSeeder::class);

        $windows = ['nicht', 'weiss_nicht', 'bis_5', 'bis_10', 'bis_15', 'bis_20', 'ueber_20'];

        $expected = [
            'baeder_wc' => [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 0.5,
                'bis_15' => 0.0,
                'bis_20' => 0.0,
                'ueber_20' => 0.0,
            ],
            'innenausbau' => [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 1.0,
                'bis_20' => 0.5,
                'ueber_20' => 0.0,
            ],
            'fenster_tueren' => [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 0.5,
                'bis_20' => 0.0,
                'ueber_20' => 0.0,
            ],
            'heizung' => [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 0.5,
                'bis_20' => 0.0,
                'ueber_20' => 0.0,
            ],
            'leitungen' => [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 1.0,
                'bis_20' => 0.5,
                'ueber_20' => 0.0,
            ],
            'dach_waermeschutz' => [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.25,
                'ueber_20' => 0.0,
            ],
            'aussenwaende' => [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.25,
                'ueber_20' => 0.0,
            ],
        ];

        $actual = DB::table('renovation_time_factors')
            ->join('renovation_categories', 'renovation_categories.id', '=', 'renovation_time_factors.renovation_category_id')
            ->select('renovation_categories.key as category_key', 'renovation_time_factors.time_window_key', 'renovation_time_factors.factor')
            ->orderBy('renovation_categories.key')
            ->orderBy('renovation_time_factors.time_window_key')
            ->get()
            ->groupBy('category_key')
            ->map(function ($items) use ($windows) {
                $matrix = array_fill_keys($windows, 0.0);

                foreach ($items as $item) {
                    $matrix[$item->time_window_key] = (float) $item->factor;
                }

                return $matrix;
            })
            ->toArray();

        array_walk($expected, fn (&$values) => ksort($values));
        array_walk($actual, fn (&$values) => ksort($values));

        ksort($actual);
        ksort($expected);

        $this->assertSame($expected, $actual);
    }
}
