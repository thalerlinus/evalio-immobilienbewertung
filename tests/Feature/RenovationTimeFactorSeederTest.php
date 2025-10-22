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

    public function test_relevant_time_windows_have_positive_factors(): void
    {
        $this->seed(RenovationCategorySeeder::class);
        $this->seed(RenovationTimeFactorSeeder::class);

        $relevantWindows = ['bis_5', 'bis_10', 'bis_15', 'bis_20', 'ueber_20'];

        $invalidCount = DB::table('renovation_time_factors')
            ->whereIn('time_window_key', $relevantWindows)
            ->where('factor', '<=', 0)
            ->count();

        $this->assertSame(0, $invalidCount, 'Alle relevanten Zeitfenster müssen eine positive Gewichtung besitzen.');
    }

    public function test_non_relevant_windows_have_zero_factors(): void
    {
        $this->seed(RenovationCategorySeeder::class);
        $this->seed(RenovationTimeFactorSeeder::class);

        $nonRelevantWindows = ['nicht', 'weiss_nicht'];

        $countNonZero = DB::table('renovation_time_factors')
            ->whereIn('time_window_key', $nonRelevantWindows)
            ->where('factor', '!=', 0)
            ->count();

        $this->assertSame(0, $countNonZero, '"Nicht" und "Weiß nicht" dürfen keine Gewichtung erhalten.');
    }
}
