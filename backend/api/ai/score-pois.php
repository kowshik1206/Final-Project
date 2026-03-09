<?php
/**
 * ========== AI POI SCORING: ADVISORY ONLY ==========
 * 
 * AI-powered POI ranking endpoint with strict advisory-only rules.
 * 
 * CRITICAL RULES:
 * 1. AI receives ONLY pre-filtered POIs (never removes POIs)
 * 2. AI never changes feasibility (backend rules override AI)
 * 3. AI only ranks and scores - all hard constraints handled by backend
 * 
 * Viva Defense:
 * "AI ranks POIs that have already passed our hard constraints. It cannot
 *  override backend feasibility rules. This ensures deterministic safety
 *  while adding intelligent personalization."
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// ========== CONFIGURATION ==========

// Gemini API configuration
$GEMINI_API_KEY = getenv('GEMINI_API_KEY') ?: 'AIzaSyCgS-5Yw32XXV1MTcBJeBFjiYp8u68S__A';
$GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

// Cache configuration
$CACHE_DIR = __DIR__ . '/../../cache/ai-scores';
$CACHE_TTL = 24 * 60 * 60; // 24 hours

// Create cache directory if not exists
if (!file_exists($CACHE_DIR)) {
    mkdir($CACHE_DIR, 0755, true);
}

// ========== INPUT VALIDATION ==========

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid JSON input'
    ]);
    exit;
}

// Validate candidates (pre-filtered POIs)
$candidates = $input['candidates'] ?? [];
if (!is_array($candidates)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Missing required field: candidates (array of POIs)'
    ]);
    exit;
}

// If no candidates, return empty result (AI cannot create POIs)
if (empty($candidates)) {
    echo json_encode([
        'ok' => true,
        'ranked_pois' => [],
        'cache_hit' => false,
        'note' => 'AI is advisory only. No candidates provided.'
    ]);
    exit;
}

// Extract context
$route = $input['route'] ?? [];
$vehicle = $input['vehicle'] ?? [];
$prefs = $input['prefs'] ?? [];

// ========== CACHING LAYER ==========

/**
 * Generate cache key from request parameters
 * Cache key = MD5(route_id + vehicle_id + poi_ids + prefs)
 */
function generateCacheKey($candidates, $route, $vehicle, $prefs) {
    $poiIds = array_map(fn($p) => $p['id'] ?? '', $candidates);
    sort($poiIds); // Ensure consistent ordering
    
    $cacheData = [
        'route_distance' => $route['distance_km'] ?? 0,
        'vehicle_type' => $vehicle['fuel_type'] ?? 'unknown',
        'poi_ids' => implode(',', $poiIds),
        'prefs' => json_encode($prefs)
    ];
    
    return md5(json_encode($cacheData));
}

/**
 * Get cached AI response if available and not expired
 */
function getCachedResponse($cacheKey, $cacheDir, $cacheTTL) {
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheTime = filemtime($cacheFile);
    if (time() - $cacheTime > $cacheTTL) {
        // Cache expired
        unlink($cacheFile);
        return null;
    }
    
    $cachedData = file_get_contents($cacheFile);
    return json_decode($cachedData, true);
}

/**
 * Save AI response to cache
 */
function saveCachedResponse($cacheKey, $cacheDir, $data) {
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    file_put_contents($cacheFile, json_encode($data));
}

// Check cache
$cacheKey = generateCacheKey($candidates, $route, $vehicle, $prefs);
$cachedResponse = getCachedResponse($cacheKey, $CACHE_DIR, $CACHE_TTL);

if ($cachedResponse) {
    // Cache hit - return cached result
    echo json_encode([
        'ok' => true,
        'ranked_pois' => $cachedResponse['ranked_pois'],
        'cache_hit' => true,
        'note' => 'AI is advisory only. Backend rules override AI.'
    ]);
    exit;
}

// ========== AI SCORING WITH GEMINI ==========

/**
 * Build prompt for Gemini API
 * 
 * AI is advisory only - it ranks pre-filtered POIs based on user preferences.
 * Backend has already validated feasibility and distance constraints.
 */
function buildAIPrompt($candidates, $route, $vehicle, $prefs) {
    $routeInfo = "Route distance: " . ($route['distance_km'] ?? 'unknown') . " km";
    $vehicleInfo = "Vehicle type: " . ($vehicle['fuel_type'] ?? 'unknown');
    
    $prefsInfo = "User preferences:\n";
    $prefsInfo .= "- Comfort mode: " . ($prefs['comfortMode'] ?? 'comfort') . "\n";
    $prefsInfo .= "- Elders count: " . ($prefs['eldersCount'] ?? 0) . "\n";
    $prefsInfo .= "- Temple priority: " . ($prefs['templePriority'] ?? 'normal') . "\n";
    
    $poisList = "POIs to rank (already filtered by backend):\n";
    foreach ($candidates as $idx => $poi) {
        $poisList .= ($idx + 1) . ". " . ($poi['name'] ?? 'Unknown') . 
                     " (" . ($poi['category'] ?? 'unknown') . ")" .
                     " - Distance to route: " . ($poi['distance_to_route_m'] ?? 'unknown') . "m\n";
    }
    
    $prompt = <<<PROMPT
You are a travel assistant helping rank Points of Interest (POIs) for a road trip.

IMPORTANT: These POIs have already been filtered by the backend for feasibility and distance constraints.
Your job is ONLY to rank them based on user preferences. You cannot remove POIs or change feasibility.

$routeInfo
$vehicleInfo

$prefsInfo

$poisList

Task: Rank these POIs from most to least relevant based on the user preferences.
Return ONLY a JSON array of POI IDs in ranked order (most relevant first).

Format: [id1, id2, id3, ...]

Do not include any explanation, just the JSON array.
PROMPT;

    return $prompt;
}

/**
 * Call Gemini API for POI ranking
 */
function callGeminiAPI($prompt, $apiKey, $apiUrl) {
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 500
        ]
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl . '?key=' . $apiKey,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        throw new Exception('Gemini API request failed');
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Invalid Gemini API response');
    }
    
    return $data['candidates'][0]['content']['parts'][0]['text'];
}

/**
 * Parse AI response and rank POIs
 */
function rankPOIsWithAI($candidates, $aiResponse) {
    // Extract JSON array from AI response
    $aiResponse = trim($aiResponse);
    
    // Try to find JSON array in response
    if (preg_match('/\[[\d,\s]+\]/', $aiResponse, $matches)) {
        $rankedIds = json_decode($matches[0], true);
    } else {
        // Fallback: return original order
        return $candidates;
    }
    
    if (!is_array($rankedIds)) {
        return $candidates;
    }
    
    // Create ID to POI map
    $poiMap = [];
    foreach ($candidates as $poi) {
        $poiMap[$poi['id']] = $poi;
    }
    
    // Reorder POIs based on AI ranking
    $rankedPois = [];
    foreach ($rankedIds as $id) {
        if (isset($poiMap[$id])) {
            $rankedPois[] = $poiMap[$id];
            unset($poiMap[$id]);
        }
    }
    
    // Add any remaining POIs (AI might have missed some)
    foreach ($poiMap as $poi) {
        $rankedPois[] = $poi;
    }
    
    return $rankedPois;
}

// ========== EXECUTE AI SCORING ==========

try {
    // Build prompt
    $prompt = buildAIPrompt($candidates, $route, $vehicle, $prefs);
    
    // Call Gemini API
    $aiResponse = callGeminiAPI($prompt, $GEMINI_API_KEY, $GEMINI_API_URL);
    
    // Rank POIs based on AI response
    $rankedPois = rankPOIsWithAI($candidates, $aiResponse);
    
    // Save to cache
    $cacheData = ['ranked_pois' => $rankedPois];
    saveCachedResponse($cacheKey, $CACHE_DIR, $cacheData);
    
    // Return result
    echo json_encode([
        'ok' => true,
        'ranked_pois' => $rankedPois,
        'cache_hit' => false,
        'note' => 'AI is advisory only. Backend rules override AI.'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // AI failure should not break the app - return original order
    echo json_encode([
        'ok' => true,
        'ranked_pois' => $candidates,
        'cache_hit' => false,
        'ai_error' => $e->getMessage(),
        'note' => 'AI ranking failed. Returning original order. AI is advisory only.'
    ], JSON_UNESCAPED_UNICODE);
}
?>
