<?php
// backend/import_osm.php
// Usage: php import_osm.php --lat=19.07 --lon=72.87 --radius=5
require_once __DIR__ . '/db.php';

// Parse arguments
$options = getopt("", ["lat:", "lon:", "radius:"]);
$lat = $options['lat'] ?? 19.0760; // Default: Mumbai
$lon = $options['lon'] ?? 72.8777;
$radiusKm = $options['radius'] ?? 5;
$radiusMeters = $radiusKm * 1000;

echo "Starting Import for Location: $lat, $lon (Radius: {$radiusKm}km)\n";

// 1. Construct Overpass QL Query
$query = "
[out:json][timeout:25];
(
  node[\"amenity\"=\"fuel\"](around:$radiusMeters, $lat, $lon);
  way[\"amenity\"=\"fuel\"](around:$radiusMeters, $lat, $lon);
  
  node[\"amenity\"=\"charging_station\"](around:$radiusMeters, $lat, $lon);
  way[\"amenity\"=\"charging_station\"](around:$radiusMeters, $lat, $lon);
  
  node[\"amenity\"=\"hospital\"](around:$radiusMeters, $lat, $lon);
  way[\"amenity\"=\"hospital\"](around:$radiusMeters, $lat, $lon);
  
  node[\"amenity\"=\"restaurant\"](around:$radiusMeters, $lat, $lon);
  way[\"amenity\"=\"restaurant\"](around:$radiusMeters, $lat, $lon);
  
  node[\"highway\"=\"toll_gantry\"](around:$radiusMeters, $lat, $lon);
);
out center;
";

// 2. Fetch from Overpass API
echo "Fetching data from OpenStreetMap...\n";
$endpoint = "https://overpass-api.de/api/interpreter";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($query));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'RouteIQ-Importer/1.0');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    die("Error fetching data from Overpass. HTTP Code: $httpCode\n");
}

$data = json_decode($response, true);
$elements = $data['elements'] ?? [];
echo "Fetched " . count($elements) . " items. Processing...\n";

// 3. Process and Upsert logic
// Ensure global conn
if (!isset($conn)) global $conn;

$count = 0;
$stmt = $conn->prepare("
    INSERT INTO pois (external_id, source, category, name, brand, operator, phone, website, tags, latitude, longitude, confidence)
    VALUES (?, 'osm', ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.8)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        brand = VALUES(brand),
        phone = VALUES(phone),
        tags = VALUES(tags),
        updated_at = NOW()
");

foreach ($elements as $el) {
    if (!isset($el['tags'])) continue;
    $tags = $el['tags'];
    
    // Determine category
    $cat = 'poi';
    if (($tags['amenity'] ?? '') === 'fuel') $cat = 'fuel';
    elseif (($tags['amenity'] ?? '') === 'charging_station') $cat = 'charger'; // normalized for frontend
    elseif (($tags['amenity'] ?? '') === 'hospital') $cat = 'hospital';
    elseif (($tags['amenity'] ?? '') === 'restaurant') $cat = 'restaurant';
    elseif (($tags['highway'] ?? '') === 'toll_gantry') $cat = 'toll';
    
    // Extract Metadata
    $name = $tags['name'] ?? $tags['name:en'] ?? ucfirst($cat);
    $brand = $tags['brand'] ?? $tags['operator'] ?? null;
    $operator = $tags['operator'] ?? null;
    $phone = $tags['phone'] ?? $tags['contact:phone'] ?? null;
    $website = $tags['website'] ?? $tags['contact:website'] ?? null;
    
    // If brand is missing but name looks like a brand (simple heuristic for fuel)
    if (!$brand && $cat === 'fuel') {
         if (stripos($name, 'Indian Oil') !== false) $brand = 'Indian Oil';
         if (stripos($name, 'HP') !== false) $brand = 'Hindustan Petroleum';
         if (stripos($name, 'Bharat') !== false) $brand = 'Bharat Petroleum';
         if (stripos($name, 'Shell') !== false) $brand = 'Shell';
    }

    // Coordinates (use center for ways)
    $pLat = $el['lat'] ?? $el['center']['lat'] ?? 0;
    $pLon = $el['lon'] ?? $el['center']['lon'] ?? 0;
    
    $externalId = "osm:" . $el['type'] . "/" . $el['id'];
    $jsonTags = json_encode($tags);
    
    $stmt->bind_param("ssssssssdd", 
        $externalId, $cat, $name, $brand, $operator, $phone, $website, $jsonTags, $pLat, $pLon
    );
    
    if ($stmt->execute()) {
        $count++;
    } else {
        // echo "Failed: " . $stmt->error . "\n";
    }
}

echo "Successfully imported/updated $count POIs.\n";
?>
