<?php

use App\Modules\Logements\Http\Controllers\EquipementController;
use App\Modules\Logements\Http\Controllers\LogementController;
use App\Modules\Logements\Http\Controllers\PhotoController;
use Illuminate\Support\Facades\Route;

// --- Routes publiques ---
Route::get('/', [LogementController::class, 'index']);
Route::get('/quartiers', [LogementController::class, 'quartiers']);

// --- Routes propriétaire ---
Route::middleware(['auth:sanctum', 'role:proprietaire'])->group(function () {
    Route::get('/mes-annonces', [LogementController::class, 'mesAnnonces']);
    Route::post('/', [LogementController::class, 'store']);

    Route::middleware('logement.owner')->group(function () {
        Route::put('/{logement}', [LogementController::class, 'update']);
        Route::delete('/{logement}', [LogementController::class, 'destroy']);

        Route::post('/{logement}/photos', [PhotoController::class, 'store']);
        Route::delete('/{logement}/photos/{photo}', [PhotoController::class, 'destroy']);
        Route::patch('/{logement}/photos/{photo}/principale', [PhotoController::class, 'setPrincipale']);

        Route::put('/{logement}/equipements', [EquipementController::class, 'sync']);
    });
});

// --- Route détail (après les routes statiques) ---
Route::get('/{logement}', [LogementController::class, 'show']);
