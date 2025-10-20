<?php

namespace App\Http\Controllers;

use App\Models\ContactSetting;
use App\Models\PropertyType;
use App\Models\RenovationCategory;
use App\Models\RenovationExtentWeight;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function index()
    {
        $timeWindowOptions = collect([
            ['key' => 'nicht', 'label' => __('Keine Sanierung')],
            ['key' => 'bis_5', 'label' => __('In den letzten 5 Jahren')],
            ['key' => 'bis_10', 'label' => __('In den letzten 5–10 Jahren')],
            ['key' => 'bis_15', 'label' => __('In den letzten 10–15 Jahren')],
            ['key' => 'bis_20', 'label' => __('In den letzten 15–20 Jahren')],
            ['key' => 'ueber_20', 'label' => __('Vor mehr als 20 Jahren')],
            ['key' => 'weiss_nicht', 'label' => __('Weiß nicht')],
        ])->sortBy('label')->values()->all();

        $propertyTypes = PropertyType::orderBy('label')->get()->map(fn ($type) => [
            'id' => $type->id,
            'key' => $type->key,
            'label' => $type->label,
            'gnd' => $type->gnd,
            'price_standard_eur' => $type->price_standard_eur,
            'request_only' => $type->request_only,
        ])->values();

        $renovationCategories = RenovationCategory::with('timeFactors')->orderBy('id')->get()->map(fn ($category) => [
            'key' => $category->key,
            'label' => $category->label,
            'max_points' => (float) $category->max_points,
            'time_factors' => $category->timeFactors
                ->map(fn ($factor) => [
                    'key' => $factor->time_window_key,
                    'factor' => (float) $factor->factor,
                ])->values(),
        ])->values();

        $extentOptions = RenovationExtentWeight::orderBy('extent_percent')->get()->map(fn ($weight) => [
            'value' => (int) $weight->extent_percent,
            'label' => sprintf('%d %%', $weight->extent_percent),
            'weight' => (float) $weight->weight,
        ])->values()->prepend([
            'value' => 0,
            'label' => __('Keine Sanierung'),
            'weight' => 0.0,
        ])->values();

        $contactSettings = ContactSetting::values([
            'support_email',
            'support_phone',
            'support_phone_display',
            'support_name',
        ]);

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'propertyTypes' => $propertyTypes,
            'renovationCategories' => $renovationCategories,
            'timeWindowOptions' => $timeWindowOptions,
            'extentOptions' => $extentOptions,
            'contactSettings' => $contactSettings,
        ]);
    }
}
