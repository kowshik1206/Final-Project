<?php
/**
 * smoke_test.php - Automated checks for RouteIQ
 * Usage: php smoke_test.php --mode=quick
 *        php smoke_test.php --mode=full
 */

require_once 'db.php';

class SmokeTest {
    private $mode = 'quick';
    private $passed = 0;
    private $failed = 0;
    private $tests = [];
    
    public function __construct($mode = 'quick') {
        $this->mode = $mode;
    }
    
    public function run() {
        echo "🔥 RouteIQ Smoke Tests - Mode: {$this->mode}\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $this->test_db_connection();
        $this->test_tables_exist();
        $this->test_plan_route_endpoint();
        $this->test_save_trip_endpoint();
        $this->test_contact_endpoint();
        
        if ($this->mode === 'full') {
            $this->test_list_trips_endpoint();
            $this->test_upload_endpoint();
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Results: ✅ {$this->passed} passed | ❌ {$this->failed} failed\n";
        echo str_repeat("=", 60) . "\n\n";
        
        return $this->failed === 0 ? 0 : 1;
    }
    
    private function test_db_connection() {
        echo "🔗 Testing database connection... ";
        try {
            $db = get_db_connection();
            echo "✅ PASS\n";
            $this->passed++;
        } catch (Exception $e) {
            echo "❌ FAIL: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
    
    private function test_tables_exist() {
        echo "📋 Testing required tables... ";
        try {
            $db = get_db_connection();
            $tables = ['users', 'trips', 'bookings', 'uploads', 'contact_messages'];
            
            foreach ($tables as $table) {
                $stmt = $db->query("SELECT 1 FROM $table LIMIT 1");
                if (!$stmt) throw new Exception("Table $table not found");
            }
            
            echo "✅ PASS\n";
            $this->passed++;
        } catch (Exception $e) {
            echo "❌ FAIL: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
    
    private function test_plan_route_endpoint() {
        echo "🗺️  Testing plan_route endpoint (Delhi→Mumbai)... ";
        
        // Delhi: 77.1025, 28.7041; Mumbai: 72.8777, 19.0760
        $url = "http://localhost/RouteIQ/backend/public/plan_route.php?from=77.1025,28.7041&to=72.8777,19.0760";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data['ok'] === true && isset($data['route']['distance_m'])) {
                echo "✅ PASS (distance: {$data['route']['distance_m']}m)\n";
                $this->passed++;
            } else {
                echo "❌ FAIL: Invalid response format\n";
                $this->failed++;
            }
        } else {
            echo "❌ FAIL: HTTP $httpCode\n";
            $this->failed++;
        }
    }
    
    private function test_save_trip_endpoint() {
        echo "💾 Testing save_trip endpoint... ";
        
        $tripData = [
            'title' => 'Smoke Test Trip',
            'from_lat' => 28.7041,
            'from_lng' => 77.1025,
            'to_lat' => 19.0760,
            'to_lng' => 72.8777,
            'distance_m' => 1400000,
            'duration_s' => 50400,
            'geometry_geojson' => [
                'type' => 'LineString',
                'coordinates' => [[77.1025, 28.7041], [72.8777, 19.0760]]
            ]
        ];
        
        $ch = curl_init('http://localhost/RouteIQ/backend/public/save_trip.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($tripData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 201) {
            $data = json_decode($response, true);
            if ($data['ok'] === true && isset($data['trip_id'])) {
                echo "✅ PASS (trip_id: {$data['trip_id']})\n";
                $this->passed++;
            } else {
                echo "❌ FAIL: Invalid response\n";
                $this->failed++;
            }
        } else {
            echo "❌ FAIL: HTTP $httpCode\n";
            $this->failed++;
        }
    }
    
    private function test_contact_endpoint() {
        echo "📧 Testing contact endpoint... ";
        
        $contactData = [
            'name' => 'Smoke Test User',
            'email' => 'test@example.com',
            'message' => 'This is an automated smoke test message from the test suite.'
        ];
        
        $ch = curl_init('http://localhost/RouteIQ/backend/public/contact.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($contactData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 201) {
            $data = json_decode($response, true);
            if ($data['ok'] === true && isset($data['message_id'])) {
                echo "✅ PASS (message_id: {$data['message_id']})\n";
                $this->passed++;
            } else {
                echo "❌ FAIL: Invalid response\n";
                $this->failed++;
            }
        } else {
            echo "❌ FAIL: HTTP $httpCode\n";
            $this->failed++;
        }
    }
    
    private function test_list_trips_endpoint() {
        echo "📜 Testing list_trips endpoint... ";
        
        $ch = curl_init('http://localhost/RouteIQ/backend/public/list_trips.php?limit=10');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if ($data['ok'] === true && isset($data['trips'])) {
                echo "✅ PASS (trips: " . count($data['trips']) . ")\n";
                $this->passed++;
            } else {
                echo "❌ FAIL: Invalid response\n";
                $this->failed++;
            }
        } else {
            echo "❌ FAIL: HTTP $httpCode\n";
            $this->failed++;
        }
    }
    
    private function test_upload_endpoint() {
        echo "📤 Testing upload endpoint... ";
        
        // Create a test image file
        $tmpFile = sys_get_temp_dir() . '/test_image_' . uniqid() . '.png';
        $img = imagecreate(10, 10);
        imagepng($img, $tmpFile);
        imagedestroy($img);
        
        $ch = curl_init('http://localhost/RouteIQ/backend/public/upload_profile.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => new CURLFile($tmpFile, 'image/png', 'test.png')],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        @unlink($tmpFile);
        
        if ($httpCode === 201) {
            $data = json_decode($response, true);
            if ($data['ok'] === true && isset($data['upload_id'])) {
                echo "✅ PASS (upload_id: {$data['upload_id']})\n";
                $this->passed++;
            } else {
                echo "❌ FAIL: Invalid response\n";
                $this->failed++;
            }
        } else {
            echo "❌ FAIL: HTTP $httpCode\n";
            $this->failed++;
        }
    }
}

// Parse command line arguments
$mode = 'quick';
if (isset($argv[1]) && strpos($argv[1], '--mode=') === 0) {
    $mode = substr($argv[1], 7);
}

$tester = new SmokeTest($mode);
exit($tester->run());
