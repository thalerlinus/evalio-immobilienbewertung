<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferBillingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $billing = $this->input('billing_address', []);

        if (! is_array($billing)) {
            return;
        }

        $normalized = [
            'name' => isset($billing['name']) ? trim((string) $billing['name']) : null,
            'company' => isset($billing['company']) ? trim((string) $billing['company']) : null,
            'email' => isset($billing['email']) ? trim((string) $billing['email']) : null,
            'street' => isset($billing['street']) ? trim((string) $billing['street']) : null,
            'zip' => isset($billing['zip']) ? preg_replace('/\s+/', '', (string) $billing['zip']) : null,
            'city' => isset($billing['city']) ? trim((string) $billing['city']) : null,
        ];

        if ($normalized['company'] === '') {
            $normalized['company'] = null;
        }

        $this->merge([
            'billing_address' => $normalized,
        ]);
    }

    public function rules(): array
    {
        return [
            'billing_address' => ['required', 'array'],
            'billing_address.name' => ['required', 'string', 'max:255'],
            'billing_address.company' => ['nullable', 'string', 'max:255'],
            'billing_address.email' => ['required', 'string', 'email:filter', 'max:255'],
            'billing_address.street' => ['required', 'string', 'max:255'],
            'billing_address.zip' => ['required', 'string', 'regex:/^[0-9]{5}$/'],
            'billing_address.city' => ['required', 'string', 'max:255'],
        ];
    }
}
