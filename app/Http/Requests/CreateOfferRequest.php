<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calculation_public_ref' => ['required', 'string', 'exists:calculations,public_ref'],
            'customer' => ['required', 'array'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:50'],
            'customer.street' => ['nullable', 'string', 'max:255'],
            'customer.zip' => ['nullable', 'string', 'max:20'],
            'customer.city' => ['nullable', 'string', 'max:255'],
            'customer.country' => ['nullable', 'string', 'max:2'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['string', 'exists:ga_pricings,key'],
            'ga_package_key' => ['nullable', 'string', 'exists:ga_pricings,key'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
