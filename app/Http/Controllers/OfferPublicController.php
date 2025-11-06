<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyDiscountCodeRequest;
use App\Http\Requests\UpdateOfferBillingAddressRequest;
use App\Http\Requests\UpdateOfferPackageRequest;
use App\Mail\OfferAdminNotificationMail;
use App\Mail\OfferConfirmedMail;
use App\Models\ContactSetting;
use App\Models\DiscountCode;
use App\Models\GaPricing;
use App\Models\Offer;
use App\Services\OfferBuilderService;
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
            'gaPackages' => GaPricing::where('category', 'package')
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(['key', 'label', 'price_eur', 'sort_order'])
                ->map(fn ($pricing) => [
                    'key' => $pricing->key,
                    'label' => $pricing->label,
                    'price_eur' => $pricing->price_eur,
                    'sort_order' => $pricing->sort_order,
                ])
                ->values()
                ->all(),
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

        if (! $this->hasBillingAddress($offer)) {
            return response()->json([
                'message' => __('Bitte ergänzen Sie Ihre Rechnungsadresse, bevor Sie das Angebot bestätigen.'),
                'data' => $this->buildOfferPayload($offer),
            ], 422);
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

    public function updatePackage(UpdateOfferPackageRequest $request, OfferBuilderService $service, string $token): JsonResponse
    {
        $offer = Offer::with(['calculation.propertyType', 'customer'])
            ->where('view_token', $token)
            ->firstOrFail();

        if ($offer->accepted_at !== null) {
            return response()->json([
                'message' => __('Das Angebot wurde bereits bestätigt und kann nicht mehr geändert werden.'),
                'data' => $this->buildOfferPayload($offer),
            ], 422);
        }

        $validated = $request->validated();
        $packageKey = $validated['ga_package_key'] ?? null;

        $updatedOffer = $service->updatePackage($offer, $packageKey);

        return response()->json([
            'message' => __('Ihre Auswahl wurde aktualisiert.'),
            'data' => $this->buildOfferPayload($updatedOffer),
        ]);
    }

    public function applyDiscount(ApplyDiscountCodeRequest $request, OfferBuilderService $service, string $token): JsonResponse
    {
        $offer = Offer::with(['calculation.propertyType', 'customer'])
            ->where('view_token', $token)
            ->firstOrFail();

        if ($offer->accepted_at !== null) {
            return response()->json([
                'message' => __('Das Angebot wurde bereits bestätigt und kann nicht mehr geändert werden.'),
                'data' => $this->buildOfferPayload($offer),
            ], 422);
        }

        $validated = $request->validated();
        $codeValue = isset($validated['code']) ? mb_strtoupper(trim($validated['code'])) : null;

        if (! $codeValue) {
            $updatedOffer = $service->applyDiscount($offer, null);

            return response()->json([
                'message' => __('Rabattcode wurde entfernt.'),
                'data' => $this->buildOfferPayload($updatedOffer),
            ]);
        }

        $discountCode = DiscountCode::active()
            ->where('code', $codeValue)
            ->first();

        if (! $discountCode) {
            return response()->json([
                'message' => __('Der eingegebene Rabattcode ist ungültig oder nicht mehr aktiv.'),
                'data' => $this->buildOfferPayload($offer),
            ], 422);
        }

        $updatedOffer = $service->applyDiscount($offer, $discountCode);

        return response()->json([
            'message' => __('Rabattcode wurde angewendet.'),
            'data' => $this->buildOfferPayload($updatedOffer),
        ]);
    }

    public function updateBillingAddress(UpdateOfferBillingAddressRequest $request, OfferBuilderService $service, string $token): JsonResponse
    {
        $offer = Offer::with(['calculation.propertyType', 'customer'])
            ->where('view_token', $token)
            ->firstOrFail();

        if ($offer->accepted_at !== null) {
            return response()->json([
                'message' => __('Das Angebot wurde bereits bestätigt und kann nicht mehr geändert werden.'),
                'data' => $this->buildOfferPayload($offer),
            ], 422);
        }

        $validated = $request->validated();
        $billingAddress = $validated['billing_address'] ?? [];

        $updatedOffer = $service->updateBillingAddress($offer, $billingAddress);

        return response()->json([
            'message' => __('Rechnungsadresse gespeichert.'),
            'data' => $this->buildOfferPayload($updatedOffer),
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
                'afa_percent_from' => $offer->calculation?->afa_percent_from,
                'afa_percent_to' => $offer->calculation?->afa_percent_to,
                'afa_percent_label' => $offer->calculation?->afa_percent_label,
                'recommendation' => $offer->calculation?->recommendation,
            ],
            'pricing' => [
                'base_price_eur' => $offer->base_price_eur,
                'inspection_price_eur' => $offer->inspection_price_eur,
                'discount_eur' => $offer->discount_eur,
                'discount_code' => $offer->discount_code,
                'discount_percent' => $offer->discount_percent,
                'discount_applied_at' => $offer->discount_applied_at,
                'net_total_eur' => $offer->net_total_eur,
                'vat_percent' => $offer->vat_percent,
                'vat_amount_eur' => $offer->vat_amount_eur,
                'gross_total_eur' => $offer->gross_total_eur,
                'line_items' => $offer->line_items,
                'ga_package' => $offer->ga_package_key ? [
                    'key' => $offer->ga_package_key,
                    'label' => $offer->ga_package_label,
                    'price_eur' => $offer->ga_package_price_eur,
                ] : null,
                'price_on_request' => $offer->base_price_eur === null,
            ],
            'selected_package_key' => $offer->ga_package_key,
            'customer' => [
                'name' => $offer->customer?->name,
                'email' => $offer->customer?->email,
                'phone' => $offer->customer?->phone,
                'billing_street' => $offer->customer?->billing_street,
                'billing_zip' => $offer->customer?->billing_zip,
                'billing_city' => $offer->customer?->billing_city,
                'billing_country' => $offer->customer?->billing_country,
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
                'billing_address' => Arr::get($calculationInputs, 'billing_address', []),
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

        return $recommendation === __('Gutachten ist sinnvoll, eine Beauftragung wird empfohlen');
    }

    private function hasBillingAddress(Offer $offer): bool
    {
        $street = $offer->customer?->billing_street;
        $zip = $offer->customer?->billing_zip;
        $city = $offer->customer?->billing_city;

        return collect([$street, $zip, $city])
            ->every(fn ($value) => is_string($value) && trim($value) !== '');
    }
}
