<?php
function testNominatim($q) {
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($q);
    if (!function_exists('curl_init')) { echo "cURL missing\n"; return; }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RouteIQ/1.0 (test@example.com)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP: $httpCode\n";
    echo $resp ? substr($resp,0,500) : "(no response)";
    echo "\n";
}

testNominatim('Delhi');
