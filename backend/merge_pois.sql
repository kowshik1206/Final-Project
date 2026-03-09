INSERT INTO pois (name, latitude, longitude, category, source, brand, operator, phone, website, tags, confidence, verified, external_id, created_at, updated_at)
SELECT name, latitude, longitude, category, source, brand, operator, phone, website, tags, confidence, verified, external_id, created_at, updated_at
FROM pois_india
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

DROP TABLE pois_india;
