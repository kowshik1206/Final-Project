<?php
/**
 * seed_trips.php — Clear and populate trips table with sample data including all modes
 */

require_once __DIR__ . '/public/db.php';

try {
    $db = get_db_connection();
    
    // Clear existing trips
    $db->exec("DELETE FROM trips");
    echo "🗑️  Cleared existing trips\n\n";
    
    // Sample trips data with emojis
    $trips = [
        // Car trips
        [
            'title' => '🚗 Bangalore → Mysore',
            'source_name' => 'Bangalore',
            'dest_name' => 'Mysore',
            'selected_mode' => 'car',
            'distance_km' => 145.5,
            'duration_min' => 150,
            'cost_amount' => 2500,
            'origin_lat' => 12.9716,
            'origin_lng' => 77.5946,
            'dest_lat' => 12.2958,
            'dest_lng' => 76.6394,
            'vehicle_json' => json_encode(['name' => 'Hyundai i20', 'fuel_type' => 'Petrol', 'mileage' => 18]),
            'stops_json' => json_encode([['name' => 'Fuel Station', 'lat' => 12.63, 'lng' => 77.12]]),
            'costs_json' => json_encode(['total' => 2500, 'fuel_cost' => 2500, 'mode' => 'car'])
        ],
        [
            'title' => '🚗 Hyderabad → Vijayawada',
            'source_name' => 'Hyderabad',
            'dest_name' => 'Vijayawada',
            'selected_mode' => 'car',
            'distance_km' => 285,
            'duration_min' => 320,
            'cost_amount' => 4200,
            'origin_lat' => 17.3850,
            'origin_lng' => 78.4867,
            'dest_lat' => 16.5062,
            'dest_lng' => 80.6480,
            'vehicle_json' => json_encode(['name' => 'Maruti Swift', 'fuel_type' => 'Petrol', 'mileage' => 20]),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 4200, 'fuel_cost' => 4200, 'mode' => 'car'])
        ],
        [
            'title' => '🚗 Pune → Nashik Road',
            'source_name' => 'Pune',
            'dest_name' => 'Nashik',
            'selected_mode' => 'car',
            'distance_km' => 210,
            'duration_min' => 280,
            'cost_amount' => 3500,
            'origin_lat' => 18.5204,
            'origin_lng' => 73.8567,
            'dest_lat' => 19.9975,
            'dest_lng' => 73.7898,
            'vehicle_json' => json_encode(['name' => 'Tata Nexon', 'fuel_type' => 'Petrol', 'mileage' => 18]),
            'stops_json' => json_encode([['name' => 'Rest Stop', 'lat' => 19.25, 'lng' => 73.82]]),
            'costs_json' => json_encode(['total' => 3500, 'fuel_cost' => 3500, 'mode' => 'car'])
        ],
        [
            'title' => '🚗 Kochi → Thiruvananthapuram',
            'source_name' => 'Kochi',
            'dest_name' => 'Thiruvananthapuram',
            'selected_mode' => 'car',
            'distance_km' => 220,
            'duration_min' => 280,
            'cost_amount' => 3500,
            'origin_lat' => 9.9312,
            'origin_lng' => 76.2673,
            'dest_lat' => 8.5241,
            'dest_lng' => 76.9366,
            'vehicle_json' => json_encode(['name' => 'Toyota Fortuner', 'fuel_type' => 'Diesel', 'mileage' => 14]),
            'stops_json' => json_encode([['name' => 'Restaurant', 'lat' => 9.2, 'lng' => 76.6]]),
            'costs_json' => json_encode(['total' => 3500, 'fuel_cost' => 3500, 'mode' => 'car'])
        ],
        [
            'title' => '🚗 Chennai → Coimbatore',
            'source_name' => 'Chennai',
            'dest_name' => 'Coimbatore',
            'selected_mode' => 'car',
            'distance_km' => 430,
            'duration_min' => 480,
            'cost_amount' => 6800,
            'origin_lat' => 13.0827,
            'origin_lng' => 80.2707,
            'dest_lat' => 11.0026,
            'dest_lng' => 76.9025,
            'vehicle_json' => json_encode(['name' => 'Mahindra XUV500', 'fuel_type' => 'Diesel', 'mileage' => 15]),
            'stops_json' => json_encode([['name' => 'Toll', 'lat' => 12.0, 'lng' => 78.5], ['name' => 'Fuel', 'lat' => 11.5, 'lng' => 77.2]]),
            'costs_json' => json_encode(['total' => 6800, 'fuel_cost' => 6800, 'mode' => 'car'])
        ],
        [
            'title' => '🚗 Guwahati → Shillong',
            'source_name' => 'Guwahati',
            'dest_name' => 'Shillong',
            'selected_mode' => 'car',
            'distance_km' => 100,
            'duration_min' => 140,
            'cost_amount' => 1800,
            'origin_lat' => 26.1445,
            'origin_lng' => 91.7362,
            'dest_lat' => 25.5788,
            'dest_lng' => 91.8933,
            'vehicle_json' => json_encode(['name' => 'Tata Nexon', 'fuel_type' => 'Petrol', 'mileage' => 17]),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 1800, 'fuel_cost' => 1800, 'mode' => 'car'])
        ],
        [
            'title' => '🚗 Kolkata → Darjeeling',
            'source_name' => 'Kolkata',
            'dest_name' => 'Darjeeling',
            'selected_mode' => 'car',
            'distance_km' => 650,
            'duration_min' => 720,
            'cost_amount' => 9500,
            'origin_lat' => 22.5726,
            'origin_lng' => 88.3639,
            'dest_lat' => 27.0360,
            'dest_lng' => 88.2601,
            'vehicle_json' => json_encode(['name' => 'Hyundai Creta', 'fuel_type' => 'Diesel', 'mileage' => 16]),
            'stops_json' => json_encode([['name' => 'Siliguri', 'lat' => 26.5, 'lng' => 88.4]]),
            'costs_json' => json_encode(['total' => 9500, 'fuel_cost' => 9500, 'mode' => 'car'])
        ],
        
        // Bus trips
        [
            'title' => '🚌 Pune → Nashik Express',
            'source_name' => 'Pune',
            'dest_name' => 'Nashik',
            'selected_mode' => 'bus',
            'distance_km' => 210,
            'duration_min' => 280,
            'cost_amount' => 850,
            'origin_lat' => 18.5204,
            'origin_lng' => 73.8567,
            'dest_lat' => 19.9975,
            'dest_lng' => 73.7898,
            'vehicle_json' => json_encode(['name' => 'MSRTC Bus', 'fuel_type' => 'Diesel']),
            'stops_json' => json_encode([['name' => 'Bus Stop', 'lat' => 19.25, 'lng' => 73.82]]),
            'costs_json' => json_encode(['total' => 850, 'fuel_cost' => 0, 'mode' => 'bus'])
        ],
        [
            'title' => '🚌 Lucknow → Kanpur',
            'source_name' => 'Lucknow',
            'dest_name' => 'Kanpur',
            'selected_mode' => 'bus',
            'distance_km' => 68,
            'duration_min' => 90,
            'cost_amount' => 350,
            'origin_lat' => 26.8467,
            'origin_lng' => 80.9462,
            'dest_lat' => 26.4499,
            'dest_lng' => 80.3319,
            'vehicle_json' => json_encode(['name' => 'Deluxe Coach', 'fuel_type' => 'Diesel']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 350, 'fuel_cost' => 0, 'mode' => 'bus'])
        ],
        [
            'title' => '🚌 Indore → Ujjain',
            'source_name' => 'Indore',
            'dest_name' => 'Ujjain',
            'selected_mode' => 'bus',
            'distance_km' => 55,
            'duration_min' => 75,
            'cost_amount' => 280,
            'origin_lat' => 22.7196,
            'origin_lng' => 75.8577,
            'dest_lat' => 23.1815,
            'dest_lng' => 75.7731,
            'vehicle_json' => json_encode(['name' => 'Private Coach', 'fuel_type' => 'Diesel']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 280, 'fuel_cost' => 0, 'mode' => 'bus'])
        ],
        [
            'title' => '🚌 Mumbai → Goa',
            'source_name' => 'Mumbai',
            'dest_name' => 'Goa',
            'selected_mode' => 'bus',
            'distance_km' => 580,
            'duration_min' => 720,
            'cost_amount' => 1500,
            'origin_lat' => 19.0760,
            'origin_lng' => 72.8777,
            'dest_lat' => 15.2993,
            'dest_lng' => 73.8243,
            'vehicle_json' => json_encode(['name' => 'Volvo Bus', 'fuel_type' => 'Diesel']),
            'stops_json' => json_encode([['name' => 'Rest Stop', 'lat' => 17.1, 'lng' => 73.0]]),
            'costs_json' => json_encode(['total' => 1500, 'fuel_cost' => 0, 'mode' => 'bus'])
        ],
        
        // Train trips
        [
            'title' => '🚆 Jaipur → Delhi Rajdhani',
            'source_name' => 'Jaipur',
            'dest_name' => 'Delhi',
            'selected_mode' => 'train',
            'distance_km' => 240,
            'duration_min' => 240,
            'cost_amount' => 1200,
            'origin_lat' => 26.9124,
            'origin_lng' => 75.7873,
            'dest_lat' => 28.7041,
            'dest_lng' => 77.1025,
            'vehicle_json' => json_encode(['name' => 'Rajdhani Express', 'fuel_type' => 'Electric']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 1200, 'fuel_cost' => 0, 'mode' => 'train'])
        ],
        [
            'title' => '🚆 Mumbai → Delhi Shatabdi',
            'source_name' => 'Mumbai',
            'dest_name' => 'Delhi',
            'selected_mode' => 'train',
            'distance_km' => 1447,
            'duration_min' => 1440,
            'cost_amount' => 2500,
            'origin_lat' => 19.0760,
            'origin_lng' => 72.8777,
            'dest_lat' => 28.7041,
            'dest_lng' => 77.1025,
            'vehicle_json' => json_encode(['name' => 'Shatabdi Express', 'fuel_type' => 'Diesel']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 2500, 'fuel_cost' => 0, 'mode' => 'train'])
        ],
        [
            'title' => '🚆 Kolkata → Chennai Mail',
            'source_name' => 'Kolkata',
            'dest_name' => 'Chennai',
            'selected_mode' => 'train',
            'distance_km' => 1659,
            'duration_min' => 2880,
            'cost_amount' => 2200,
            'origin_lat' => 22.5726,
            'origin_lng' => 88.3639,
            'dest_lat' => 13.0827,
            'dest_lng' => 80.2707,
            'vehicle_json' => json_encode(['name' => 'Express Mail', 'fuel_type' => 'Diesel']),
            'stops_json' => json_encode([['name' => 'Hyderabad', 'lat' => 17.3, 'lng' => 78.4]]),
            'costs_json' => json_encode(['total' => 2200, 'fuel_cost' => 0, 'mode' => 'train'])
        ],
        
        // Flight trips
        [
            'title' => '✈️ Delhi → Mumbai',
            'source_name' => 'Delhi',
            'dest_name' => 'Mumbai',
            'selected_mode' => 'flight',
            'distance_km' => 1447,
            'duration_min' => 180,
            'cost_amount' => 5500,
            'origin_lat' => 28.7041,
            'origin_lng' => 77.1025,
            'dest_lat' => 19.0760,
            'dest_lng' => 72.8777,
            'vehicle_json' => json_encode(['name' => 'Air India', 'fuel_type' => 'Jet Fuel', 'aircraft' => 'Boeing 737']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 5500, 'fuel_cost' => 0, 'mode' => 'flight'])
        ],
        [
            'title' => '✈️ Mumbai → Bangalore',
            'source_name' => 'Mumbai',
            'dest_name' => 'Bangalore',
            'selected_mode' => 'flight',
            'distance_km' => 845,
            'duration_min' => 120,
            'cost_amount' => 4200,
            'origin_lat' => 19.0760,
            'origin_lng' => 72.8777,
            'dest_lat' => 12.9716,
            'dest_lng' => 77.5946,
            'vehicle_json' => json_encode(['name' => 'IndiGo', 'fuel_type' => 'Jet Fuel', 'aircraft' => 'Airbus A320']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 4200, 'fuel_cost' => 0, 'mode' => 'flight'])
        ],
        [
            'title' => '✈️ Delhi → Goa',
            'source_name' => 'Delhi',
            'dest_name' => 'Goa',
            'selected_mode' => 'flight',
            'distance_km' => 1775,
            'duration_min' => 200,
            'cost_amount' => 6200,
            'origin_lat' => 28.7041,
            'origin_lng' => 77.1025,
            'dest_lat' => 15.2993,
            'dest_lng' => 73.8243,
            'vehicle_json' => json_encode(['name' => 'SpiceJet', 'fuel_type' => 'Jet Fuel', 'aircraft' => 'Boeing 737']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 6200, 'fuel_cost' => 0, 'mode' => 'flight'])
        ],
        [
            'title' => '✈️ Bangalore → Chennai',
            'source_name' => 'Bangalore',
            'dest_name' => 'Chennai',
            'selected_mode' => 'flight',
            'distance_km' => 280,
            'duration_min' => 90,
            'cost_amount' => 3500,
            'origin_lat' => 12.9716,
            'origin_lng' => 77.5946,
            'dest_lat' => 13.0827,
            'dest_lng' => 80.2707,
            'vehicle_json' => json_encode(['name' => 'GoAir', 'fuel_type' => 'Jet Fuel', 'aircraft' => 'Airbus A320']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 3500, 'fuel_cost' => 0, 'mode' => 'flight'])
        ],
        [
            'title' => '✈️ Kolkata → Delhi',
            'source_name' => 'Kolkata',
            'dest_name' => 'Delhi',
            'selected_mode' => 'flight',
            'distance_km' => 1200,
            'duration_min' => 150,
            'cost_amount' => 4800,
            'origin_lat' => 22.5726,
            'origin_lng' => 88.3639,
            'dest_lat' => 28.7041,
            'dest_lng' => 77.1025,
            'vehicle_json' => json_encode(['name' => 'Vistara', 'fuel_type' => 'Jet Fuel', 'aircraft' => 'Airbus A320']),
            'stops_json' => json_encode([]),
            'costs_json' => json_encode(['total' => 4800, 'fuel_cost' => 0, 'mode' => 'flight'])
        ]
    ];
    
    // Insert trips
    $stmt = $db->prepare("
        INSERT INTO trips 
        (title, source_name, dest_name, selected_mode, distance_km, duration_min, cost_amount, 
         origin_lat, origin_lng, dest_lat, dest_lng, vehicle_json, stops_json, costs_json, created_at) 
        VALUES 
        (:title, :source, :dest, :mode, :distance, :duration, :cost, 
         :orig_lat, :orig_lng, :dest_lat, :dest_lng, :vehicle, :stops, :costs, NOW())
    ");
    
    $count = 0;
    foreach ($trips as $trip) {
        $stmt->execute([
            ':title' => $trip['title'],
            ':source' => $trip['source_name'],
            ':dest' => $trip['dest_name'],
            ':mode' => $trip['selected_mode'],
            ':distance' => $trip['distance_km'],
            ':duration' => $trip['duration_min'],
            ':cost' => $trip['cost_amount'],
            ':orig_lat' => $trip['origin_lat'],
            ':orig_lng' => $trip['origin_lng'],
            ':dest_lat' => $trip['dest_lat'],
            ':dest_lng' => $trip['dest_lng'],
            ':vehicle' => $trip['vehicle_json'],
            ':stops' => $trip['stops_json'],
            ':costs' => $trip['costs_json']
        ]);
        $count++;
    }
    
    echo "✅ Successfully inserted $count sample trips!\n\n";
    
    // Show summary
    $result = $db->query("SELECT COUNT(*) as total FROM trips");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total trips in database: " . $row['total'] . "\n";
    
    // Show breakdown by mode
    $modes = $db->query("SELECT selected_mode, COUNT(*) as count FROM trips GROUP BY selected_mode ORDER BY count DESC");
    echo "\n📈 Trips by mode:\n";
    $modeEmojis = ['car' => '🚗', 'bus' => '🚌', 'train' => '🚆', 'flight' => '✈️'];
    while ($mode = $modes->fetch(PDO::FETCH_ASSOC)) {
        $emoji = $modeEmojis[$mode['selected_mode']] ?? '•';
        echo "   $emoji " . ucfirst($mode['selected_mode']) . ": " . $mode['count'] . "\n";
    }
    
    echo "\n✨ Sample trips ready for testing!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

