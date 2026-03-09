-- Migration: Create railway_stations table
-- Purpose: Store curated major and junction railway stations for train mode routing
-- Note: This is NOT a complete Indian Railways database - only major stations for routing

CREATE TABLE IF NOT EXISTS railway_stations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(10) NOT NULL UNIQUE,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100),
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  importance ENUM('major','junction') NOT NULL DEFAULT 'major',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes for fast nearest-station lookup
  INDEX idx_city (city),
  INDEX idx_coords (latitude, longitude),
  INDEX idx_importance (importance),
  INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
