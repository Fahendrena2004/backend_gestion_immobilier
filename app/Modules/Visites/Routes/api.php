<?php

use Illuminate\Support\Facades\Route;

// Visites Routes (Fandaharam-potoana sy fitsidihana trano)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'List visites']);
    });
    Route::post('/', function () {
        return response()->json(['message' => 'Schedule a visit']);
    });
    Route::patch('/{id}/status', function ($id) {
        return response()->json(['message' => "Update visit status: $id"]);
    });
});
