<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSetting;
use App\Models\Offer;
use App\Models\PropertyType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $recentOffers = Offer::query()
            ->with(['customer', 'propertyType'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Offer $offer) => [
                'id' => $offer->id,
                'number' => $offer->number,
                'status' => $offer->status,
                'created_at' => optional($offer->created_at)->toIso8601String(),
                'accepted_at' => optional($offer->accepted_at)->toIso8601String(),
                'gross_total_eur' => $offer->gross_total_eur,
                'customer' => [
                    'name' => $offer->customer?->name,
                    'email' => $offer->customer?->email,
                ],
                'property_type' => $offer->propertyType?->label,
                'public_url' => route('offers.public.show', $offer->view_token),
            ])->values();

        $stats = [
            'total_offers' => Offer::count(),
            'confirmed_offers' => Offer::whereNotNull('accepted_at')->count(),
            'property_types' => PropertyType::count(),
            'contact_settings' => ContactSetting::count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'recentOffers' => $recentOffers,
            'stats' => $stats,
        ]);
    }
}
