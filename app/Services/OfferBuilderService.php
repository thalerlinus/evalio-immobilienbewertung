<?php

namespace App\Services;

use App\Models\Calculation;
use App\Models\Customer;
use App\Models\GaPricing;
use App\Models\Offer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferBuilderService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Offer
    {
    return DB::transaction(function () use ($payload) {
            $calculation = Calculation::with('propertyType')->where('public_ref', $payload['calculation_public_ref'] ?? null)->first();

            if (! $calculation) {
                throw ValidationException::withMessages([
                    'calculation_public_ref' => __('Die angegebene Berechnung wurde nicht gefunden.'),
                ]);
            }

            $customerData = Arr::get($payload, 'customer', []);
            $customer = $this->upsertCustomer($customerData, $calculation);

            $packageKey = $payload['ga_package_key'] ?? null;

            if ($packageKey === '') {
                $packageKey = null;
            }

            if ($packageKey === null) {
                $addonKeys = collect($payload['addons'] ?? [])->filter()->unique()->values();
                $packageKey = $addonKeys->first();
            }

            $packagePricing = $packageKey
                ? GaPricing::where('key', $packageKey)->first()
                : null;

            if ($packageKey && (! $packagePricing || $packagePricing->category !== 'package')) {
                throw ValidationException::withMessages([
                    'ga_package_key' => __('Die ausgewählte Zusatzoption ist nicht verfügbar.'),
                ]);
            }

            $basePrice = $calculation->propertyType?->price_standard_eur;
            $baseLabel = $calculation->propertyType?->label ?? __('Gutachten');
            $lineItems = [[
                'key' => 'base',
                'label' => $baseLabel,
                'amount_eur' => $basePrice !== null ? (int) $basePrice : null,
            ]];

            $packagePrice = null;
            $packageLabel = null;

            if ($packagePricing) {
                $packageLabel = $packagePricing->label;
                $packagePrice = $packagePricing->price_eur !== null ? (int) $packagePricing->price_eur : null;

                $lineItems[] = [
                    'key' => $packagePricing->key,
                    'label' => $packageLabel,
                    'amount_eur' => $packagePrice,
                ];
            }

            $netTotal = collect($lineItems)
                ->pluck('amount_eur')
                ->filter(fn ($value) => $value !== null)
                ->sum();

            if ($netTotal === 0) {
                $netTotal = null;
            }

            $priceOnRequest = $basePrice === null;

            if ($priceOnRequest) {
                $netTotal = null;
            }

            $vatPercent = 19;
            $vatAmount = $netTotal !== null ? (int) round($netTotal * $vatPercent / 100) : null;
            $grossTotal = $netTotal !== null ? $netTotal + $vatAmount : null;

            $offer = Offer::create([
                'calculation_id' => $calculation->id,
                'customer_id' => $customer?->id,
                'property_type_id' => $calculation->property_type_id,
                'calculation_snapshot' => Arr::only($calculation->toArray(), [
                    'id',
                    'public_ref',
                    'gnd',
                    'baujahr',
                    'anschaffungsjahr',
                    'steuerjahr',
                    'ermittlungsjahr',
                    'alter',
                    'score',
                    'score_details',
                    'rnd_years',
                    'rnd_min',
                    'rnd_max',
                    'rnd_interval_label',
                    'afa_percent',
                    'afa_percent_from',
                    'afa_percent_to',
                    'afa_percent_label',
                    'recommendation',
                ]),
                'input_snapshot' => [
                    'calculation_inputs' => $calculation->inputs,
                    'customer' => $customerData,
                    'addons' => $packageKey ? [$packageKey] : [],
                    'pricing' => [
                        'base' => [
                            'label' => $baseLabel,
                            'amount_eur' => $basePrice,
                        ],
                    ],
                ],
                'base_price_eur' => $basePrice,
                'inspection_price_eur' => $packageKey === 'besichtigung' ? $packagePrice : null,
                'ga_package_key' => $packageKey,
                'ga_package_label' => $packageLabel,
                'ga_package_price_eur' => $packagePrice,
                'discount_eur' => 0,
                'net_total_eur' => $netTotal,
                'vat_percent' => $vatPercent,
                'vat_amount_eur' => $vatAmount,
                'gross_total_eur' => $grossTotal,
                'line_items' => $lineItems,
                'notes' => $payload['notes'] ?? null,
            ]);

            return $offer->fresh(['calculation.propertyType', 'customer']);
        });
    }

    public function updatePackage(Offer $offer, ?string $packageKey): Offer
    {
        return DB::transaction(function () use ($offer, $packageKey) {
            if ($packageKey === null || $packageKey === '') {
                $packageKey = null;
            }

            $packagePricing = $packageKey ? GaPricing::where('key', $packageKey)->first() : null;

            if ($packageKey && (! $packagePricing || $packagePricing->category !== 'package')) {
                throw ValidationException::withMessages([
                    'ga_package_key' => __('Die ausgewählte Zusatzoption ist nicht verfügbar.'),
                ]);
            }

            $packageLabel = $packagePricing?->label;
            $packagePrice = $packagePricing?->price_eur !== null ? (int) $packagePricing->price_eur : null;

            $baseLabel = $offer->calculation?->propertyType?->label
                ?? data_get($offer->calculation_snapshot, 'property_type.label')
                ?? data_get($offer->input_snapshot, 'pricing.base.label')
                ?? __('Gutachten');

            $lineItems = [[
                'key' => 'base',
                'label' => $baseLabel,
                'amount_eur' => $offer->base_price_eur !== null ? (int) $offer->base_price_eur : null,
            ]];

            if ($packagePricing) {
                $lineItems[] = [
                    'key' => $packagePricing->key,
                    'label' => $packageLabel,
                    'amount_eur' => $packagePrice,
                ];
            }

            $netTotal = collect($lineItems)
                ->pluck('amount_eur')
                ->filter(fn ($value) => $value !== null)
                ->sum();

            if ($netTotal === 0) {
                $netTotal = null;
            }

            $priceOnRequest = $offer->base_price_eur === null;

            if ($priceOnRequest) {
                $netTotal = null;
            }

            $vatPercent = 19;
            $vatAmount = $netTotal !== null ? (int) round($netTotal * $vatPercent / 100) : null;
            $grossTotal = $netTotal !== null ? $netTotal + $vatAmount : null;

            $inputSnapshot = $offer->input_snapshot ?? [];
            $inputSnapshot['addons'] = $packageKey ? [$packageKey] : [];
            $inputSnapshot['pricing'] = $inputSnapshot['pricing'] ?? [];
            $inputSnapshot['pricing']['base'] = [
                'label' => $baseLabel,
                'amount_eur' => $offer->base_price_eur,
            ];

            $offer->fill([
                'inspection_price_eur' => $packageKey === 'besichtigung' ? $packagePrice : null,
                'ga_package_key' => $packageKey,
                'ga_package_label' => $packageLabel,
                'ga_package_price_eur' => $packagePrice,
                'net_total_eur' => $netTotal,
                'vat_percent' => $vatPercent,
                'vat_amount_eur' => $vatAmount,
                'gross_total_eur' => $grossTotal,
                'input_snapshot' => $inputSnapshot,
                'line_items' => $lineItems,
            ]);

            $offer->save();

            return $offer->fresh(['calculation.propertyType', 'customer']);
        });
    }

    private function upsertCustomer(array $input, Calculation $calculation): ?Customer
    {
        $email = $input['email'] ?? null;

        if (! $email) {
            return null;
        }

        $payload = [
            'name' => $input['name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'billing_street' => $input['street'] ?? Arr::get($calculation->inputs, 'billing_address.street'),
            'billing_zip' => $input['zip'] ?? Arr::get($calculation->inputs, 'billing_address.zip'),
            'billing_city' => $input['city'] ?? Arr::get($calculation->inputs, 'billing_address.city'),
            'billing_country' => $input['country'] ?? 'DE',
        ];

        return Customer::updateOrCreate(
            ['email' => $email],
            array_filter($payload, fn ($value) => $value !== null && $value !== '')
        );
    }
}
