<?php
/**
 * Test script for plan_route_v2.php API
 * Tests all three modes: car, train, flight
 */

function test_route($mode, $source, $destination) {
    echo "\n========== Testing $mode mode: $source → $destination ==========\n";
    
    $url = 'http://localhost:8000/api/plan-route-v2';
    $data = json_encode([
        'source' => $source,
        'destination' => $destination,
        'mode' => $mode
    ]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($response) {
        $result = json_decode($response, true);
        
        if ($result['ok'] ?? false) {
            echo "✅ SUCCESS\n";
            echo "Mode: " . $result['mode'] . "\n";
            echo "Total Distance: " . $result['total_distance_km'] . " km\n";
            echo "Total Duration: " . $result['total_duration_min'] . " min\n";
            echo "Segments: " . count($result['segments']) . "\n\n";
            
            foreach ($result['segments'] as $i => $seg) {
                echo "  Segment " . ($i + 1) . ": " . strtoupper($seg['type']) . "\n";
                echo "    From: " . ($seg['from'] ?? 'N/A') . "\n";
                echo "    To: " . ($seg['to'] ?? 'N/A') . "\n";
                if (isset($seg['label'])) {
                    echo "    Label: " . $seg['label'] . "\n";
                }
                echo "    Distance: " . ($seg['distance_km'] ?? 0) . " km\n";
                echo "    Duration: " . ($seg['duration_min'] ?? 0) . " min\n";
                echo "    Polyline points: " . count($seg['polyline'] ?? []) . "\n\n";
            }
        } else {
            echo "❌ FAILED: " . ($result['message'] ?? 'Unknown error') . "\n";
            print_r($result);
        }
    } else {
        echo "❌ No response from server\n";
    }
    
    echo "================================================\n";
}

// Run tests
echo "Multi-Modal Routing API Tests\n";
echo "==============================\n";

// Test 1: Car mode
test_route('car', 'Delhi', 'Agra');

// Test 2: Train mode
test_route('train', 'Delhi', 'Mumbai');

// Test 3: Flight mode
test_route('flight', 'Delhi', 'Bangalore');

echo "\n✅ All tests complete!\n";
?>
