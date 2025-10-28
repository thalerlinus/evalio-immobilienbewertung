<?php

namespace Tests\Unit;

use App\Models\RenovationCategory;
use App\Services\RndCalculatorService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RenovationScoreTest extends TestCase
{
    public function test_score_calculation_respects_customer_matrix(): void
    {
        $service = new RndCalculatorService();

        $categories = new Collection([
            $this->makeCategory('baeder_wc', 2, [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 0.5,
                'bis_15' => 0.0,
                'bis_20' => 0.0,
                'ueber_20' => 0.0,
            ]),
            $this->makeCategory('innenausbau', 2, [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 1.0,
                'bis_20' => 0.5,
                'ueber_20' => 0.0,
            ]),
            $this->makeCategory('fenster_tueren', 2, [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 0.5,
                'bis_20' => 0.0,
                'ueber_20' => 0.0,
            ]),
            $this->makeCategory('heizung', 2, [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 0.5,
                'bis_20' => 0.0,
                'ueber_20' => 0.0,
            ]),
            $this->makeCategory('leitungen', 2, [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 1.0,
                'bis_15' => 1.0,
                'bis_20' => 0.5,
                'ueber_20' => 0.0,
            ]),
            $this->makeCategory('dach_waermeschutz', 4, [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.25,
                'ueber_20' => 0.0,
            ]),
            $this->makeCategory('aussenwaende', 4, [
                'nicht' => 0.0,
                'weiss_nicht' => 0.0,
                'bis_5' => 1.0,
                'bis_10' => 0.75,
                'bis_15' => 0.5,
                'bis_20' => 0.25,
                'ueber_20' => 0.0,
            ]),
        ]);

        $categories = $categories->keyBy(fn (RenovationCategory $category) => $category->key);

        $extentWeights = new Collection([
            0 => 0.0,
            20 => 0.2,
            40 => 0.4,
            60 => 0.6,
            80 => 0.8,
            100 => 1.0,
        ]);

        $renovationsInput = new Collection([
            ['category_key' => 'baeder_wc', 'time_window_key' => 'bis_10', 'extent_percent' => 100],
            ['category_key' => 'innenausbau', 'time_window_key' => 'bis_15', 'extent_percent' => 80],
            ['category_key' => 'fenster_tueren', 'time_window_key' => 'bis_15', 'extent_percent' => 60],
            ['category_key' => 'heizung', 'time_window_key' => 'bis_20', 'extent_percent' => 40],
            ['category_key' => 'leitungen', 'time_window_key' => 'bis_15', 'extent_percent' => 60],
            ['category_key' => 'dach_waermeschutz', 'time_window_key' => 'bis_10', 'extent_percent' => 100],
            ['category_key' => 'aussenwaende', 'time_window_key' => 'bis_20', 'extent_percent' => 80],
        ]);

        $renovationsInput = $renovationsInput->keyBy('category_key');

        [$scoreRounded, $details, $scoreRaw] = $this->invokeCalculateScore(
            $service,
            $categories,
            $extentWeights,
            $renovationsInput
        );

        $this->assertSame(8.0, $scoreRounded);
        $this->assertEqualsWithDelta(8.2, $scoreRaw, 0.0001);
        $this->assertArrayHasKey('heizung', $details);
        $this->assertSame(0.0, $details['heizung']['points']);
        $this->assertSame(0.25, $details['aussenwaende']['time_factor']);
    }

    private function makeCategory(string $key, float $maxPoints, array $factors): RenovationCategory
    {
        $category = new RenovationCategory();
        $category->forceFill([
            'key' => $key,
            'label' => $key,
            'max_points' => $maxPoints,
        ]);

        $timeFactors = new Collection();
        foreach ($factors as $window => $factor) {
            $timeFactors->push((object) [
                'time_window_key' => $window,
                'factor' => $factor,
            ]);
        }

        $category->setRelation('timeFactors', new EloquentCollection($timeFactors->all()));

        return $category;
    }

    private function invokeCalculateScore(
        RndCalculatorService $service,
        Collection $categories,
        Collection $extentWeights,
        Collection $renovationsInput
    ): array {
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        return $method->invoke($service, $categories, $extentWeights, $renovationsInput);
    }
}
