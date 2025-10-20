<?php

use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\RndCalculationController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('rnd')->group(function () {
    Route::get('meta', [RndCalculationController::class, 'meta'])->name('api.rnd.meta');
    Route::post('calculate', [RndCalculationController::class, 'store'])->name('api.rnd.calculate');
});

Route::middleware('api')->post('offers', [OfferController::class, 'store'])->name('api.offers.store');
