<?php

use Illuminate\Support\Facades\Route;

// Auth Routes (Login, Register, Logout, Refresh)
Route::post('/register', function () {
    return response()->json(['message' => 'Register endpoint']);
});
Route::post('/login', function () {
    return response()->json(['message' => 'Login endpoint']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function () {
        return response()->json(['message' => 'Logout endpoint']);
    });
    Route::get('/me', function () {
        return response()->json(['user' => request()->user()]);
    });
});
