<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactSettingController extends Controller
{
    public function index(): Response
    {
        $settings = ContactSetting::query()
            ->orderBy('key')
            ->get()
            ->map(fn (ContactSetting $setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'label' => $setting->label,
                'type' => $setting->type,
                'value' => $setting->value,
                'updated_at' => optional($setting->updated_at)->toIso8601String(),
            ])->values();

        return Inertia::render('Admin/Settings/Index', [
            'contactSettings' => $settings,
        ]);
    }

    public function update(Request $request, ContactSetting $contactSetting): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['nullable', 'string', 'max:1000'],
        ]);

        $contactSetting->value = $validated['value'];
        $contactSetting->save();

        return back()->with(
            'success',
            __('Kontakt-Einstellung ":key" wurde aktualisiert.', ['key' => $contactSetting->key])
        );
    }
}
