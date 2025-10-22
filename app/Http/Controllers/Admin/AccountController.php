<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        if (! empty($validated['name'])) {
            $user->name = $validated['name'];
        }

        // Für Admin-Benutzer setzen wir die E-Mail direkt als verifiziert
        $emailChanged = $user->email !== $validated['email'];
        
        $user->email = $validated['email'];
        
        // Wenn die E-Mail geändert wurde, setze sie direkt als verifiziert
        if ($emailChanged) {
            $user->email_verified_at = now();
        }
        
        $user->save();

        return back()->with('success', __('Zugangsdaten wurden aktualisiert.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('success', __('Das Passwort wurde aktualisiert.'));
    }
}
