<?php
/**
 * Seed the geocoding cache with common Indian cities
 * Usage: php seed_geocache.php
 */

require_once __DIR__ . '/public/db.php';

$cities = [
    // Major cities
    'Delhi' => ['lat' => 28.6139, 'lng' => 77.2090],
    'Mumbai' => ['lat' => 19.0760, 'lng' => 72.8777],
    'Bangalore' => ['lat' => 12.9716, 'lng' => 77.5946],
    'Hyderabad' => ['lat' => 17.3850, 'lng' => 78.4744],
    'Pune' => ['lat' => 18.5204, 'lng' => 73.8567],
    'Chennai' => ['lat' => 13.0827, 'lng' => 80.2707],
    'Kolkata' => ['lat' => 22.5726, 'lng' => 88.3639],
    'Jaipur' => ['lat' => 26.9124, 'lng' => 75.7873],
    'Ahmedabad' => ['lat' => 23.0225, 'lng' => 72.5479],
    'Lucknow' => ['lat' => 26.8467, 'lng' => 80.9462],
    'Chandigarh' => ['lat' => 30.7333, 'lng' => 76.7794],
    'Bhopal' => ['lat' => 23.1815, 'lng' => 79.9864],
    'Indore' => ['lat' => 22.7196, 'lng' => 75.8577],
    'Visakhapatnam' => ['lat' => 17.6869, 'lng' => 83.2185],
    'Surat' => ['lat' => 21.1458, 'lng' => 72.1554],
    'Kochi' => ['lat' => 9.9312, 'lng' => 76.2673],
    'Thiruvananthapuram' => ['lat' => 8.5241, 'lng' => 76.9366],
    
    // Popular tourist and second-tier cities
    'Agra' => ['lat' => 27.1767, 'lng' => 78.0081],
    'Goa' => ['lat' => 15.2993, 'lng' => 73.8243],
    'Varanasi' => ['lat' => 25.3176, 'lng' => 82.9789],
    'Udaipur' => ['lat' => 24.5854, 'lng' => 73.7125],
    'Jodhpur' => ['lat' => 26.2389, 'lng' => 73.0243],
    'Pushkar' => ['lat' => 26.4923, 'lng' => 74.6011],
    'Rishikesh' => ['lat' => 30.0893, 'lng' => 78.2679],
    'Shimla' => ['lat' => 31.7771, 'lng' => 77.1770],
    'Manali' => ['lat' => 32.2396, 'lng' => 77.1887],
    'Leh' => ['lat' => 34.1526, 'lng' => 77.5771],
    'Srinagar' => ['lat' => 34.0837, 'lng' => 74.7973],
    'Darjeeling' => ['lat' => 27.0410, 'lng' => 88.2663],
    'Gangtok' => ['lat' => 27.5330, 'lng' => 88.6109],
    
    // Andhra Pradesh cities (Including Mangalagiri)
    'Mangalagiri' => ['lat' => 16.4669, 'lng' => 80.4864],
    'Vijayawada' => ['lat' => 16.5062, 'lng' => 80.6480],
    'Vikarabad' => ['lat' => 17.3667, 'lng' => 78.1333],
    'Guntur' => ['lat' => 16.3067, 'lng' => 80.4365],
    'Tirupati' => ['lat' => 13.1939, 'lng' => 79.8941],
    'Nellore' => ['lat' => 14.4426, 'lng' => 79.9864],
    
    // Maharashtra
    'Nagpur' => ['lat' => 21.1458, 'lng' => 79.0882],
    'Aurangabad' => ['lat' => 19.8762, 'lng' => 75.3433],
    'Nashik' => ['lat' => 19.9975, 'lng' => 73.7898],
    'Kolhapur' => ['lat' => 16.7050, 'lng' => 73.7421],
    
    // Telangana
    'Warangal' => ['lat' => 17.9689, 'lng' => 79.5941],
    'Nizamabad' => ['lat' => 18.6725, 'lng' => 78.1271],
    
    // Karnataka
    'Mysore' => ['lat' => 12.2958, 'lng' => 76.6394],
    'Belgaum' => ['lat' => 15.8681, 'lng' => 74.5041],
    'Hubli' => ['lat' => 15.3647, 'lng' => 75.3647],
    'Mangalore' => ['lat' => 12.8628, 'lng' => 74.8354],
    
    // Tamil Nadu
    'Madurai' => ['lat' => 9.9252, 'lng' => 78.1198],
    'Coimbatore' => ['lat' => 11.0081, 'lng' => 76.8956],
    'Salem' => ['lat' => 11.6643, 'lng' => 78.1460],
    'Trichy' => ['lat' => 10.7905, 'lng' => 78.7047],
    
    // Rajasthan
    'Kota' => ['lat' => 25.2183, 'lng' => 75.8648],
    'Bikaner' => ['lat' => 28.0229, 'lng' => 71.8315],
    'Ajmer' => ['lat' => 26.4499, 'lng' => 74.6399],
    
    // Uttar Pradesh
    'Kanpur' => ['lat' => 26.4499, 'lng' => 80.3319],
    'Ghaziabad' => ['lat' => 28.6692, 'lng' => 77.4538],
    'Noida' => ['lat' => 28.5355, 'lng' => 77.3910],
    'Mathura' => ['lat' => 27.4924, 'lng' => 77.6737],
    
    // Haryana
    'Gurgaon' => ['lat' => 28.4595, 'lng' => 77.0266],
    'Faridabad' => ['lat' => 28.4089, 'lng' => 77.3178],
    'Hisar' => ['lat' => 29.1487, 'lng' => 75.7362],
];

try {
    $db = get_db_connection();
    
    $inserted = 0;
    $skipped = 0;
    
    foreach ($cities as $city => $coords) {
        $queryHash = md5(strtolower($city));
        
        // Check if already cached
        $stmt = $db->prepare("SELECT query_hash FROM cache_geocoding WHERE query_hash = ?");
        $stmt->execute([$queryHash]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $skipped++;
            echo "⏭️  $city (already cached)\n";
            continue;
        }
        
        // Insert into cache
        $stmt = $db->prepare("
            INSERT INTO cache_geocoding (query_hash, query_text, lat, lng, display_name, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $queryHash,
            $city,
            $coords['lat'],
            $coords['lng'],
            "$city, India"
        ]);
        
        $inserted++;
        echo "✅ $city ({$coords['lat']}, {$coords['lng']})\n";
    }
    
    echo "\n✨ Geocache seeded successfully!\n";
    echo "📊 Inserted: $inserted, Skipped: $skipped\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
