-- Upgrade POIS table to support robust data
-- Run this in your MySQL database (routeiq)

-- 1. Add new columns if they don't exist
-- We use a stored procedure to safely add columns only if missing, or we can just ALTER IGNORE in some versions.
-- Ideally, since this is dev, we can DROP and RECREATE or just ALTER safely.

ALTER TABLE pois
ADD COLUMN IF NOT EXISTS external_id VARCHAR(255),
ADD COLUMN IF NOT EXISTS source VARCHAR(50) DEFAULT 'manual', -- 'google', 'osm', 'manual'
ADD COLUMN IF NOT EXISTS brand VARCHAR(255),
ADD COLUMN IF NOT EXISTS operator VARCHAR(255),
ADD COLUMN IF NOT EXISTS phone VARCHAR(50),
ADD COLUMN IF NOT EXISTS website VARCHAR(255),
ADD COLUMN IF NOT EXISTS tags JSON,
ADD COLUMN IF NOT EXISTS confidence FLOAT DEFAULT 0.5,
ADD COLUMN IF NOT EXISTS verified BOOLEAN DEFAULT 0,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 2. Add Indexes for performance
-- Spatial index is best, but for basic lat/lon buffering, composite is okay.
-- Ensure we have index on category for filtering.

CREATE INDEX IF NOT EXISTS idx_pois_category ON pois(category);
CREATE INDEX IF NOT EXISTS idx_pois_source ON pois(source);
CREATE INDEX IF NOT EXISTS idx_pois_brand ON pois(brand);
-- Composite index for rough bounding box queries (latitude first usually helpful)
CREATE INDEX IF NOT EXISTS idx_pois_lat_lon ON pois(latitude, longitude);

-- 3. Modify existing columns if needed (e.g. ensure category is long enough)
ALTER TABLE pois MODIFY COLUMN category VARCHAR(100);
