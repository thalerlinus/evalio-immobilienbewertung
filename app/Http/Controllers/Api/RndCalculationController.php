<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateRndRequest;
use App\Mail\CalculationResultMail;
use App\Models\GaPricing;
use App\Models\PropertyType;
use App\Models\RenovationCategory;
use App\Models\RenovationExtentWeight;
use App\Services\OfferBuilderService;
use App\Services\RndCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class RndCalculationController extends Controller
{
    public function meta(): JsonResponse
    {
        $propertyTypes = PropertyType::query()
            ->orderBy('request_only')
            ->orderBy('label')
            ->get(['id', 'key', 'label', 'gnd', 'price_standard_eur', 'request_only']);

        $categories = RenovationCategory::with('timeFactors:id,renovation_category_id,time_window_key,factor')
            ->orderBy('id')
            ->get()
            ->map(fn ($category) => [
                'key' => $category->key,
                'label' => $category->label,
                'max_points' => (float) $category->max_points,
                'time_factors' => $category->timeFactors
                    ->sortBy('id')
                    ->map(fn ($factor) => [
                        'time_window_key' => $factor->time_window_key,
                        'factor' => (float) $factor->factor,
                    ])->values(),
            ]);

        $extentWeights = RenovationExtentWeight::orderBy('extent_percent')
            ->get(['extent_percent', 'weight'])
            ->map(fn ($weight) => [
                'extent_percent' => (int) $weight->extent_percent,
                'weight' => (float) $weight->weight,
            ]);

        $gaPricing = GaPricing::orderBy('sort_order')
            ->orderBy('label')
            ->get(['key', 'label', 'category', 'price_eur']);

        return response()->json([
            'data' => [
                'property_types' => $propertyTypes,
                'renovation_categories' => $categories,
                'extent_weights' => $extentWeights,
                'time_windows' => ['nicht', 'bis_5', 'bis_10', 'bis_15', 'bis_20', 'ueber_20', 'weiss_nicht'],
                'ga_pricings' => $gaPricing,
                'defaults' => [
                    'steuerjahr' => now()->year,
                ],
            ],
        ]);
    }

    public function store(CalculateRndRequest $request, RndCalculatorService $service, OfferBuilderService $offerBuilder): JsonResponse
    {
        $validated = $request->validated();

        $calculation = $service->calculate($validated, $request->user());
        $calculation->loadMissing('propertyType');

        $gaPricing = GaPricing::orderBy('sort_order')
            ->orderBy('label')
            ->get(['key', 'label', 'category', 'price_eur']);

        $offer = null;

        if ($calculation->recommendation === __('Gutachten ist sinnvoll, Beauftragung empfehlen')) {
            $address = Arr::wrap($validated['address'] ?? []);
            $billingAddress = Arr::wrap($validated['billing_address'] ?? []);
            $contact = Arr::wrap($validated['contact']);

            $offer = $offerBuilder->create([
                'calculation_public_ref' => $calculation->public_ref,
                'customer' => [
                    'name' => $contact['name'] ?? null,
                    'email' => $contact['email'] ?? null,
                    'phone' => $contact['phone'] ?? null,
                    'street' => $billingAddress['street'] ?? null,
                    'zip' => $billingAddress['zip'] ?? null,
                    'city' => $billingAddress['city'] ?? null,
                    'country' => $billingAddress['country'] ?? 'DE',
                ],
                'addons' => [],
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($contact['email'])) {
                Mail::to($contact['email'])
                    ->send(new CalculationResultMail($calculation, $offer));
            }
        }

        return response()->json([
            'data' => [
                'calculation' => [
                    'id' => $calculation->id,
                    'public_ref' => $calculation->public_ref,
                    'property_type' => $calculation->propertyType ? [
                        'id' => $calculation->propertyType->id,
                        'key' => $calculation->propertyType->key,
                        'label' => $calculation->propertyType->label,
                        'gnd' => $calculation->propertyType->gnd,
                        'price_standard_eur' => $calculation->propertyType->price_standard_eur,
                        'request_only' => $calculation->propertyType->request_only,
                    ] : null,
                    'gnd' => $calculation->gnd,
                    'baujahr' => $calculation->baujahr,
                    'anschaffungsjahr' => $calculation->anschaffungsjahr,
                    'steuerjahr' => $calculation->steuerjahr,
                    'ermittlungsjahr' => $calculation->ermittlungsjahr,
                    'alter' => $calculation->alter,
                    'inputs' => $calculation->inputs,
                    'score' => $calculation->score !== null ? (float) $calculation->score : null,
                    'score_details' => $calculation->score_details,
                    'result_debug' => $calculation->result_debug,
                    'rnd_years' => $calculation->rnd_years !== null ? (float) $calculation->rnd_years : null,
                    'rnd_min' => $calculation->rnd_min,
                    'rnd_max' => $calculation->rnd_max,
                    'rnd_interval_label' => $calculation->rnd_interval_label,
                    'afa_percent' => $calculation->afa_percent !== null ? (float) $calculation->afa_percent : null,
                    'afa_percent_from' => $calculation->afa_percent_from !== null ? (float) $calculation->afa_percent_from : null,
                    'afa_percent_to' => $calculation->afa_percent_to !== null ? (float) $calculation->afa_percent_to : null,
                    'afa_percent_label' => $calculation->afa_percent_label,
                    'recommendation' => $calculation->recommendation,
                    'created_at' => $calculation->created_at,
                ],
                'offer' => $offer ? [
                    'id' => $offer->id,
                    'number' => $offer->number,
                    'view_token' => $offer->view_token,
                    'public_url' => route('offers.public.show', $offer->view_token),
                    'pricing' => [
                        'base_price_eur' => $offer->base_price_eur,
                        'inspection_price_eur' => $offer->inspection_price_eur,
                        'discount_eur' => $offer->discount_eur,
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
                    'ga_package_key' => $offer->ga_package_key,
                    'packages' => $gaPricing
                        ->where('category', 'package')
                        ->map(fn ($pricing) => [
                            'key' => $pricing->key,
                            'label' => $pricing->label,
                            'price_eur' => $pricing->price_eur,
                        ])
                        ->values()
                        ->all(),
                ] : null,
            ],
        ]);
    }
}
