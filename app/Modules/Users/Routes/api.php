<?php

use Illuminate\Support\Facades\Route;

// Users Routes (Admin, Propriétaire, Locataire)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'List users']);
    });
    Route::get('/profile', function () {
        return response()->json(['message' => 'User profile']);
    });
    Route::put('/profile', function () {
        return response()->json(['message' => 'Update profile']);
    });
});
