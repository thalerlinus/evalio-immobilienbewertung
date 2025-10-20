<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PropertyTypeController extends Controller
{
    public function index(): Response
    {
        $propertyTypes = PropertyType::query()
            ->orderBy('label')
            ->get()
            ->map(fn (PropertyType $type) => [
                'id' => $type->id,
                'key' => $type->key,
                'label' => $type->label,
                'gnd' => $type->gnd,
                'price_standard_eur' => $type->price_standard_eur,
                'request_only' => $type->request_only,
                'updated_at' => optional($type->updated_at)->toIso8601String(),
            ])->values();

        return Inertia::render('Admin/Values/Index', [
            'propertyTypes' => $propertyTypes,
        ]);
    }

    public function update(Request $request, PropertyType $propertyType): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'gnd' => ['nullable', 'integer', 'min:0', 'max:200'],
            'price_standard_eur' => ['nullable', 'integer', 'min:0'],
            'request_only' => ['required', 'boolean'],
        ]);

        $propertyType->fill($validated);
        $propertyType->save();

        return back()->with(
            'success',
            __('Immobilienart ":label" wurde aktualisiert.', ['label' => $propertyType->label])
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', 'unique:property_types,key'],
            'gnd' => ['nullable', 'integer', 'min:0', 'max:200'],
            'price_standard_eur' => ['nullable', 'integer', 'min:0'],
            'request_only' => ['nullable', 'boolean'],
        ]);

        $key = $validated['key'] ?? null;
        $label = trim($validated['label']);

        if (! $key) {
            $baseKey = Str::slug($label, '_');
            if (! $baseKey) {
                $baseKey = Str::random(6);
            }

            $key = $baseKey;
            $suffix = 1;

            while (PropertyType::where('key', $key)->exists()) {
                $key = $baseKey.'_'.$suffix;
                $suffix++;
            }
        }

        if ($key) {
            $key = Str::lower($key);
        }

        $propertyType = PropertyType::create([
            'label' => $label,
            'key' => $key,
            'gnd' => $validated['gnd'] ?? null,
            'price_standard_eur' => $validated['price_standard_eur'] ?? null,
            'request_only' => (bool) ($validated['request_only'] ?? false),
        ]);

        return redirect()
            ->route('admin.values.index')
            ->with('success', __('Immobilienart ":label" wurde angelegt.', ['label' => $propertyType->label]));
    }

    public function destroy(PropertyType $propertyType): RedirectResponse
    {
        if ($propertyType->offers()->exists() || $propertyType->calculations()->exists()) {
            return back()->with(
                'error',
                __('Immobilienart ":label" kann nicht gelöscht werden, da noch Angebote oder Berechnungen darauf verweisen.', [
                    'label' => $propertyType->label,
                ])
            );
        }

        $label = $propertyType->label;
        $propertyType->delete();

        return redirect()
            ->route('admin.values.index')
            ->with('success', __('Immobilienart ":label" wurde gelöscht.', ['label' => $label]));
    }
}
