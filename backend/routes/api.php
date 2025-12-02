<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\CostController;
use App\Http\Controllers\Api\PoiController;
use App\Http\Controllers\Api\OptimizeController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\RecommendController;

// ============================================================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================================================

// Authentication endpoints
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Health check
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// ============================================================================
// PROTECTED ROUTES (Requires JWT Authentication)
// ============================================================================

Route::middleware('auth:api')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('user.me');
    });

    // Route Calculation (Rate limit: 30 requests per minute)
    Route::prefix('route')->middleware('throttle:30,1')->group(function () {
        Route::post('/calc', [RouteController::class, 'calc'])->name('route.calc');
        Route::post('/parse-geometry', [RouteController::class, 'parseGeometry'])->name('route.parse');
    });

    // Cost Calculation (Rate limit: 30 requests per minute)
    Route::prefix('cost')->middleware('throttle:30,1')->group(function () {
        Route::post('/calculate', [CostController::class, 'calculateCost'])->name('cost.calculate');
        Route::post('/preview', [CostController::class, 'previewCost'])->name('cost.preview');
        Route::post('/recommend', [CostController::class, 'recommend'])->name('cost.recommend');
    });

    // Recommendations (Rate limit: 30 requests per minute)
    Route::post('/recommend', [RecommendController::class, 'recommend'])->middleware('throttle:30,1')->name('recommend');

    // Multi-stop Optimization (Rate limit: 20 requests per minute)
    Route::prefix('optimize')->middleware('throttle:20,1')->group(function () {
        Route::post('/multi-stop', [OptimizeController::class, 'multiStop'])->name('optimize.multiStop');
    });

    // POI Management
    Route::prefix('pois')->group(function () {
        Route::get('/', [PoiController::class, 'index'])->name('pois.index')->withoutMiddleware('auth:api');
        Route::get('/{id}', [PoiController::class, 'show'])->name('pois.show')->withoutMiddleware('auth:api');
        Route::post('/', [PoiController::class, 'store'])->name('pois.store');
        Route::put('/{id}', [PoiController::class, 'update'])->name('pois.update');
        Route::delete('/{id}', [PoiController::class, 'destroy'])->name('pois.destroy');
        Route::post('/near-route', [PoiController::class, 'nearRoute'])->name('pois.nearRoute');
    });

    // Trip Management
    Route::prefix('trips')->group(function () {
        Route::get('/', [TripController::class, 'listTrips'])->name('trips.list');
        Route::post('/', [TripController::class, 'saveTrip'])->name('trips.save');
        Route::get('/{id}', [TripController::class, 'getTrip'])->name('trips.get');
        Route::delete('/{id}', [TripController::class, 'deleteTrip'])->name('trips.delete');
    });

    // Analytics (Rate limit: 10 requests per minute)
    Route::prefix('analytics')->middleware('throttle:10,1')->group(function () {
        Route::get('/summary', [AnalyticsController::class, 'summary'])->name('analytics.summary');
    });
});
