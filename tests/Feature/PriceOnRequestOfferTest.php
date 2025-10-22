<?php

namespace Tests\Feature;

use App\Mail\CalculationResultMail;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PriceOnRequestOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seed();
    }

    public function test_offer_created_with_price_on_request_has_null_totals(): void
    {
        [$offer, $response] = $this->createPriceOnRequestOffer();

        Mail::assertSentTimes(CalculationResultMail::class, 1);

        $this->assertTrue($response->json('data.offer.pricing.price_on_request'));
        $this->assertNull($offer->base_price_eur);
        $this->assertNull($offer->net_total_eur);
    $this->assertSame(19, $offer->vat_percent);
        $this->assertNull($offer->vat_amount_eur);
        $this->assertNull($offer->gross_total_eur);

        $lineItems = $offer->line_items;
        $this->assertNotEmpty($lineItems);
        $this->assertSame('base', $lineItems[0]['key']);
        $this->assertNull($lineItems[0]['amount_eur']);

        $this->assertNull(data_get($offer->input_snapshot, 'pricing.base.amount_eur'));
    }

    public function test_admin_can_set_manual_price_and_trigger_email_resend(): void
    {
        [$offer] = $this->createPriceOnRequestOffer();

        Mail::assertSentTimes(CalculationResultMail::class, 1);

        Mail::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Session::start();
        $token = csrf_token();

        $response = $this->from('/admin/offers')
            ->actingAs($admin)
            ->withHeader('X-CSRF-TOKEN', $token)
            ->put("/admin/offers/{$offer->id}/price", [
                '_token' => $token,
                'price' => 2499,
            ]);

        $response->assertRedirect('/admin/offers');

        $updatedOffer = $offer->fresh();

        $this->assertSame(2499, $updatedOffer->base_price_eur);
        $this->assertSame(2499, $updatedOffer->net_total_eur);
        $this->assertSame(19, $updatedOffer->vat_percent);
        $this->assertSame((int) round(2499 * 0.19), $updatedOffer->vat_amount_eur);
        $this->assertSame(
            $updatedOffer->net_total_eur + $updatedOffer->vat_amount_eur,
            $updatedOffer->gross_total_eur
        );

        $lineItems = $updatedOffer->line_items;
        $this->assertNotEmpty($lineItems);
        $this->assertSame('base', $lineItems[0]['key']);
        $this->assertSame(2499, $lineItems[0]['amount_eur']);

        $this->assertSame(2499, data_get($updatedOffer->input_snapshot, 'pricing.base.amount_eur'));

        Mail::assertSentTimes(CalculationResultMail::class, 1);
        Mail::assertSent(CalculationResultMail::class, function (CalculationResultMail $mail) use ($updatedOffer) {
            return $mail->offer->is($updatedOffer);
        });
    }

    /**
     * @return array{0: Offer, 1: \Illuminate\Testing\TestResponse}
     */
    private function createPriceOnRequestOffer(): array
    {
        $payload = [
            'property_type_key' => 'mfh_10_plus',
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
        ];

        $response = $this->postJson('/api/rnd/calculate', $payload);

        $response->assertOk();

        $token = $response->json('data.offer.view_token');
        $this->assertNotNull($token);

        $offer = Offer::where('view_token', $token)->firstOrFail();

        return [$offer, $response];
    }
}
