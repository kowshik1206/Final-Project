-- ============================================
-- Test Verification: Train & Flight Trip Saves
-- ============================================

-- 1. CHECK LATEST TRAIN TRIP
SELECT '=== LATEST TRAIN TRIP ===' as 'Section';
SELECT 
  id,
  user_id,
  source,
  destination,
  distance_km,
  duration_min,
  cost,
  selected_mode,
  passengers,
  created_at,
  ROUND(duration_min / 60, 2) as 'duration_hours'
FROM trips 
WHERE selected_mode = 'train' 
  AND source LIKE '%Chennai%'
  AND destination LIKE '%Mumbai%'
ORDER BY created_at DESC 
LIMIT 1;

-- 2. CHECK LATEST FLIGHT TRIP
SELECT '=== LATEST FLIGHT TRIP ===' as 'Section';
SELECT 
  id,
  user_id,
  source,
  destination,
  distance_km,
  duration_min,
  cost,
  selected_mode,
  passengers,
  created_at
FROM trips 
WHERE selected_mode = 'flight' 
  AND source LIKE '%Chennai%'
  AND destination LIKE '%Mumbai%'
ORDER BY created_at DESC 
LIMIT 1;

-- 3. COMPARE BOTH MODES (SAME ROUTE)
SELECT '=== TRAIN vs FLIGHT COMPARISON ===' as 'Section';
SELECT 
  id,
  selected_mode as 'Mode',
  source,
  destination,
  distance_km,
  duration_min,
  cost,
  created_at
FROM trips 
WHERE (selected_mode IN ('train', 'flight')
  AND source LIKE '%Chennai%'
  AND destination LIKE '%Mumbai%')
ORDER BY created_at DESC 
LIMIT 2;

-- 4. VERIFY DATA TYPES & NO NULL VALUES
SELECT '=== DATA TYPE VERIFICATION ===' as 'Section';
SELECT 
  'Train' as 'Test Type',
  COUNT(*) as 'Record Count',
  COUNT(CASE WHEN duration_min IS NULL THEN 1 END) as 'NULL duration_min',
  COUNT(CASE WHEN cost IS NULL THEN 1 END) as 'NULL cost',
  COUNT(CASE WHEN distance_km IS NULL THEN 1 END) as 'NULL distance_km',
  COUNT(CASE WHEN selected_mode IS NULL THEN 1 END) as 'NULL selected_mode',
  COUNT(CASE WHEN selected_mode = 'train' THEN 1 END) as 'Train Records'
FROM trips 
WHERE selected_mode = 'train'
UNION ALL
SELECT 
  'Flight',
  COUNT(*),
  COUNT(CASE WHEN duration_min IS NULL THEN 1 END),
  COUNT(CASE WHEN cost IS NULL THEN 1 END),
  COUNT(CASE WHEN distance_km IS NULL THEN 1 END),
  COUNT(CASE WHEN selected_mode IS NULL THEN 1 END),
  COUNT(CASE WHEN selected_mode = 'flight' THEN 1 END)
FROM trips 
WHERE selected_mode = 'flight';

-- 5. CHECK TABLE SCHEMA
SELECT '=== TABLE SCHEMA ===' as 'Section';
DESCRIBE trips;

-- 6. COUNT ALL TRIPS BY MODE
SELECT '=== TRIP COUNT BY MODE ===' as 'Section';
SELECT 
  selected_mode,
  COUNT(*) as 'Total Trips',
  COUNT(DISTINCT source) as 'Unique Sources',
  MIN(created_at) as 'First Trip',
  MAX(created_at) as 'Latest Trip'
FROM trips 
GROUP BY selected_mode
ORDER BY COUNT(*) DESC;

-- 7. CHECK LAST 5 TRIPS (ALL MODES)
SELECT '=== LAST 5 TRIPS (ALL MODES) ===' as 'Section';
SELECT 
  id,
  selected_mode,
  source,
  destination,
  distance_km,
  duration_min,
  cost,
  created_at
FROM trips 
ORDER BY created_at DESC 
LIMIT 5;

-- 8. VALIDATE NUMERIC COLUMNS (NO ZEROS OR CORRUPTION)
SELECT '=== NUMERIC VALIDATION ===' as 'Section';
SELECT 
  COUNT(*) as 'Total',
  COUNT(CASE WHEN distance_km = 0 THEN 1 END) as 'ZERO distance_km',
  COUNT(CASE WHEN duration_min = 0 THEN 1 END) as 'ZERO duration_min',
  COUNT(CASE WHEN cost = 0 THEN 1 END) as 'ZERO cost',
  COUNT(CASE WHEN cost > 10000 THEN 1 END) as 'Suspiciously HIGH cost',
  MIN(distance_km) as 'Min Distance',
  MAX(distance_km) as 'Max Distance',
  ROUND(AVG(distance_km), 2) as 'Avg Distance',
  ROUND(AVG(duration_min), 2) as 'Avg Duration (mins)',
  ROUND(AVG(cost), 2) as 'Avg Cost (₹)'
FROM trips 
WHERE selected_mode IN ('train', 'flight');

-- 9. CHECK JSON FIELDS (POLYLINE & COSTS)
SELECT '=== JSON FIELD VALIDATION ===' as 'Section';
SELECT 
  id,
  selected_mode,
  CASE 
    WHEN JSON_VALID(geometry_geojson) THEN 'VALID'
    ELSE 'INVALID or NULL'
  END as 'Polyline Status',
  CASE 
    WHEN JSON_VALID(costs_json) THEN 'VALID'
    ELSE 'INVALID or NULL'
  END as 'Costs JSON Status',
  JSON_LENGTH(geometry_geojson) as 'Polyline Points',
  created_at
FROM trips 
WHERE selected_mode IN ('train', 'flight')
ORDER BY created_at DESC 
LIMIT 5;

-- 10. SUMMARY REPORT
SELECT '=== FINAL SUMMARY ===' as 'Section';
SELECT 
  'Total Train Trips' as 'Metric',
  COUNT(*) as 'Value'
FROM trips 
WHERE selected_mode = 'train'
UNION ALL
SELECT 
  'Total Flight Trips',
  COUNT(*)
FROM trips 
WHERE selected_mode = 'flight'
UNION ALL
SELECT 
  'Total All Trips',
  COUNT(*)
FROM trips
UNION ALL
SELECT 
  'Last Save Time (Train)',
  DATE_FORMAT(MAX(created_at), '%Y-%m-%d %H:%i:%s')
FROM trips 
WHERE selected_mode = 'train'
UNION ALL
SELECT 
  'Last Save Time (Flight)',
  DATE_FORMAT(MAX(created_at), '%Y-%m-%d %H:%i:%s')
FROM trips 
WHERE selected_mode = 'flight';
