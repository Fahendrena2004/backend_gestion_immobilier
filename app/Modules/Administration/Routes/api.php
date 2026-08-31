<?php

use Illuminate\Support\Facades\Route;

// Administration Routes (Modération, Statistiques & Dashboard)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard-stats', function () {
        return response()->json(['message' => 'Admin dashboard statistics']);
    });
    Route::patch('/logements/{id}/moderation', function ($id) {
        return response()->json(['message' => "Moderate logement: $id"]);
    });
    Route::patch('/users/{id}/status', function ($id) {
        return response()->json(['message' => "Block / Activate user: $id"]);
    });
});
