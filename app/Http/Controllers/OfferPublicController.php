<?php

namespace App\Http\Controllers;

use App\Mail\OfferAdminNotificationMail;
use App\Mail\OfferConfirmedMail;
use App\Models\ContactSetting;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class OfferPublicController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $offer = Offer::with(['calculation.propertyType', 'customer'])
            ->where('view_token', $token)
            ->firstOrFail();

        return Inertia::render('Offers/Show', [
            'offer' => $this->buildOfferPayload($offer),
            'contactSettings' => ContactSetting::values([
                'support_name',
                'support_email',
                'support_phone',
                'support_phone_display',
            ]),
        ]);
    }

    public function confirm(Request $request, string $token): JsonResponse
    {
        $offer = Offer::with(['calculation.propertyType', 'customer'])
            ->where('view_token', $token)
            ->firstOrFail();

        if ($offer->accepted_at !== null) {
            return response()->json([
                'message' => __('Dieses Angebot wurde bereits bestätigt.'),
                'data' => $this->buildOfferPayload($offer),
            ]);
        }

        $offer->status = 'accepted';
        $offer->accepted_at = now();
        $offer->sent_at = $offer->sent_at ?? now();
        $offer->save();

        $calculationInputs = $this->resolveCalculationInputs($offer);
        $contact = $this->resolveContactData($offer, $calculationInputs);

        if ($customerEmail = $contact['email'] ?? $offer->customer?->email) {
            Mail::to($customerEmail)->send(new OfferConfirmedMail($offer, $contact));
        }

        $adminAddress = ContactSetting::findValue('admin_notification_email', config('mail.admin_address'));

        if ($this->shouldNotifyAdmin($offer) && $adminAddress) {
            Mail::to($adminAddress)->send(new OfferAdminNotificationMail($offer, $contact));
        }

        return response()->json([
            'message' => __('Vielen Dank! Ihr Angebot wurde bestätigt.'),
            'data' => $this->buildOfferPayload($offer, $calculationInputs, $contact),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $calculationInputs
     * @param  array<string, mixed>|null  $contact
     * @return array<string, mixed>
     */
    private function buildOfferPayload(Offer $offer, ?array $calculationInputs = null, ?array $contact = null): array
    {
        $calculationInputs ??= $this->resolveCalculationInputs($offer);
        $contact ??= $this->resolveContactData($offer, $calculationInputs);
        $renovations = $this->resolveRenovations($offer, $calculationInputs);

        return [
            'token' => $offer->view_token,
            'public_url' => route('offers.public.show', $offer->view_token),
            'number' => $offer->number,
            'status' => $offer->status,
            'created_at' => $offer->created_at,
            'accepted_at' => $offer->accepted_at,
            'is_confirmed' => $offer->accepted_at !== null,
            'can_confirm' => $offer->accepted_at === null,
            'calculation' => [
                'property_type' => $offer->calculation?->propertyType?->label,
                'rnd_years' => $offer->calculation?->rnd_years,
                'rnd_min' => $offer->calculation?->rnd_min,
                'rnd_max' => $offer->calculation?->rnd_max,
                'rnd_interval_label' => $offer->calculation?->rnd_interval_label,
                'afa_percent' => $offer->calculation?->afa_percent,
                'recommendation' => $offer->calculation?->recommendation,
            ],
            'pricing' => [
                'base_price_eur' => $offer->base_price_eur,
                'inspection_price_eur' => $offer->inspection_price_eur,
                'discount_eur' => $offer->discount_eur,
                'net_total_eur' => $offer->net_total_eur,
                'vat_percent' => $offer->vat_percent,
                'vat_amount_eur' => $offer->vat_amount_eur,
                'gross_total_eur' => $offer->gross_total_eur,
                'line_items' => $offer->line_items,
            ],
            'customer' => [
                'name' => $offer->customer?->name,
                'email' => $offer->customer?->email,
                'phone' => $offer->customer?->phone,
                'billing_street' => $offer->customer?->billing_street,
                'billing_zip' => $offer->customer?->billing_zip,
                'billing_city' => $offer->customer?->billing_city,
            ],
            'form_inputs' => [
                'property' => Arr::only($calculationInputs, [
                    'property_type_key',
                    'gnd_override',
                    'baujahr',
                    'anschaffungsjahr',
                    'steuerjahr',
                    'ermittlungsjahr',
                    'bauweise',
                    'eigennutzung',
                ]),
                'address' => Arr::get($calculationInputs, 'address', []),
                'contact' => $contact,
                'renovations' => $renovations,
                'notes' => $calculationInputs['notes'] ?? null,
            ],
            'raw_inputs' => $calculationInputs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCalculationInputs(Offer $offer): array
    {
        return $offer->input_snapshot['calculation_inputs']
            ?? $offer->calculation?->inputs
            ?? [];
    }

    /**
     * @param  array<string, mixed>  $calculationInputs
     * @return array<string, mixed>
     */
    private function resolveContactData(Offer $offer, array $calculationInputs): array
    {
        $customerSnapshot = $offer->input_snapshot['customer'] ?? [];

        return [
            'name' => $customerSnapshot['name']
                ?? Arr::get($calculationInputs, 'contact.name')
                ?? $offer->customer?->name,
            'email' => $customerSnapshot['email']
                ?? Arr::get($calculationInputs, 'contact.email')
                ?? $offer->customer?->email,
            'phone' => $customerSnapshot['phone']
                ?? Arr::get($calculationInputs, 'contact.phone')
                ?? $offer->customer?->phone,
        ];
    }

    /**
     * @param  array<string, mixed>  $calculationInputs
     * @return array<int, array<string, mixed>>
     */
    private function resolveRenovations(Offer $offer, array $calculationInputs): array
    {
        return collect(Arr::get($calculationInputs, 'renovations', []))
            ->map(function ($item, $key) use ($offer) {
                $categoryKey = is_string($key) ? $key : ($item['category_key'] ?? null);

                return [
                    'category_key' => $categoryKey,
                    'label' => data_get($offer->calculation?->score_details, $categoryKey . '.label'),
                    'extent_percent' => $item['extent_percent'] ?? null,
                    'time_window_key' => $item['time_window_key'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function shouldNotifyAdmin(Offer $offer): bool
    {
        $recommendation = $offer->calculation?->recommendation;

        if (! $recommendation) {
            return false;
        }

        return $recommendation === __('Gutachten ist sinnvoll, Beauftragung empfehlen');
    }
}
