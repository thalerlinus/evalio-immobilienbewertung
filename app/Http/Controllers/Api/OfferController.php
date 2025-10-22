<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOfferRequest;
use App\Services\OfferBuilderService;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function store(CreateOfferRequest $request, OfferBuilderService $service): JsonResponse
    {
        $offer = $service->create($request->validated());

        return response()->json([
            'data' => [
                'id' => $offer->id,
                'number' => $offer->number,
                'view_token' => $offer->view_token,
                'status' => $offer->status,
                'calculation_id' => $offer->calculation_id,
                'calculation_public_ref' => $offer->calculation?->public_ref,
                'customer' => $offer->customer ? [
                    'id' => $offer->customer->id,
                    'name' => $offer->customer->name,
                    'email' => $offer->customer->email,
                    'phone' => $offer->customer->phone,
                ] : null,
                'property_type' => $offer->calculation?->propertyType ? [
                    'key' => $offer->calculation->propertyType->key,
                    'label' => $offer->calculation->propertyType->label,
                ] : null,
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
                'notes' => $offer->notes,
                'created_at' => $offer->created_at,
            ],
        ], 201);
    }
}
