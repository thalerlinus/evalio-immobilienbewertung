<?php

namespace App\Services;

use App\Models\Calculation;
use App\Models\Customer;
use App\Models\DiscountCode;
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

            $priceOnRequest = $basePrice === null;
            $vatPercent = 19;

            $pricing = $this->buildPricing($lineItems, $vatPercent, $priceOnRequest, null);

            $attributes = [
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
                        'discount' => null,
                    ],
                ],
                'base_price_eur' => $basePrice,
                'inspection_price_eur' => $packageKey === 'besichtigung' ? $packagePrice : null,
                'ga_package_key' => $packageKey,
                'ga_package_label' => $packageLabel,
                'ga_package_price_eur' => $packagePrice,
                'discount_code' => null,
                'discount_percent' => null,
                'discount_eur' => $pricing['discount_net'],
                'net_total_eur' => $pricing['net_total'],
                'vat_percent' => $vatPercent,
                'vat_amount_eur' => $pricing['vat_amount'],
                'gross_total_eur' => $pricing['gross_total'],
                'line_items' => $lineItems,
                'notes' => $payload['notes'] ?? null,
            ];

            $existingOffer = Offer::where('calculation_id', $calculation->id)
                ->latest('id')
                ->first();

            if ($existingOffer) {
                $existingOffer->fill($attributes);
                $existingOffer->save();

                return $existingOffer->fresh(['calculation.propertyType', 'customer']);
            }

            $offer = Offer::create($attributes);

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

            $priceOnRequest = $offer->base_price_eur === null;
            $vatPercent = 19;
            $discountPercent = $offer->discount_percent;

            $pricing = $this->buildPricing($lineItems, $vatPercent, $priceOnRequest, $discountPercent);

            $inputSnapshot = $offer->input_snapshot ?? [];
            $inputSnapshot['addons'] = $packageKey ? [$packageKey] : [];
            $inputSnapshot['pricing'] = $inputSnapshot['pricing'] ?? [];
            $inputSnapshot['pricing']['base'] = [
                'label' => $baseLabel,
                'amount_eur' => $offer->base_price_eur,
            ];
            $inputSnapshot['pricing']['discount'] = $offer->discount_code ? [
                'code' => $offer->discount_code,
                'percent' => $offer->discount_percent,
            ] : null;

            $offer->fill([
                'inspection_price_eur' => $packageKey === 'besichtigung' ? $packagePrice : null,
                'ga_package_key' => $packageKey,
                'ga_package_label' => $packageLabel,
                'ga_package_price_eur' => $packagePrice,
                'discount_eur' => $pricing['discount_net'],
                'net_total_eur' => $pricing['net_total'],
                'vat_percent' => $vatPercent,
                'vat_amount_eur' => $pricing['vat_amount'],
                'gross_total_eur' => $pricing['gross_total'],
                'input_snapshot' => $inputSnapshot,
                'line_items' => $lineItems,
            ]);

            $offer->save();

            return $offer->fresh(['calculation.propertyType', 'customer']);
        });
    }

    public function updateBillingAddress(Offer $offer, array $billingAddress): Offer
    {
        return DB::transaction(function () use ($offer, $billingAddress) {
            $name = trim((string) (Arr::get($billingAddress, 'name') ?? ''));
            $company = trim((string) (Arr::get($billingAddress, 'company') ?? ''));
            $email = trim((string) (Arr::get($billingAddress, 'email') ?? ''));
            $street = trim((string) (Arr::get($billingAddress, 'street') ?? ''));
            $zip = preg_replace('/\s+/', '', (string) (Arr::get($billingAddress, 'zip') ?? ''));
            $city = trim((string) (Arr::get($billingAddress, 'city') ?? ''));

            $company = $company !== '' ? $company : null;
            $email = $email !== '' ? $email : null;

            $customer = $offer->customer;

            if (! $customer) {
                $emailForLookup = Arr::get($offer->input_snapshot, 'customer.email')
                    ?? Arr::get($offer->input_snapshot, 'calculation_inputs.contact.email')
                    ?? $email;

                if ($emailForLookup) {
                    $customer = Customer::updateOrCreate(
                        ['email' => $emailForLookup],
                        []
                    );

                    $offer->customer()->associate($customer);
                }
            }

            if ($customer) {
                $customer->fill([
                    'billing_name' => $name,
                    'billing_company' => $company,
                    'billing_email' => $email,
                    'billing_street' => $street,
                    'billing_zip' => $zip,
                    'billing_city' => $city,
                    'billing_country' => $customer->billing_country ?? 'DE',
                ]);

                $customer->save();
            }

            $inputSnapshot = $offer->input_snapshot ?? [];

            $customerSnapshot = $inputSnapshot['customer'] ?? [];
            $customerSnapshot['billing_name'] = $name;
            $customerSnapshot['billing_company'] = $company;
            $customerSnapshot['billing_email'] = $email;
            $customerSnapshot['billing_street'] = $street;
            $customerSnapshot['billing_zip'] = $zip;
            $customerSnapshot['billing_city'] = $city;
            $inputSnapshot['customer'] = $customerSnapshot;

            $calculationInputs = $inputSnapshot['calculation_inputs'] ?? [];
            $billingSnapshot = $calculationInputs['billing_address'] ?? [];
            $billingSnapshot['name'] = $name;
            $billingSnapshot['company'] = $company;
            $billingSnapshot['email'] = $email;
            $billingSnapshot['street'] = $street;
            $billingSnapshot['zip'] = $zip;
            $billingSnapshot['city'] = $city;
            $billingSnapshot['country'] = $billingSnapshot['country'] ?? 'DE';
            $calculationInputs['billing_address'] = $billingSnapshot;
            $inputSnapshot['calculation_inputs'] = $calculationInputs;

            $offer->input_snapshot = $inputSnapshot;
            $offer->save();

            return $offer->fresh(['calculation.propertyType', 'customer']);
        });
    }

    public function applyDiscount(Offer $offer, ?DiscountCode $discountCode): Offer
    {
        return DB::transaction(function () use ($offer, $discountCode) {
            $lineItems = $offer->line_items ?? [];
            $priceOnRequest = $offer->base_price_eur === null;
            $vatPercent = $offer->vat_percent ?? 19;
            $discountPercent = $discountCode?->percent;

            $pricing = $this->buildPricing($lineItems, $vatPercent, $priceOnRequest, $discountPercent);

            $discountSnapshot = $discountCode ? [
                'code' => $discountCode->code,
                'percent' => $discountCode->percent,
            ] : null;

            $inputSnapshot = $offer->input_snapshot ?? [];
            $inputSnapshot['pricing'] = $inputSnapshot['pricing'] ?? [];
            $inputSnapshot['pricing']['discount'] = $discountSnapshot;

            $offer->fill([
                'discount_code' => $discountCode?->code,
                'discount_percent' => $discountCode?->percent,
                'discount_eur' => $pricing['discount_net'],
                'net_total_eur' => $pricing['net_total'],
                'vat_percent' => $vatPercent,
                'vat_amount_eur' => $pricing['vat_amount'],
                'gross_total_eur' => $pricing['gross_total'],
                'discount_applied_at' => $discountCode ? now() : null,
                'input_snapshot' => $inputSnapshot,
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
            'billing_name' => $input['billing_name'] ?? null,
            'billing_company' => $input['billing_company'] ?? null,
            'billing_email' => $input['billing_email'] ?? null,
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

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     * @return array{net_total: int|null, discount_net: int, vat_amount: int|null, gross_total: int|null}
     */
    private function buildPricing(array $lineItems, int $vatPercent, bool $priceOnRequest, ?int $discountPercent): array
    {
        $netSubtotal = collect($lineItems)
            ->pluck('amount_eur')
            ->filter(fn ($value) => $value !== null)
            ->sum();

        if ($netSubtotal === 0) {
            $netSubtotal = null;
        }

        if ($priceOnRequest || $netSubtotal === null) {
            return [
                'net_total' => null,
                'discount_net' => 0,
                'vat_amount' => null,
                'gross_total' => null,
            ];
        }

        $percent = $discountPercent !== null ? max(0, min(100, (int) $discountPercent)) : 0;
        $discountNet = $percent > 0 ? (int) round($netSubtotal * $percent / 100) : 0;
        $discountNet = min($discountNet, $netSubtotal);

        $netTotal = $netSubtotal - $discountNet;
        if ($netTotal < 0) {
            $netTotal = 0;
        }

        $vatAmount = (int) round($netTotal * $vatPercent / 100);
        $grossTotal = $netTotal + $vatAmount;

        return [
            'net_total' => $netTotal,
            'discount_net' => $discountNet,
            'vat_amount' => $vatAmount,
            'gross_total' => $grossTotal,
        ];
    }
}
