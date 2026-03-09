-- Phase 6: Analytics Credibility
-- Create fact tables for authoritative, tamper-proof analytics

DROP TABLE IF EXISTS daily_trip_metrics;
DROP TABLE IF EXISTS trip_poi_facts;
DROP TABLE IF EXISTS trip_segment_facts;
DROP TABLE IF EXISTS trip_facts;

-- 1. Trip Facts: One row per trip, optimized for analytics queries
CREATE TABLE IF NOT EXISTS trip_facts (
    trip_id INT UNSIGNED PRIMARY KEY,
    user_id INT NULL,
    mode VARCHAR(50) NOT NULL,
    distance_km DECIMAL(10, 2) NOT NULL,
    duration_min INT NOT NULL,
    cost_amount DECIMAL(10, 2) NOT NULL,
    passengers INT DEFAULT 1,
    confidence_level ENUM('HIGH', 'ESTIMATED', 'DEMO') NOT NULL DEFAULT 'HIGH',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

-- 2. Trip Segment Facts: Granular analysis of mixed-mode trips
CREATE TABLE IF NOT EXISTS trip_segment_facts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    segment_index INT NOT NULL,
    segment_type VARCHAR(50) NOT NULL, -- e.g., 'car', 'walk', 'train'
    distance_km DECIMAL(10, 2) NOT NULL,
    duration_min INT NOT NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

-- 3. Trip POI Facts: Analytics on infrastructure usage (only authoritative POIs)
CREATE TABLE IF NOT EXISTS trip_poi_facts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT UNSIGNED NOT NULL,
    poi_id INT UNSIGNED NOT NULL,
    poi_type VARCHAR(50) NOT NULL,
    distance_to_route_m INT DEFAULT 0,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (poi_id) REFERENCES pois(id) ON DELETE CASCADE
);

-- 4. Precomputed Aggregates: Daily metrics to avoid full table scans
CREATE TABLE IF NOT EXISTS daily_trip_metrics (
    metric_date DATE NOT NULL,
    mode VARCHAR(50) NOT NULL,
    confidence_level ENUM('HIGH', 'ESTIMATED', 'DEMO') NOT NULL,
    trip_count INT DEFAULT 0,
    total_distance_km DECIMAL(15, 2) DEFAULT 0,
    total_cost DECIMAL(15, 2) DEFAULT 0,
    avg_cost_per_km DECIMAL(10, 2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (metric_date, mode, confidence_level)
);

-- Indexing for performance
CREATE INDEX idx_trip_facts_created_at ON trip_facts(created_at);
CREATE INDEX idx_trip_facts_mode ON trip_facts(mode);
CREATE INDEX idx_trip_facts_confidence ON trip_facts(confidence_level);
