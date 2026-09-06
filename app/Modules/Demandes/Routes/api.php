<?php

use App\Modules\Demandes\Http\Controllers\DemandeLocationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [DemandeLocationController::class, 'index']);

    Route::middleware('role:locataire')->group(function () {
        Route::post('/', [DemandeLocationController::class, 'store']);
    });

    Route::patch('/{demande}/status', [DemandeLocationController::class, 'updateStatus']);
});
