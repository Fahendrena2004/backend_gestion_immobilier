<?php

use Illuminate\Support\Facades\Route;

// Finances Routes (Factures, Paiements, Quittances)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/factures', function () {
        return response()->json(['message' => 'List factures']);
    });
    Route::post('/paiements', function () {
        return response()->json(['message' => 'Submit paiement with preuve']);
    });
    Route::patch('/paiements/{id}/valider', function ($id) {
        return response()->json(['message' => "Valider paiement: $id"]);
    });
    Route::get('/quittances/{id}/pdf', function ($id) {
        return response()->json(['message' => "Download quittance PDF: $id"]);
    });
});
