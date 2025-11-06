<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\ContactSettingController as AdminContactSettingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DiscountCodeController as AdminDiscountCodeController;
use App\Http\Controllers\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\Admin\PropertyTypeController as AdminPropertyTypeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OfferPublicController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Legal Pages
Route::get('/datenschutz', function () {
    return Inertia::render('Legal/Privacy');
})->name('legal.privacy');

Route::get('/widerrufsbelehrung', function () {
    return Inertia::render('Legal/Revocation');
})->name('legal.revocation');

Route::get('/agb', function () {
    return Inertia::render('Legal/Terms');
})->name('legal.terms');

Route::get('/impressum', function () {
    return Inertia::render('Legal/Imprint');
})->name('legal.imprint');

Route::get('/angebote/{token}', [OfferPublicController::class, 'show'])
    ->name('offers.public.show');

Route::post('/angebote/{token}/confirm', [OfferPublicController::class, 'confirm'])
    ->name('offers.public.confirm');

Route::post('/angebote/{token}/package', [OfferPublicController::class, 'updatePackage'])
    ->name('offers.public.package');

Route::post('/angebote/{token}/discount', [OfferPublicController::class, 'applyDiscount'])
    ->name('offers.public.discount');

Route::post('/angebote/{token}/billing-address', [OfferPublicController::class, 'updateBillingAddress'])
    ->name('offers.public.billing-address');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/values', [AdminPropertyTypeController::class, 'index'])->name('values.index');
        Route::get('/settings', [AdminContactSettingController::class, 'index'])->name('settings.index');
        Route::put('/contact-settings/{contactSetting}', [AdminContactSettingController::class, 'update'])
            ->name('contact-settings.update');
        Route::post('/property-types', [AdminPropertyTypeController::class, 'store'])
            ->name('property-types.store');
        Route::put('/property-types/{propertyType}', [AdminPropertyTypeController::class, 'update'])
            ->name('property-types.update');
        Route::delete('/property-types/{propertyType}', [AdminPropertyTypeController::class, 'destroy'])
            ->name('property-types.destroy');
        Route::put('/account/email', [AdminAccountController::class, 'updateEmail'])->name('account.email.update');
        Route::put('/account/password', [AdminAccountController::class, 'updatePassword'])->name('account.password.update');
        Route::get('/offers', [AdminOfferController::class, 'index'])->name('offers.index');
        Route::put('/offers/{offer}/price', [AdminOfferController::class, 'updatePrice'])->name('offers.price.update');
        Route::resource('discount-codes', AdminDiscountCodeController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    });

require __DIR__.'/auth.php';
