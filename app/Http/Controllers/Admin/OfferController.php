<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOfferPriceRequest;
use App\Mail\CalculationResultMail;
use App\Models\Offer;
use App\Services\OfferBuilderService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
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
                    'net_total_eur' => $offer->net_total_eur,
                    'base_price_eur' => $offer->base_price_eur,
                    'price_on_request' => $offer->base_price_eur === null,
                    'created_at' => optional($offer->created_at)->toIso8601String(),
                    'accepted_at' => optional($offer->accepted_at)->toIso8601String(),
                    'customer' => [
                        'name' => $offer->customer?->name,
                        'email' => $offer->customer?->email,
                        'billing_name' => $offer->customer?->billing_name,
                        'billing_company' => $offer->customer?->billing_company,
                        'billing_email' => $offer->customer?->billing_email,
                        'billing_street' => $offer->customer?->billing_street,
                        'billing_zip' => $offer->customer?->billing_zip,
                        'billing_city' => $offer->customer?->billing_city,
                    ],
                    'property_type' => $offer->propertyType?->label,
                    'public_url' => route('offers.public.show', $offer->view_token),
                ];
            });

        return Inertia::render('Admin/Offers/Index', [
            'offers' => $offers,
        ]);
    }

    public function updatePrice(UpdateOfferPriceRequest $request, OfferBuilderService $service, Offer $offer)
    {
        $validated = $request->validated();
        $priceInput = $validated['price'] ?? null;
        $price = $priceInput !== null ? (int) round($priceInput) : null;

        $offer->loadMissing(['calculation.propertyType', 'customer']);

        $offer->base_price_eur = $price;
        $offer->save();

        $updatedOffer = $service->updatePackage(
            $offer->fresh(['calculation.propertyType', 'customer']),
            $offer->ga_package_key
        );

        $emailSent = false;

        if ($price !== null) {
            if ($contactEmail = $this->resolveContactEmail($updatedOffer)) {
                Mail::to($contactEmail)->send(new CalculationResultMail($updatedOffer->calculation, $updatedOffer));
                $emailSent = true;
            }
        }

        $message = $emailSent
            ? __('Preis gespeichert und erneut per E-Mail versendet.')
            : __('Preis gespeichert.');

        return redirect()->back()->with('success', $message);
    }

    private function resolveContactEmail(Offer $offer): ?string
    {
        return Arr::get($offer->input_snapshot, 'customer.email')
            ?? Arr::get($offer->input_snapshot, 'calculation_inputs.contact.email')
            ?? $offer->customer?->email
            ?? null;
    }
}
