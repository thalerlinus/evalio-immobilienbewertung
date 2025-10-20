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

            $addonKeys = collect($payload['addons'] ?? [])->filter()->unique()->values();
            $addonPricing = $addonKeys->isNotEmpty()
                ? GaPricing::whereIn('key', $addonKeys)->get()->keyBy('key')
                : collect();

            $basePrice = $calculation->propertyType?->price_standard_eur;
            $lineItems = [];

            if ($basePrice !== null) {
                $lineItems[] = [
                    'key' => 'base',
                    'label' => $calculation->propertyType?->label ?? __('Gutachten'),
                    'amount_eur' => (int) $basePrice,
                ];
            }

            $inspectionPrice = null;
            $onlinePrice = null;

            foreach ($addonKeys as $key) {
                $pricing = $addonPricing->get($key);

                if (! $pricing) {
                    continue;
                }

                $amount = $pricing->price_eur !== null ? (int) $pricing->price_eur : null;

                if ($key === 'besichtigung') {
                    $inspectionPrice = $amount;
                }

                if ($key === 'online') {
                    $onlinePrice = $amount;
                }

                $lineItems[] = [
                    'key' => $key,
                    'label' => $pricing->label,
                    'amount_eur' => $amount,
                ];
            }

            $netTotal = collect($lineItems)
                ->pluck('amount_eur')
                ->filter(fn ($value) => $value !== null)
                ->sum();

            if ($netTotal === 0) {
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
                    'recommendation',
                ]),
                'input_snapshot' => [
                    'calculation_inputs' => $calculation->inputs,
                    'customer' => $customerData,
                    'addons' => $addonKeys->toArray(),
                ],
                'base_price_eur' => $basePrice,
                'inspection_price_eur' => $inspectionPrice,
                'discount_eur' => 0,
                'net_total_eur' => $netTotal,
                'vat_percent' => $netTotal !== null ? $vatPercent : null,
                'vat_amount_eur' => $vatAmount,
                'gross_total_eur' => $grossTotal,
                'line_items' => $lineItems,
                'notes' => $payload['notes'] ?? null,
            ]);

            if ($onlinePrice !== null) {
                $offer->line_items = collect($offer->line_items ?? [])->map(function ($item) use ($onlinePrice) {
                    if (($item['key'] ?? null) === 'online') {
                        $item['amount_eur'] = $onlinePrice;
                    }

                    return $item;
                })->toArray();
                $offer->save();
            }

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
            'billing_street' => $input['street'] ?? Arr::get($calculation->inputs, 'address.street'),
            'billing_zip' => $input['zip'] ?? Arr::get($calculation->inputs, 'address.zip'),
            'billing_city' => $input['city'] ?? Arr::get($calculation->inputs, 'address.city'),
            'billing_country' => $input['country'] ?? Arr::get($calculation->inputs, 'address.country', 'DE'),
        ];

        return Customer::updateOrCreate(
            ['email' => $email],
            array_filter($payload, fn ($value) => $value !== null && $value !== '')
        );
    }
}
