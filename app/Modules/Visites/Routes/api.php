<?php

use App\Modules\Visites\Http\Controllers\VisiteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [VisiteController::class, 'index']);

    Route::middleware('role:locataire')->group(function () {
        Route::post('/', [VisiteController::class, 'store']);
    });

    Route::patch('/{visite}/status', [VisiteController::class, 'updateStatus']);
});
