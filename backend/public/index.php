<?php
require_once __DIR__ . '/../cors.php';

// Safely get REQUEST_URI only in web context
$path = '/';
if (isset($_SERVER["REQUEST_URI"])) {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
}

// Define API routes mapping
$routes = [
    '/api/health'           => 'health.php',         // Phase 4: Health endpoint
    '/api/plan-route'       => 'plan_route.php',
    '/api/plan-route-train' => 'plan_route_train.php',  // NEW: Train-specific routing
    '/api/plan-route-bus'   => 'plan_route_bus.php',    // NEW: Bus-specific routing
    '/api/plan-route-v2'    => 'plan_route_v2.php',  // Multi-modal routing
    '/api/vehicle-profiles' => 'vehicle_profiles.php',
    '/api/fuel-calc'        => 'fuel_calc.php',
    '/api/ev/calculate'     => 'ev_calculate.php',    // EV calculator (pure math, no DB)
    '/api/bus/calculate'    => 'bus_calculate.php',   // Bus calculator (station-based, train-like)
    '/api/train/calculate'  => 'train_calculate.php', // Train calculator (realistic, v2.2)
    '/api/flight/calculate' => 'flight_calculate.php', // Flight calculator (new)
    '/api/recommend-mode'   => 'recommend_mode.php',  // Mode recommendation engine
    '/api/pois-for-route'   => 'pois-for-route.php',
    '/api/calc-cost'        => 'calc_cost.php',
    '/api/trips'            => 'trips.php',
    '/api/trips/optimize-stops' => 'optimize_route.php',
    '/api/geocode'          => 'geocode.php',
    '/api/health'           => 'health.php',         // Phase 4: Health endpoint
    '/api/trip-preferences' => 'trip_preferences.php',
    '/api/analytics'        => 'analytics.php',
    '/api/login'            => 'auth/login.php',
    '/api/register'         => 'auth/register.php',
    '/api/me'               => 'auth/me.php',
    '/api/logout'           => 'auth/logout.php'
];

// Check if route exists
if (array_key_exists($path, $routes)) {
    $file = __DIR__ . '/../api/' . $routes[$path];
    if (file_exists($file)) {
        require_once $file;
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'API file not found: ' . $routes[$path]]);
        exit;
    }
}

echo json_encode([
    'ok' => false,
    'message' => 'Invalid endpoint: ' . $path
]);
