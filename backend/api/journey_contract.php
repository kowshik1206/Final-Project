<?php
/**
 * PHASE 3: JOURNEY CONTRACT STANDARDIZATION
 * 
 * This file contains the authoritative journey contract definition and validation.
 * Every journey API (Bus, Train, Flight) MUST return this exact shape.
 * 
 * CONTRACT:
 * {
 *   "ok": true,
 *   "mode": "bus|train|flight",
 *   "total_distance_km": number,
 *   "total_duration_min": number,
 *   "cost": number,
 *   "source": "CITY_OR_CODE",
 *   "destination": "CITY_OR_CODE",
 *   "source_coords": { "lat": number, "lng": number },
 *   "destination_coords": { "lat": number, "lng": number },
 *   "segments": [
 *     {
 *       "type": "road|rail|flight",
 *       "label": "Human readable",
 *       "from": "LOCATION_ID",
 *       "to": "LOCATION_ID",
 *       "from_name": "Human readable",
 *       "to_name": "Human readable",
 *       "distance_km": number,
 *       "duration_min": number,
 *       "polyline": [{"lat": number, "lng": number}, ...]
 *     }
 *   ]
 * }
 */

/**
 * Validate a journey response contract.
 * Returns {valid: bool, error: string|null}
 * 
 * CONTRACT INVARIANTS:
 * - total_distance_km ≈ Σ segment.distance_km (tolerance: ±1%)
 * - total_duration_min ≈ Σ segment.duration_min (tolerance: ±5%)
 * - Allowed segment combinations:
 *   - Bus: [road]
 *   - Train: [road, rail, road]
 *   - Flight: [road, flight, road]
 */
function validateJourneyContract($response) {
    // Check basic structure
    if (!is_array($response)) {
        return ['valid' => false, 'error' => 'Response is not an array'];
    }

    if (!isset($response['ok']) || !$response['ok']) {
        return ['valid' => false, 'error' => 'Response ok is not true'];
    }

    if (!isset($response['mode'])) {
        return ['valid' => false, 'error' => 'Missing mode field'];
    }

    $mode = $response['mode'];
    if (!in_array($mode, ['car', 'bus', 'train', 'flight'])) {
        return ['valid' => false, 'error' => "Invalid mode: {$mode}"];
    }

    // Check required fields
    $requiredFields = ['total_distance_km', 'total_duration_min', 'cost', 'source', 'destination', 'source_coords', 'destination_coords', 'segments'];
    foreach ($requiredFields as $field) {
        if (!isset($response[$field])) {
            return ['valid' => false, 'error' => "Missing required field: {$field}"];
        }
    }

    // Check coordinates
    if (!isset($response['source_coords']['lat']) || !isset($response['source_coords']['lng'])) {
        return ['valid' => false, 'error' => 'Invalid source_coords structure'];
    }

    if (!isset($response['destination_coords']['lat']) || !isset($response['destination_coords']['lng'])) {
        return ['valid' => false, 'error' => 'Invalid destination_coords structure'];
    }

    // Check segments
    $segments = $response['segments'];
    if (!is_array($segments) || count($segments) === 0) {
        return ['valid' => false, 'error' => 'Segments must be a non-empty array'];
    }

    // Validate each segment
    foreach ($segments as $index => $segment) {
        if (!is_array($segment)) {
            return ['valid' => false, 'error' => "Segment {$index} is not an array"];
        }

        // Required segment fields
        $segmentRequired = ['type', 'label', 'from', 'to', 'from_name', 'to_name', 'distance_km', 'duration_min', 'polyline'];
        foreach ($segmentRequired as $field) {
            if (!isset($segment[$field])) {
                return ['valid' => false, 'error' => "Segment {$index} missing field: {$field}"];
            }
        }

        // Validate type
        if (!in_array($segment['type'], ['road', 'rail', 'flight'])) {
            return ['valid' => false, 'error' => "Segment {$index} invalid type: {$segment['type']}"];
        }

        // Validate polyline
        if (!is_array($segment['polyline'])) {
            return ['valid' => false, 'error' => "Segment {$index} polyline is not an array"];
        }

        // Polyline can be empty for calculation endpoints (frontend requests map data separately)
        // But if polyline has points, it must have proper structure
        if (count($segment['polyline']) > 0) {
            // For non-empty polylines, road and rail must have at least 2 points
            if (in_array($segment['type'], ['road', 'rail']) && count($segment['polyline']) < 2) {
                return ['valid' => false, 'error' => "Segment {$index} ({$segment['type']}) must have at least 2 polyline points if polyline is non-empty"];
            }

            // Validate polyline structure
            foreach ($segment['polyline'] as $pi => $point) {
                if (!isset($point['lat']) || !isset($point['lng'])) {
                    return ['valid' => false, 'error' => "Segment {$index} polyline point {$pi} missing lat/lng"];
                }
            }
        }

        // Distance must be > 0 (or 0 for trivial road segments)
        if ($segment['distance_km'] < 0) {
            return ['valid' => false, 'error' => "Segment {$index} distance_km cannot be negative"];
        }

        // Duration must be >= 0
        if ($segment['duration_min'] < 0) {
            return ['valid' => false, 'error' => "Segment {$index} duration_min cannot be negative"];
        }
    }

    // Validate segment type combinations per mode
    $segmentTypes = array_map(fn($s) => $s['type'], $segments);
    $segmentTypeStr = implode(',', $segmentTypes);

    $validCombinations = [
        'car' => ['road'],
        'bus' => ['road'],
        'train' => ['road,rail,road'],
        'flight' => ['road,flight,road']
    ];

    if (!in_array($segmentTypeStr, (array)$validCombinations[$mode])) {
        return ['valid' => false, 'error' => "Invalid segment combination for {$mode}: {$segmentTypeStr}. Expected: " . implode(' or ', (array)$validCombinations[$mode])];
    }

    // Check distance invariant
    $sumDistance = 0;
    foreach ($segments as $seg) {
        $sumDistance += floatval($seg['distance_km']);
    }

    $totalDistance = floatval($response['total_distance_km']);
    $distanceTolerance = max(0.1, abs($totalDistance * 0.01)); // ±1% or at least 0.1 km
    $distanceDiff = abs($totalDistance - $sumDistance);

    if ($distanceDiff > $distanceTolerance) {
        return ['valid' => false, 'error' => "Distance mismatch: total={$totalDistance}, sum_of_segments={$sumDistance}, diff={$distanceDiff}, tolerance={$distanceTolerance}", 'error_code' => 'SEGMENT_MISMATCH'];
    }

    // Check duration invariant
    $sumDuration = 0;
    foreach ($segments as $seg) {
        $sumDuration += intval($seg['duration_min']);
    }

    $totalDuration = intval($response['total_duration_min']);
    $durationTolerance = max(5, intval(abs($totalDuration * 0.05))); // ±5% or at least 5 min
    $durationDiff = abs($totalDuration - $sumDuration);

    if ($durationDiff > $durationTolerance) {
        return ['valid' => false, 'error' => "Duration mismatch: total={$totalDuration}, sum_of_segments={$sumDuration}, diff={$durationDiff}, tolerance={$durationTolerance}", 'error_code' => 'SEGMENT_MISMATCH'];
    }

    // All checks passed
    return ['valid' => true, 'error' => null];
}

/**
 * Build a unified journey response.
 * 
 * @param string $mode 'bus', 'train', or 'flight'
 * @param float $totalDistanceKm Total distance
 * @param int $totalDurationMin Total duration in minutes
 * @param float $cost Total cost
 * @param string $source Source city/code
 * @param string $destination Destination city/code
 * @param array $sourceCoords {lat, lng}
 * @param array $destCoords {lat, lng}
 * @param array $segments Array of segment objects
 * @return array Journey contract response
 */
function buildJourneyResponse($mode, $totalDistanceKm, $totalDurationMin, $cost, $source, $destination, $sourceCoords, $destCoords, $segments) {
    $response = [
        'ok' => true,
        'mode' => $mode,
        'total_distance_km' => round($totalDistanceKm, 2),
        'total_duration_min' => intval($totalDurationMin),
        'cost' => round($cost, 2),
        'source' => $source,
        'destination' => $destination,
        'source_coords' => [
            'lat' => floatval($sourceCoords['lat']),
            'lng' => floatval($sourceCoords['lng'])
        ],
        'destination_coords' => [
            'lat' => floatval($destCoords['lat']),
            'lng' => floatval($destCoords['lng'])
        ],
        'segments' => $segments
    ];

    // Validate before returning
    $validation = validateJourneyContract($response);
    if (!$validation['valid']) {
        return [
            'ok' => false,
            'error' => $validation['error'],
            'error_code' => $validation['error_code'] ?? 'VALIDATION_FAILED'
        ];
    }

    return $response;
}

/**
 * Return a contract validation error response.
 * 
 * @param string $error Error message
 * @param string $errorCode Error code
 * @param int $httpStatus HTTP status code
 */
function rejectInvalidContract($error, $errorCode = 'VALIDATION_FAILED', $httpStatus = 400) {
    http_response_code($httpStatus);
    echo json_encode([
        'ok' => false,
        'error' => $error,
        'error_code' => $errorCode
    ]);
    exit;
}
