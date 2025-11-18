<?php

namespace Tests\Unit;

use App\Models\Calculation;
use PHPUnit\Framework\TestCase;

class CalculationAccessorTest extends TestCase
{
    public function test_clamped_interval_is_used_for_label_and_afa(): void
    {
        $calculation = new Calculation();
        $calculation->forceFill([
            'rnd_min' => 10,
            'rnd_max' => 20,
            'rnd_years' => 12,
        ]);

        $this->assertSame('rd. 15 – 25 Jahre', $calculation->rnd_interval_label);
        $this->assertSame(4.0, $calculation->afa_percent_from);
        $this->assertEqualsWithDelta(6.67, $calculation->afa_percent_to, 0.001);
    }

    public function test_interval_above_threshold_is_used_directly(): void
    {
        $calculation = new Calculation();
        $calculation->forceFill([
            'rnd_min' => 30,
            'rnd_max' => 40,
        ]);

        $this->assertSame('rd. 30 – 40 Jahre', $calculation->rnd_interval_label);
        $this->assertSame(2.5, $calculation->afa_percent_from);
        $this->assertEqualsWithDelta(3.33, $calculation->afa_percent_to, 0.001);
    }
}
