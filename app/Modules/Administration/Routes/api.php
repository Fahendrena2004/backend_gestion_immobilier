<?php

use App\Modules\Administration\Http\Controllers\DashboardController;
use App\Modules\Administration\Http\Controllers\ModerationController;
use App\Modules\Administration\Http\Controllers\ReferentielController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum', 'role:admin')->group(function () {
    Route::get('/dashboard-stats', [DashboardController::class, 'stats']);
    Route::get('/logements', [ModerationController::class, 'annonces']);
    Route::patch('/logements/{id}/moderation', [ModerationController::class, 'moderateLogement']);
    Route::patch('/users/{id}/status', [ModerationController::class, 'updateUserStatus']);

    Route::get('/referentiels/en-attente', [ReferentielController::class, 'enAttente']);
    Route::patch('/referentiels/{ressource}/{id}/valider', [ReferentielController::class, 'valider']);
    Route::post('/referentiels/{ressource}/{id}/fusionner', [ReferentielController::class, 'fusionner']);
});
