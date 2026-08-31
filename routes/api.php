<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| B-TECH API Routes (Modular Architecture)
|--------------------------------------------------------------------------
| Fianarantsoa Housing Rental Platform - Modular Route Loader
*/

$modules = [
    'auth'           => 'Auth',
    'users'          => 'Users',
    'logements'      => 'Logements',
    'demandes'       => 'Demandes',
    'visites'        => 'Visites',
    'locations'      => 'Locations',
    'finances'       => 'Finances',
    'administration' => 'Administration',
    'notifications'  => 'Notifications',
];

foreach ($modules as $prefix => $moduleName) {
    $routeFile = app_path("Modules/{$moduleName}/Routes/api.php");
    if (file_exists($routeFile)) {
        Route::prefix($prefix)->group($routeFile);
    }
}
