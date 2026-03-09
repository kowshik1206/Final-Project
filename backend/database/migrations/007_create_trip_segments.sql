-- Migration: Create trip_segments table
-- Purpose: Formal storage of trip segments for multi-modal routes
-- Used by: Train (road+rail+road), Bus (road), Car (road)

CREATE TABLE IF NOT EXISTS trip_segments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  trip_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to trips table',
  segment_type ENUM('road', 'rail', 'flight') NOT NULL COMMENT 'Type of transportation segment',
  distance_km DECIMAL(10, 2) NOT NULL COMMENT 'Distance in kilometers',
  duration_min INT NOT NULL COMMENT 'Duration in minutes',
  polyline JSON NOT NULL COMMENT 'Array of {lat, lng} coordinates',
  from_location VARCHAR(100) COMMENT 'Departure location name',
  to_location VARCHAR(100) COMMENT 'Arrival location name',
  from_code VARCHAR(10) COMMENT 'Station/airport code (e.g., NDLS, DEL)',
  to_code VARCHAR(10) COMMENT 'Station/airport code (e.g., MAS, MAA)',
  sort_order INT NOT NULL COMMENT 'Order of segment in trip (1, 2, 3...)',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
  INDEX idx_trip (trip_id),
  INDEX idx_type (segment_type),
  INDEX idx_sort (trip_id, sort_order),
  UNIQUE KEY unique_segment_order (trip_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual segments of multi-modal trips';
