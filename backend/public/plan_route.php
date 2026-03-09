<?php
/**
 * plan_route.php - Route planning endpoint
 * GET /plan_route.php?from=lon,lat&to=lon,lat
 * Uses OSRM (Open Source Routing Machine) for public routing
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$from = $_GET['from'] ?? $_POST['from'] ?? null;
$to = $_GET['to'] ?? $_POST['to'] ?? null;

if (!$from || !$to) {
    json_response(['ok' => false, 'error' => 'Missing from or to coordinates'], 400);
}

// Parse coordinates (lon,lat format)
$fromParts = explode(',', $from);
$toParts = explode(',', $to);

if (count($fromParts) !== 2 || count($toParts) !== 2) {
    json_response(['ok' => false, 'error' => 'Invalid coordinate format. Use lon,lat'], 400);
}

$fromLon = (float)$fromParts[0];
$fromLat = (float)$fromParts[1];
$toLon = (float)$toParts[0];
$toLat = (float)$toParts[1];

if (!validate_coordinates($fromLat, $fromLon) || !validate_coordinates($toLat, $toLon)) {
    json_response(['ok' => false, 'error' => 'Invalid coordinate values'], 400);
}

// Call OSRM public API (driving mode)
$osrmUrl = "https://router.project-osrm.org/route/v1/driving/$fromLon,$fromLat;$toLon,$toLat?overview=full&geometries=geojson&steps=false";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $osrmUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'RouteIQ/1.0',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    json_response(['ok' => false, 'error' => 'OSRM routing service unavailable'], 503);
}

$routeData = json_decode($response, true);

if (!$routeData || $routeData['code'] !== 'Ok') {
    json_response(['ok' => false, 'error' => 'No route found between coordinates'], 400);
}

if (empty($routeData['routes'])) {
    json_response(['ok' => false, 'error' => 'No route found between coordinates'], 400);
}

$route = $routeData['routes'][0];
$distance = $route['distance'] ?? 0; // meters
$duration = $route['duration'] ?? 0; // seconds
$geometry = $route['geometry'] ?? null;

json_response([
    'ok' => true,
    'route' => [
        'distance_m' => (int)$distance,
        'duration_s' => (int)$duration,
        'geometry' => $geometry,
        'from' => ['lat' => $fromLat, 'lng' => $fromLon],
        'to' => ['lat' => $toLat, 'lng' => $toLon],
    ]
], 200);
