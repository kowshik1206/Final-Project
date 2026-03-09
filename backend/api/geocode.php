<?php
/**
 * geocode.php
 * Simple geocoding endpoint using Nominatim
 * Returns lat/lng for a given location name
 */

header('Content-Type: application/json; charset=utf-8');

// Get query parameter
$query = $_GET['q'] ?? '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Query parameter "q" is required'
    ]);
    exit;
}

// City aliases for common Indian city names
$cityAliases = [
    'vizag' => 'Visakhapatnam',
    'vizagpatnam' => 'Visakhapatnam',
    'kolkata' => 'Calcutta',
    'bangalore' => 'Bengaluru',
    'bengaluru' => 'Bengaluru',
    'bombay' => 'Mumbai',
    'pune' => 'Puna',
    'nagpur' => 'Nagpur',
    'coimbatore' => 'Coimbatore',
    'lucknow' => 'Lucknow',
    'kanpur' => 'Kanpur',
    'chandigarh' => 'Chandigarh',
    'bhopal' => 'Bhopal',
    'indore' => 'Indore',
    'ahmedabad' => 'Ahmedabad',
    'surat' => 'Surat',
    'vadodara' => 'Vadodara',
    'jaipur' => 'Jaipur',
    'agra' => 'Agra',
    'varanasi' => 'Varanasi',
    'allahabad' => 'Allahabad',
    'patna' => 'Patna',
    'ranchi' => 'Ranchi',
    'guwahati' => 'Guwahati',
    'shillong' => 'Shillong',
    'kochi' => 'Kochi',
    'thiruvananthapuram' => 'Thiruvananthapuram',
    'trivandrum' => 'Thiruvananthapuram',
    'kottayam' => 'Kottayam',
    'thrissur' => 'Thrissur',
    'kozhikode' => 'Kozhikode',
    'calicut' => 'Kozhikode',
    'kannur' => 'Kannur',
    'kasaragod' => 'Kasaragod'
];

// Apply alias mapping
$normalizedQuery = strtolower(trim($query));
if (isset($cityAliases[$normalizedQuery])) {
    $query = $cityAliases[$normalizedQuery];
}

// Call Nominatim API
$url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($query);
$opts = [
    'http' => [
        'header' => "User-Agent: RouteIQ/1.0\r\n"
    ]
];
$ctx = stream_context_create($opts);

$json = @file_get_contents($url, false, $ctx);

if (!$json) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Geocoding service unavailable'
    ]);
    exit;
}

$data = json_decode($json, true);

if (!$data || empty($data)) {
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'message' => 'Location not found: ' . $query
    ]);
    exit;
}

// Return first result
echo json_encode([
    'ok' => true,
    'lat' => floatval($data[0]['lat']),
    'lng' => floatval($data[0]['lon']),
    'display_name' => $data[0]['display_name'] ?? $query
]);
?>
