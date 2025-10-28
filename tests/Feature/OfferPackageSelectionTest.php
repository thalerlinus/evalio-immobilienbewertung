<?php

namespace Tests\Feature;

use App\Models\GaPricing;
use App\Models\Offer;
use App\Models\PropertyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OfferPackageSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seed();
    }

    public function test_customer_can_select_ga_package(): void
    {
        $offer = $this->createOfferFromPositiveRecommendation();

        Session::start();
        $csrfToken = csrf_token();

        $response = $this->withHeader('X-CSRF-TOKEN', $csrfToken)->postJson("/angebote/{$offer->view_token}/package", [
            '_token' => $csrfToken,
            'ga_package_key' => 'besichtigung',
        ]);

        $packageBesichtigung = GaPricing::where('key', 'besichtigung')->firstOrFail();
        $basePrice = PropertyType::where('key', 'einfamilienhaus')->value('price_standard_eur');

        $response
            ->assertOk()
            ->assertJsonPath('data.pricing.ga_package.key', 'besichtigung')
            ->assertJsonPath('data.pricing.ga_package.price_eur', $packageBesichtigung->price_eur);

        $offer->refresh();

        $this->assertSame('besichtigung', $offer->ga_package_key);
        $this->assertSame($packageBesichtigung->price_eur, $offer->ga_package_price_eur);
        $this->assertSame($basePrice + $packageBesichtigung->price_eur, $offer->net_total_eur);
        $this->assertSame((int) round($offer->net_total_eur * 0.19), $offer->vat_amount_eur);
        $this->assertSame($offer->net_total_eur + $offer->vat_amount_eur, $offer->gross_total_eur);
        $this->assertEquals(['besichtigung'], $offer->input_snapshot['addons']);
    }

    public function test_customer_can_remove_ga_package(): void
    {
        $offer = $this->createOfferFromPositiveRecommendation();

        Session::start();
        $csrfToken = csrf_token();

        $response = $this->withHeader('X-CSRF-TOKEN', $csrfToken)->postJson("/angebote/{$offer->view_token}/package", [
            '_token' => $csrfToken,
            'ga_package_key' => 'online',
        ]);

        $packageOnline = GaPricing::where('key', 'online')->firstOrFail();
        $basePrice = PropertyType::where('key', 'einfamilienhaus')->value('price_standard_eur');

        $response->assertOk();
        $offer->refresh();
        $this->assertSame('online', $offer->ga_package_key);
        $this->assertSame($packageOnline->price_eur, $offer->ga_package_price_eur);

        $response = $this->withHeader('X-CSRF-TOKEN', $csrfToken)->postJson("/angebote/{$offer->view_token}/package", [
            '_token' => $csrfToken,
            'ga_package_key' => null,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.pricing.ga_package', null);

        $offer->refresh();

        $this->assertNull($offer->ga_package_key);
        $this->assertNull($offer->ga_package_price_eur);
        $this->assertSame($basePrice, $offer->net_total_eur);
        $this->assertSame((int) round($offer->net_total_eur * 0.19), $offer->vat_amount_eur);
        $this->assertSame($offer->net_total_eur + $offer->vat_amount_eur, $offer->gross_total_eur);
        $this->assertEquals([], $offer->input_snapshot['addons']);
    }

    private function createOfferFromPositiveRecommendation(): Offer
    {
        $response = $this->postJson('/api/rnd/calculate', [
            'property_type_key' => 'einfamilienhaus',
            'baujahr' => 1975,
            'anschaffungsjahr' => 2021,
            'steuerjahr' => 2025,
            'bauweise' => 'massiv',
            'eigennutzung' => false,
            'renovations' => [
                ['category_key' => 'heizung', 'time_window_key' => 'bis_5', 'extent_percent' => 100],
                ['category_key' => 'dach_waermeschutz', 'time_window_key' => 'bis_5', 'extent_percent' => 100],
            ],
            'contact' => [
                'name' => 'Test Kundin',
                'email' => 'kundin@example.de',
                'phone' => '+49 30 1234567',
            ],
            'address' => [
                'street' => 'Musterstraße 1',
                'zip' => '12345',
                'city' => 'Berlin',
                'country' => 'DE',
            ],
            'billing_address' => [
                'street' => 'Rechnung 2',
                'zip' => '12345',
                'city' => 'Berlin',
                'country' => 'DE',
            ],
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.offer.number'));
        $firstPackageKey = GaPricing::where('category', 'package')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->value('key');
        $this->assertSame($firstPackageKey, $response->json('data.offer.packages.0.key'));

        $token = $response->json('data.offer.view_token');
        $this->assertNotNull($token);

        return Offer::where('view_token', $token)->firstOrFail();
    }
}
