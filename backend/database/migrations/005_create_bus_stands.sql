-- Migration: Create bus_stands table
-- Purpose: Store major bus stands for bus mode routing and reference
-- Note: Includes interstate, city, and private bus terminals

CREATE TABLE IF NOT EXISTS bus_stands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL COMMENT 'Bus stand / terminal name',
  city VARCHAR(100) NOT NULL COMMENT 'City where bus stand is located',
  state VARCHAR(100) COMMENT 'State/UT',
  latitude DECIMAL(10,7) NOT NULL COMMENT 'Latitude for geolocation',
  longitude DECIMAL(10,7) NOT NULL COMMENT 'Longitude for geolocation',
  type ENUM('interstate', 'city', 'private') NOT NULL DEFAULT 'city' COMMENT 'Type of bus terminal',
  facilities JSON COMMENT 'e.g., {"wifi": true, "food": true, "restroom": true}',
  rating DECIMAL(2,1) COMMENT 'Passenger rating (0-5)',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for fast nearest-stand lookup
  INDEX idx_city (city),
  INDEX idx_coords (latitude, longitude),
  INDEX idx_type (type),
  UNIQUE KEY unique_stand (name, city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bus terminals and major stands for routing';
