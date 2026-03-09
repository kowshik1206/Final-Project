<?php
// Test geocoding for Mangalagiri

require_once __DIR__ . '/public/db.php';

// Test data
$testCities = ['Mangalagiri', 'Delhi', 'Mumbai', 'Hyderabad'];

try {
    $db = get_db_connection();
    
    echo "Testing Geocoding Cache:\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    foreach ($testCities as $city) {
        $queryHash = md5(strtolower($city));
        $stmt = $db->prepare("SELECT query_text, lat, lng, display_name FROM cache_geocoding WHERE query_hash = ?");
        $stmt->execute([$queryHash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "✅ $city\n";
            echo "   Lat: {$result['lat']}, Lng: {$result['lng']}\n";
            echo "   Name: {$result['display_name']}\n\n";
        } else {
            echo "❌ $city - NOT FOUND IN CACHE\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
