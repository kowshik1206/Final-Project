CREATE TABLE IF NOT EXISTS pois_india (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    latitude DOUBLE,
    longitude DOUBLE,
    category VARCHAR(50),      -- 'fuel' / 'ev' / 'cng'
    route_segment VARCHAR(255),-- e.g., "NH44:km_120-130" or NULL
    created_at DATETIME,
    external_id VARCHAR(255),
    source VARCHAR(100),
    brand VARCHAR(100),
    operator VARCHAR(100),
    phone VARCHAR(50),
    website VARCHAR(255),
    tags TEXT,
    confidence FLOAT,
    verified BOOLEAN,
    updated_at DATETIME
);
