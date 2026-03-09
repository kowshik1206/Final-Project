<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\PoiController;
use App\Http\Controllers\Api\RouteController;

// Auth Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Public routing endpoint (no auth required)
Route::post('/plan-route', [RouteController::class, 'planRoute']);

// Protected Routes (requires authentication)
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Trip Routes
    Route::apiResource('trips', TripController::class);

    // POI Routes
    Route::post('/pois-for-route', [PoiController::class, 'fetchForRoute']);
});
