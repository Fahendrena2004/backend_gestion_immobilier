<?php

use App\Modules\Locations\Http\Controllers\ContratController;
use App\Modules\Locations\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [LocationController::class, 'index']);

    Route::middleware('role:proprietaire')->group(function () {
        Route::post('/', [LocationController::class, 'store']);
    });

    Route::get('/{id}/contrat-pdf', [ContratController::class, 'generatePdf']);
});
