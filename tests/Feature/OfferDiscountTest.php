<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_discount_code_applies_and_updates_pricing(): void
    {
        $offer = $this->createOffer();

        DiscountCode::create([
            'code' => 'SAVE10',
            'label' => 'Sommeraktion',
            'percent' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('offers.public.discount', $offer->view_token), [
            'code' => 'save10',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.pricing.discount_code', 'SAVE10')
            ->assertJsonPath('data.pricing.discount_percent', 10)
            ->assertJsonPath('data.pricing.gross_total_eur', 1071);

        $offer->refresh();

        $this->assertSame('SAVE10', $offer->discount_code);
        $this->assertSame(10, $offer->discount_percent);
        $this->assertSame(100, $offer->discount_eur);
        $this->assertSame(900, $offer->net_total_eur);
        $this->assertSame(171, $offer->vat_amount_eur);
        $this->assertSame(1071, $offer->gross_total_eur);
    }

    public function test_discount_code_can_be_removed(): void
    {
        $offer = $this->createOffer();

        DiscountCode::create([
            'code' => 'SAVE10',
            'label' => null,
            'percent' => 10,
            'is_active' => true,
        ]);

        $this->postJson(route('offers.public.discount', $offer->view_token), ['code' => 'SAVE10'])->assertOk();

        $this->postJson(route('offers.public.discount', $offer->view_token), ['code' => null])->assertOk();

        $offer->refresh();

        $this->assertNull($offer->discount_code);
        $this->assertNull($offer->discount_percent);
        $this->assertSame(0, $offer->discount_eur);
        $this->assertSame(1000, $offer->net_total_eur);
        $this->assertSame(190, $offer->vat_amount_eur);
        $this->assertSame(1190, $offer->gross_total_eur);
    }

    public function test_invalid_discount_code_returns_error(): void
    {
        $offer = $this->createOffer();

        $response = $this->postJson(route('offers.public.discount', $offer->view_token), ['code' => 'UNKNOWN']);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Der eingegebene Rabattcode ist ungültig oder nicht mehr aktiv.');
    }

    private function createOffer(): Offer
    {
        return Offer::create([
            'calculation_snapshot' => [],
            'input_snapshot' => [
                'pricing' => [
                    'base' => [
                        'label' => 'Gutachten',
                        'amount_eur' => 1000,
                    ],
                    'discount' => null,
                ],
            ],
            'base_price_eur' => 1000,
            'inspection_price_eur' => null,
            'ga_package_key' => null,
            'ga_package_label' => null,
            'ga_package_price_eur' => null,
            'discount_code' => null,
            'discount_percent' => null,
            'discount_eur' => 0,
            'net_total_eur' => 1000,
            'vat_percent' => 19,
            'vat_amount_eur' => 190,
            'gross_total_eur' => 1190,
            'line_items' => [
                [
                    'key' => 'base',
                    'label' => 'Gutachten',
                    'amount_eur' => 1000,
                ],
            ],
            'notes' => null,
        ]);
    }
}
