<?php

namespace Tests\Feature;

use App\Models\Calculation;
use App\Models\GaPricing;
use App\Models\Offer;
use App\Models\PropertyType;
use Illuminate\Support\Facades\Mail;
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
            'address' => [
                'street' => 'Musterstraße 5',
                'zip' => '10115',
                'city' => 'Berlin',
                'country' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Rechnung 10',
                'zip' => '10115',
                'city' => 'Berlin',
                'country' => 'DE',
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'calculation' => [
                        'id',
                        'public_ref',
                        'property_type' => ['key', 'label'],
                        'score',
                        'score_details',
                        'rnd_years',
                        'afa_percent',
                        'afa_percent_from',
                        'afa_percent_to',
                        'afa_percent_label',
                        'recommendation',
                    ],
                    'offer',
                ],
            ]);

        $calculation = Calculation::first();

        $this->assertNotNull($calculation);
        $this->assertSame('zweifamilienhaus', $calculation->propertyType->key);
        $this->assertGreaterThan(0, (float) $calculation->rnd_years);
        $this->assertSame(2025, $calculation->ermittlungsjahr);
        $this->assertNotNull($calculation->afa_percent_from);
        $this->assertNotNull($calculation->afa_percent_to);
        $this->assertNotNull($calculation->afa_percent_label);
    }

    public function test_it_caps_property_age_at_seventy_five_years(): void
    {
        $payload = [
            'property_type_key' => 'einfamilienhaus',
            'baujahr' => 1900,
            'anschaffungsjahr' => 2020,
            'steuerjahr' => 2025,
            'renovations' => [],
            'contact' => [
                'name' => 'Eva Beispiel',
                'email' => 'eva@example.de',
                'phone' => '+49 30 1234567',
            ],
            'billing_address' => [
                'street' => 'Rechnung 10',
                'zip' => '10115',
                'city' => 'Berlin',
                'country' => 'DE',
            ],
        ];

        $this->postJson('/api/rnd/calculate', $payload)->assertOk();

        $calculation = Calculation::latest()->first();
        $this->assertNotNull($calculation);

        $this->assertSame(75, $calculation->alter);
        $this->assertSame(1900 + 75, $calculation->result_debug['ermittlungsjahr_for_calculation']);
        $this->assertSame(2025 - 1900, $calculation->result_debug['alter_original']);
        $this->assertNotNull($calculation->afa_percent_from);
        $this->assertNotNull($calculation->afa_percent_to);
    }

    public function test_contact_is_recommended_when_first_estimate_is_high(): void
    {
        Mail::fake();

        $payload = [
            'property_type_key' => 'eigentumswohnung',
            'baujahr' => 2010,
            'anschaffungsjahr' => 2020,
            'steuerjahr' => 2025,
            'renovations' => [],
            'contact' => [
                'name' => 'Clara Kontakt',
                'email' => 'clara@example.de',
                'phone' => '+49 40 123456',
            ],
            'billing_address' => [
                'street' => 'Kontaktweg 1',
                'zip' => '20095',
                'city' => 'Hamburg',
                'country' => 'DE',
            ],
        ];

        $response = $this->postJson('/api/rnd/calculate', $payload);

        $response->assertOk()
            ->assertJsonPath('data.offer', null)
            ->assertJsonPath('data.calculation.recommendation', 'Ein Gutachten ist für Sie allein auf Grundlage dieser Abfrage nicht sinnvoll. Kontaktieren Sie uns gerne und wir prüfen für Sie, ob es Möglichkeiten für eine verkürzte Restnutzungsdauer gibt.');

        $calculation = Calculation::latest()->first();
        $this->assertNotNull($calculation);

    $this->assertGreaterThanOrEqual(50, $calculation->rnd_min);
    $this->assertSame('Ein Gutachten ist für Sie allein auf Grundlage dieser Abfrage nicht sinnvoll. Kontaktieren Sie uns gerne und wir prüfen für Sie, ob es Möglichkeiten für eine verkürzte Restnutzungsdauer gibt.', $calculation->recommendation);

        Mail::assertNothingSent();
    }

    public function test_customer_example_matches_expected_values(): void
    {
        $payload = [
            'property_type_key' => 'eigentumswohnung',
            'baujahr' => 1800,
            'anschaffungsjahr' => 2022,
            'steuerjahr' => 2023,
            'bauweise' => 'massiv',
            'eigennutzung' => false,
            'renovations' => [
                ['category_key' => 'baeder_wc', 'time_window_key' => 'bis_5', 'extent_percent' => 60],
                ['category_key' => 'innenausbau', 'time_window_key' => 'bis_5', 'extent_percent' => 100],
                ['category_key' => 'fenster_tueren', 'time_window_key' => 'bis_5', 'extent_percent' => 80],
                ['category_key' => 'heizung', 'time_window_key' => 'bis_10', 'extent_percent' => 60],
                ['category_key' => 'leitungen', 'time_window_key' => 'bis_10', 'extent_percent' => 20],
                ['category_key' => 'dach_waermeschutz', 'time_window_key' => 'weiss_nicht', 'extent_percent' => 0],
                ['category_key' => 'aussenwaende', 'time_window_key' => 'nicht', 'extent_percent' => 0],
            ],
            'contact' => [
                'name' => 'Kunden Beispiel',
                'email' => 'kunde@example.de',
                'phone' => '+49 30 555555',
            ],
            'address' => [
                'street' => 'Musterstraße 1',
                'zip' => '10115',
                'city' => 'Berlin',
                'country' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Rechnungsweg 1',
                'zip' => '10115',
                'city' => 'Berlin',
                'country' => 'DE',
            ],
        ];

        $this->postJson('/api/rnd/calculate', $payload)->assertOk();

        $calculation = Calculation::latest()->first();
        $this->assertNotNull($calculation);

        $this->assertEqualsWithDelta(6.5, (float) $calculation->score, 0.001);
        $this->assertEqualsWithDelta(6.4, $calculation->result_debug['score_raw'], 0.001);
        $this->assertTrue($calculation->result_debug['use_advanced_formula']);

        $this->assertEqualsWithDelta(28.75, (float) $calculation->rnd_years, 0.01);
        $this->assertSame(25, $calculation->rnd_min);
        $this->assertSame(30, $calculation->rnd_max);

        $this->assertEqualsWithDelta(3.48, (float) $calculation->afa_percent, 0.01);
        $this->assertEqualsWithDelta(3.33, $calculation->afa_percent_from, 0.01);
        $this->assertEqualsWithDelta(4.00, $calculation->afa_percent_to, 0.01);
        $this->assertSame('rd. 25 – 30 Jahre', $calculation->rnd_interval_label);
        $this->assertSame('rd. 3,33 – 4,00 %', $calculation->afa_percent_label);

        $details = $calculation->score_details;
        $this->assertIsArray($details);
        $this->assertEqualsWithDelta(1.2, $details['baeder_wc']['points'], 0.01);
        $this->assertEqualsWithDelta(2.0, $details['innenausbau']['points'], 0.01);
        $this->assertEqualsWithDelta(1.6, $details['fenster_tueren']['points'], 0.01);
        $this->assertEqualsWithDelta(1.2, $details['heizung']['points'], 0.01);
        $this->assertEqualsWithDelta(0.4, $details['leitungen']['points'], 0.01);
        $this->assertEqualsWithDelta(0.0, $details['dach_waermeschutz']['points'], 0.01);
        $this->assertEqualsWithDelta(0.0, $details['aussenwaende']['points'], 0.01);

        $this->assertSame(0.9375, $calculation->result_debug['relative_age']);
        $this->assertSame(75, $calculation->alter);

        $formula = $calculation->result_debug['formula'] ?? null;
        $this->assertNotNull($formula);
        $this->assertEqualsWithDelta(6.5, $formula['score'], 0.001);
        $this->assertEqualsWithDelta(0.5863, $formula['a'], 0.0001);
        $this->assertEqualsWithDelta(1.2783, $formula['b'], 0.0001);
        $this->assertEqualsWithDelta(1.0425, $formula['c'], 0.0001);
        $this->assertEqualsWithDelta(0.28, $formula['rel_alter_min'], 0.0001);
        $this->assertSame(25, $formula['alter_schwelle']);
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
            'billing_address' => [
                'street' => 'Rechnung 20',
                'zip' => '80331',
                'city' => 'München',
                'country' => 'DE',
            ],
        ])->assertOk();

        $publicRef = $calcResponse->json('data.calculation.public_ref');
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

        $basePrice = (int) PropertyType::where('key', 'einfamilienhaus')->value('price_standard_eur');
        $packagePrice = (int) GaPricing::where('key', 'besichtigung')->value('price_eur');

        $offerResponse->assertCreated()
            ->assertJsonPath('data.customer.email', 'max@example.com')
            ->assertJsonPath('data.pricing.base_price_eur', $basePrice)
            ->assertJsonPath('data.pricing.net_total_eur', $basePrice + $packagePrice);

        $this->assertSame(1, Offer::count());
        $offer = Offer::first();
        $this->assertSame('max@example.com', $offer->customer->email);
        $this->assertSame($basePrice + $packagePrice, $offer->net_total_eur);
        $this->assertEquals(['besichtigung'], $offer->input_snapshot['addons']);
    }
}
