<?php

use App\Modules\Finances\Http\Controllers\FactureController;
use App\Modules\Finances\Http\Controllers\ModePaiementController;
use App\Modules\Finances\Http\Controllers\PaiementController;
use App\Modules\Finances\Http\Controllers\QuittanceController;
use Illuminate\Support\Facades\Route;

// Finances Routes (Factures, Paiements, Quittances)
Route::middleware('auth:sanctum')->group(function () {
    // Factures
    Route::get('/factures', [FactureController::class, 'index']);

    // Paiements
    Route::get('/paiements', [PaiementController::class, 'index']);
    Route::post('/paiements', [PaiementController::class, 'store'])
        ->middleware('role:locataire');
    Route::patch('/paiements/{id}/valider', [PaiementController::class, 'valider'])
        ->middleware('role:admin');

    // Quittances
    Route::get('/quittances/{id}/pdf', [QuittanceController::class, 'downloadPdf']);

    // Modes de paiement (lecture publique pour tous les connectés — le locataire
    // en a besoin pour déclarer un paiement ; gestion réservée à l'admin)
    Route::get('/modes-paiement', [ModePaiementController::class, 'index']);

    // Modes de paiement (admin)
    Route::middleware('role:admin')->prefix('modes-paiement')->group(function () {
        Route::post('/', [ModePaiementController::class, 'store']);
        Route::patch('/{mode}', [ModePaiementController::class, 'update']);
        Route::delete('/{mode}', [ModePaiementController::class, 'destroy']);
    });
});
