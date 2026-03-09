<?php
/**
 * seed_temples.php
 * Seed major temples across India into POIs database
 * Focus on main and priority temples (religious significance, pilgrimage sites)
 */

require_once __DIR__ . '/public/db.php';

$db = get_db_connection();

// List of major temples in India with coordinates (lat, lng)
$temples = [
    // NORTH INDIA
    ['name' => 'Varanasi Kashi Vishwanath Temple', 'city' => 'Varanasi', 'lat' => 25.3240, 'lng' => 82.9876, 'importance' => 'major'],
    ['name' => 'Varanasi Annapurna Temple', 'city' => 'Varanasi', 'lat' => 25.3256, 'lng' => 82.9856, 'importance' => 'major'],
    ['name' => 'Varanasi Sankat Mochan Temple', 'city' => 'Varanasi', 'lat' => 25.3346, 'lng' => 82.9950, 'importance' => 'major'],
    
    ['name' => 'Ayodhya Ram Mandir', 'city' => 'Ayodhya', 'lat' => 26.8125, 'lng' => 82.0017, 'importance' => 'major'],
    ['name' => 'Ayodhya Hanuman Garhi', 'city' => 'Ayodhya', 'lat' => 26.8093, 'lng' => 82.0029, 'importance' => 'major'],
    
    ['name' => 'Mathura Krishna Janmabhoomi', 'city' => 'Mathura', 'lat' => 27.4924, 'lng' => 77.6737, 'importance' => 'major'],
    ['name' => 'Mathura Dwarkadhish Temple', 'city' => 'Mathura', 'lat' => 27.4934, 'lng' => 77.6849, 'importance' => 'major'],
    
    ['name' => 'Haridwar Har Ki Pauri', 'city' => 'Haridwar', 'lat' => 29.9433, 'lng' => 78.1637, 'importance' => 'major'],
    ['name' => 'Haridwar Mansa Devi Temple', 'city' => 'Haridwar', 'lat' => 29.9551, 'lng' => 78.1728, 'importance' => 'major'],
    
    ['name' => 'Rishikesh Laxmanjhula Temples', 'city' => 'Rishikesh', 'lat' => 30.0922, 'lng' => 78.4428, 'importance' => 'major'],
    ['name' => 'Rishikesh Ram Mandir', 'city' => 'Rishikesh', 'lat' => 30.1056, 'lng' => 78.4450, 'importance' => 'major'],
    
    ['name' => 'Delhi Rajarani Temple', 'city' => 'Delhi', 'lat' => 28.6369, 'lng' => 77.1995, 'importance' => 'major'],
    ['name' => 'Delhi Birla Mandir', 'city' => 'Delhi', 'lat' => 28.5933, 'lng' => 77.2197, 'importance' => 'major'],
    ['name' => 'Delhi Kalkaji Mandir', 'city' => 'Delhi', 'lat' => 28.5236, 'lng' => 77.2499, 'importance' => 'major'],
    
    ['name' => 'Ujjain Mahakal Temple', 'city' => 'Ujjain', 'lat' => 23.1815, 'lng' => 75.7845, 'importance' => 'major'],
    ['name' => 'Ujjain Hari Garh Temple', 'city' => 'Ujjain', 'lat' => 23.1876, 'lng' => 75.7912, 'importance' => 'major'],
    
    ['name' => 'Omkareshwar Temple', 'city' => 'Omkareshwar', 'lat' => 22.3565, 'lng' => 75.6734, 'importance' => 'major'],
    
    // SOUTH INDIA
    ['name' => 'Tirupati Venkateswara Temple', 'city' => 'Tirupati', 'lat' => 13.1827, 'lng' => 79.8254, 'importance' => 'major'],
    ['name' => 'Tirupati Padmavati Temple', 'city' => 'Tirupati', 'lat' => 13.2165, 'lng' => 79.8456, 'importance' => 'major'],
    
    ['name' => 'Chidambaram Nataraja Temple', 'city' => 'Chidambaram', 'lat' => 11.3948, 'lng' => 79.6955, 'importance' => 'major'],
    
    ['name' => 'Madurai Meenakshi Temple', 'city' => 'Madurai', 'lat' => 9.9252, 'lng' => 78.1198, 'importance' => 'major'],
    ['name' => 'Madurai Mariamman Temple', 'city' => 'Madurai', 'lat' => 9.9189, 'lng' => 78.1210, 'importance' => 'major'],
    
    ['name' => 'Rameswaram Ramanathaswamy Temple', 'city' => 'Rameswaram', 'lat' => 9.2868, 'lng' => 79.3119, 'importance' => 'major'],
    
    ['name' => 'Kanyakumari Bhagavathi Temple', 'city' => 'Kanyakumari', 'lat' => 8.0883, 'lng' => 77.5385, 'importance' => 'major'],
    
    ['name' => 'Thiruvananthapuram Padmanabhaswamy Temple', 'city' => 'Thiruvananthapuram', 'lat' => 8.4855, 'lng' => 76.9379, 'importance' => 'major'],
    
    ['name' => 'Kochi Mata Mandir', 'city' => 'Kochi', 'lat' => 9.9312, 'lng' => 76.2673, 'importance' => 'major'],
    ['name' => 'Kochi Ernakulatappan Temple', 'city' => 'Kochi', 'lat' => 9.9768, 'lng' => 76.2560, 'importance' => 'major'],
    
    ['name' => 'Coimbatore Arulmigu Avinashi Vinayagar Temple', 'city' => 'Coimbatore', 'lat' => 11.0026, 'lng' => 76.7055, 'importance' => 'major'],
    
    // WEST INDIA
    ['name' => 'Dwarka Dwarkadhish Temple', 'city' => 'Dwarka', 'lat' => 22.2381, 'lng' => 68.9679, 'importance' => 'major'],
    
    ['name' => 'Somnath Temple', 'city' => 'Somnath', 'lat' => 20.8856, 'lng' => 71.4049, 'importance' => 'major'],
    
    ['name' => 'Shirdi Sai Baba Temple', 'city' => 'Shirdi', 'lat' => 19.7676, 'lng' => 75.4849, 'importance' => 'major'],
    
    ['name' => 'Nashik Trimbakeshwar Temple', 'city' => 'Nashik', 'lat' => 19.9165, 'lng' => 73.8371, 'importance' => 'major'],
    ['name' => 'Nashik Panchavati Temples', 'city' => 'Nashik', 'lat' => 19.9989, 'lng' => 73.7850, 'importance' => 'major'],
    
    // EAST INDIA
    ['name' => 'Puri Jagannath Temple', 'city' => 'Puri', 'lat' => 19.8136, 'lng' => 85.8273, 'importance' => 'major'],
    
    ['name' => 'Kolkata Kali Temple', 'city' => 'Kolkata', 'lat' => 22.6026, 'lng' => 88.3629, 'importance' => 'major'],
    ['name' => 'Kolkata Jain Temple', 'city' => 'Kolkata', 'lat' => 22.5727, 'lng' => 88.3456, 'importance' => 'major'],
    
    ['name' => 'Guwahati Kamakhya Temple', 'city' => 'Guwahati', 'lat' => 26.1667, 'lng' => 91.7500, 'importance' => 'major'],
    
    // CENTRAL INDIA
    ['name' => 'Indore Rajwada Temple', 'city' => 'Indore', 'lat' => 22.7196, 'lng' => 75.8577, 'importance' => 'major'],
    ['name' => 'Indore Khanda Mandir', 'city' => 'Indore', 'lat' => 22.7156, 'lng' => 75.8567, 'importance' => 'major'],
    
    ['name' => 'Bhopal Birla Mandir', 'city' => 'Bhopal', 'lat' => 23.1815, 'lng' => 77.4125, 'importance' => 'major'],
    
    // NORTHEAST INDIA
    ['name' => 'Guwahati Bhagsunag Temple', 'city' => 'Guwahati', 'lat' => 26.1456, 'lng' => 91.7389, 'importance' => 'major'],
    
    // HIMALAYAN REGION
    ['name' => 'Shimla Kali Bari Temple', 'city' => 'Shimla', 'lat' => 31.7724, 'lng' => 77.1767, 'importance' => 'major'],
];

echo "🕉️  Starting temple seeding...\n\n";

$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($temples as $temple) {
    try {
        // Check if temple already exists (by name and coordinates)
        $checkStmt = $db->prepare(
            "SELECT id FROM pois WHERE name = ? AND ABS(latitude - ?) < 0.001 AND ABS(longitude - ?) < 0.001"
        );
        $checkStmt->execute([$temple['name'], $temple['lat'], $temple['lng']]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($checkResult) {
            // Temple already exists
            $skipped++;
            echo "⏭️  Skipped: {$temple['name']}\n";
            continue;
        }
        
        // Insert temple
        $stmt = $db->prepare(
            "INSERT INTO pois (name, category, latitude, longitude) VALUES (?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            echo "❌ Prepare error for {$temple['name']}\n";
            $skipped++;
            continue;
        }
        
        $category = 'temple';
        
        if ($stmt->execute([$temple['name'], $category, $temple['lat'], $temple['lng']])) {
            $inserted++;
            echo "✅ Inserted: {$temple['name']} ({$temple['city']})\n";
        } else {
            echo "❌ Insert error for {$temple['name']}\n";
            $skipped++;
        }
        
    } catch (Exception $e) {
        echo "❌ Exception for {$temple['name']}: " . $e->getMessage() . "\n";
        $skipped++;
    }
}

echo "\n";
echo "====================================\n";
echo "✅ Inserted: $inserted temples\n";
echo "⏭️  Skipped: $skipped temples (already exist)\n";
echo "Updated: $updated temples\n";
echo "====================================\n\n";

// Verify total temples in database
$countResult = $db->query("SELECT COUNT(*) as total FROM pois WHERE category = 'temple'");
$countRow = $countResult->fetch(PDO::FETCH_ASSOC);
$totalTemples = $countRow['total'];

echo "📊 Total temples in database: $totalTemples\n";

// Show temple count summary
echo "\n✨ Temple seeding completed!\n";
?>
