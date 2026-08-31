<?php

use Illuminate\Support\Facades\Route;

// Notifications Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'List user notifications']);
    });
    Route::patch('/{id}/read', function ($id) {
        return response()->json(['message' => "Mark notification as read: $id"]);
    });
});
