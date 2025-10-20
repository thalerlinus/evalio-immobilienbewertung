<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateRndRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currentYear = (int) now()->year + 1;

        return [
            'property_type_key' => ['required', 'string', 'exists:property_types,key'],
            'gnd_override' => ['nullable', 'integer', 'min:1', 'max:200'],
            'baujahr' => ['required', 'integer', 'between:1800,' . $currentYear],
            'anschaffungsjahr' => ['required', 'integer', 'gte:baujahr', 'between:1800,' . $currentYear],
            'steuerjahr' => ['required', 'integer', 'gte:anschaffungsjahr', 'between:1800,' . $currentYear],
            'bauweise' => ['nullable', 'string', 'in:massiv,holz,unbekannt'],
            'eigennutzung' => ['nullable', 'boolean'],
            'renovations' => ['nullable', 'array'],
            'renovations.*.category_key' => ['required_with:renovations', 'string', 'exists:renovation_categories,key'],
            'renovations.*.time_window_key' => ['nullable', 'string', 'in:nicht,bis_5,bis_10,bis_15,bis_20,ueber_20,weiss_nicht'],
            'renovations.*.extent_percent' => ['nullable', 'integer', 'in:0,20,40,60,80,100'],
            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.zip' => ['nullable', 'string', 'max:20'],
            'address.city' => ['nullable', 'string', 'max:255'],
            'address.country' => ['nullable', 'string', 'max:2'],
            'contact' => ['required', 'array'],
            'contact.email' => ['required', 'email:rfc,dns', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +()\-\/]*$/'],
            'contact.name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
