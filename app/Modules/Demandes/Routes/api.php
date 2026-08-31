<?php

use Illuminate\Support\Facades\Route;

// Demandes Routes (Fangatahana trano avy amin'ny mpanofa)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'List demandes']);
    });
    Route::post('/', function () {
        return response()->json(['message' => 'Create demande']);
    });
    Route::patch('/{id}/status', function ($id) {
        return response()->json(['message' => "Update demande status: $id"]);
    });
});
