<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        /** @var LengthAwarePaginator $offers */
        $offers = Offer::query()
            ->with(['customer', 'propertyType'])
            ->latest('created_at')
            ->paginate($perPage)
            ->through(function (Offer $offer) {
                return [
                    'id' => $offer->id,
                    'number' => $offer->number,
                    'status' => $offer->status,
                    'gross_total_eur' => $offer->gross_total_eur,
                    'created_at' => optional($offer->created_at)->toIso8601String(),
                    'accepted_at' => optional($offer->accepted_at)->toIso8601String(),
                    'customer' => [
                        'name' => $offer->customer?->name,
                        'email' => $offer->customer?->email,
                    ],
                    'property_type' => $offer->propertyType?->label,
                    'public_url' => route('offers.public.show', $offer->view_token),
                ];
            });

        return Inertia::render('Admin/Offers/Index', [
            'offers' => $offers,
        ]);
    }
}
