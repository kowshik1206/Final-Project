-- Migration: Create airports table
-- Purpose: Store Indian airports with IATA codes for flight mode routing
-- Note: Includes all commercial airports supporting passenger flights

CREATE TABLE IF NOT EXISTS airports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  iata_code CHAR(3) NOT NULL UNIQUE,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100),
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  is_international BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes for fast nearest-airport lookup
  INDEX idx_city (city),
  INDEX idx_coords (latitude, longitude),
  INDEX idx_iata (iata_code),
  INDEX idx_international (is_international)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
