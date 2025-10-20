<?php

namespace Tests\Feature;

use App\Models\Calculation;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RndCalculatorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_it_calculates_rnd_and_persists_calculation(): void
    {
        $response = $this->postJson('/api/rnd/calculate', [
            'property_type_key' => 'zweifamilienhaus',
            'baujahr' => 1980,
            'anschaffungsjahr' => 2020,
            'steuerjahr' => 2025,
            'bauweise' => 'massiv',
            'eigennutzung' => false,
            'renovations' => [
                ['category_key' => 'baeder_wc', 'time_window_key' => 'bis_5', 'extent_percent' => 80],
                ['category_key' => 'dach_waermeschutz', 'time_window_key' => 'bis_10', 'extent_percent' => 100],
            ],
            'contact' => [
                'name' => 'Eva Beispiel',
                'email' => 'eva@example.de',
                'phone' => '+49 30 1234567',
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'public_ref',
                    'property_type' => ['key', 'label'],
                    'score',
                    'score_details',
                    'rnd_years',
                    'afa_percent',
                    'recommendation',
                ],
            ]);

        $calculation = Calculation::first();

        $this->assertNotNull($calculation);
        $this->assertSame('zweifamilienhaus', $calculation->propertyType->key);
        $this->assertGreaterThan(0, (float) $calculation->rnd_years);
        $this->assertSame(2025, $calculation->ermittlungsjahr);
    }

    public function test_it_creates_offer_from_calculation(): void
    {
        $calcResponse = $this->postJson('/api/rnd/calculate', [
            'property_type_key' => 'einfamilienhaus',
            'baujahr' => 1975,
            'anschaffungsjahr' => 2021,
            'steuerjahr' => 2025,
            'renovations' => [
                ['category_key' => 'heizung', 'time_window_key' => 'bis_5', 'extent_percent' => 100],
            ],
            'contact' => [
                'name' => 'Hans Beispiel',
                'email' => 'hans@example.de',
                'phone' => '+49 151 9876543',
            ],
        ])->assertOk();

        $publicRef = $calcResponse->json('data.public_ref');
        $this->assertNotNull($publicRef);

        $offerResponse = $this->postJson('/api/offers', [
            'calculation_public_ref' => $publicRef,
            'customer' => [
                'name' => 'Max Mustermann',
                'email' => 'max@example.com',
                'phone' => '+49 123 4567',
            ],
            'addons' => ['besichtigung'],
        ]);

        $offerResponse->assertCreated()
            ->assertJsonPath('data.customer.email', 'max@example.com')
            ->assertJsonPath('data.pricing.base_price_eur', 1449)
            ->assertJsonPath('data.pricing.net_total_eur', 1449 + 350);

        $this->assertSame(1, Offer::count());
        $offer = Offer::first();
        $this->assertSame('max@example.com', $offer->customer->email);
        $this->assertSame(1449 + 350, $offer->net_total_eur);
        $this->assertEquals(['besichtigung'], $offer->input_snapshot['addons']);
    }
}
