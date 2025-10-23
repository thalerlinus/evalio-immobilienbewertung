<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiscountCodeController extends Controller
{
    public function index(): Response
    {
        $discountCodes = DiscountCode::query()
            ->orderBy('code')
            ->get()
            ->map(fn (DiscountCode $code) => [
                'id' => $code->id,
                'code' => $code->code,
                'label' => $code->label,
                'percent' => $code->percent,
                'is_active' => $code->is_active,
                'created_at' => optional($code->created_at)->toIso8601String(),
                'updated_at' => optional($code->updated_at)->toIso8601String(),
            ])->values();

        return Inertia::render('Admin/DiscountCodes/Index', [
            'discountCodes' => $discountCodes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DiscountCode::create($validated);

        return redirect()
            ->route('admin.discount-codes.index')
            ->with('success', __('Rabattcode wurde angelegt.'));
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $validated = $this->validatePayload($request, $discountCode->id);

        $discountCode->fill($validated);
        $discountCode->save();

        return back()->with('success', __('Rabattcode "'.$discountCode->code.'" wurde aktualisiert.'));
    }

    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        $code = $discountCode->code;
        $discountCode->delete();

        return redirect()
            ->route('admin.discount-codes.index')
            ->with('success', __('Rabattcode "'.$code.'" wurde gelöscht.'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $codeId = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('discount_codes', 'code')->ignore($codeId)],
            'label' => ['nullable', 'string', 'max:255'],
            'percent' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['code'] = mb_strtoupper($validated['code']);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        return $validated;
    }
}
