<?php

use Illuminate\Support\Facades\Route;

// Locations Routes (Fifanarahana / Contrats de bail)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'List locations']);
    });
    Route::post('/', function () {
        return response()->json(['message' => 'Create contrat de location']);
    });
    Route::get('/{id}/contrat-pdf', function ($id) {
        return response()->json(['message' => "Generate / Download contrat PDF: $id"]);
    });
});
