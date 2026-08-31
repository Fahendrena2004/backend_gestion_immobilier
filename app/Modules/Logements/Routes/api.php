<?php

use Illuminate\Support\Facades\Route;

// Logements Routes (Fikarohana, Quartiers Fianarantsoa, Sary, Equipements)
Route::get('/', function () {
    return response()->json(['message' => 'List logements']);
});
Route::get('/quartiers', function () {
    return response()->json(['message' => 'List quartiers Fianarantsoa']);
});
Route::get('/{id}', function ($id) {
    return response()->json(['message' => "Logement detail: $id"]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/', function () {
        return response()->json(['message' => 'Create logement']);
    });
    Route::put('/{id}', function ($id) {
        return response()->json(['message' => "Update logement: $id"]);
    });
    Route::delete('/{id}', function ($id) {
        return response()->json(['message' => "Delete logement: $id"]);
    });
    Route::post('/{id}/photos', function ($id) {
        return response()->json(['message' => "Upload photo for logement: $id"]);
    });
});
