<?php
/**
 * Quick test for geocoding fix
 */

// Test geocoding directly
$ch = curl_init();
$url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode("Delhi, India") . "&limit=1";

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'RouteIQ/2.0 (Multi-Modal Routing)',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Error: $curlError\n";
echo "Response: " . substr($response, 0, 200) . "...\n";

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    if ($data && !empty($data)) {
        echo "\n✅ Geocoding SUCCESS\n";
        echo "Location: " . $data[0]['display_name'] . "\n";
        echo "Coords: " . $data[0]['lat'] . ", " . $data[0]['lon'] . "\n";
    } else {
        echo "\n❌ No results returned\n";
    }
} else {
    echo "\n❌ Request failed\n";
}
?>
