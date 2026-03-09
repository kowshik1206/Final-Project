-- Migration: Seed major Indian bus stands
-- Purpose: Populate bus_stands table with major terminals for routing

INSERT INTO bus_stands (name, city, state, latitude, longitude, type, facilities) VALUES

-- Delhi
('Kashmiri Gate Bus Stand', 'New Delhi', 'Delhi', 28.6505, 77.2272, 'interstate', '{"wifi": true, "food": true, "restroom": true}'),
('Maharana Pratap Bus Stand', 'New Delhi', 'Delhi', 28.6210, 77.2395, 'interstate', '{"wifi": true, "food": true, "restroom": true}'),
('Sarai Kale Khan', 'New Delhi', 'Delhi', 28.5775, 77.2476, 'interstate', '{"wifi": false, "food": true, "restroom": true}'),

-- Mumbai
('Dadar Bus Stand', 'Mumbai', 'Maharashtra', 19.0176, 72.8298, 'city', '{"wifi": true, "food": true, "restroom": true}'),
('Central Bus Station', 'Mumbai', 'Maharashtra', 19.1136, 72.8280, 'interstate', '{"wifi": true, "food": true, "restroom": true}'),

-- Bangalore
('Kempegowda Bus Station', 'Bangalore', 'Karnataka', 12.9627, 77.5903, 'interstate', '{"wifi": true, "food": true, "restroom": true}'),
('Shantinagar Bus Stand', 'Bangalore', 'Karnataka', 12.9633, 77.5913, 'city', '{"wifi": false, "food": true, "restroom": true}'),

-- Chennai
('Chennai Central Bus Stand', 'Chennai', 'Tamil Nadu', 13.0827, 80.2707, 'interstate', '{"wifi": true, "food": true, "restroom": true}'),
('Moffusil Bus Stand', 'Chennai', 'Tamil Nadu', 13.0943, 80.2281, 'city', '{"wifi": false, "food": true, "restroom": true}'),

-- Kolkata
('Calcutta Bus Terminus', 'Kolkata', 'West Bengal', 22.5726, 88.3639, 'interstate', '{"wifi": false, "food": true, "restroom": true}'),

-- Hyderabad
('Hyderabad Bus Station', 'Hyderabad', 'Telangana', 17.3850, 78.4867, 'interstate', '{"wifi": true, "food": true, "restroom": true}'),

-- Pune
('Pune Bus Stand', 'Pune', 'Maharashtra', 18.5244, 73.8478, 'city', '{"wifi": true, "food": true, "restroom": true}'),

-- Ahmedabad
('Ahmedabad Bus Station', 'Ahmedabad', 'Gujarat', 23.0225, 72.5714, 'interstate', '{"wifi": true, "food": true, "restroom": true}'),

-- Jaipur
('Jaipur Bus Stand', 'Jaipur', 'Rajasthan', 26.9124, 75.7873, 'interstate', '{"wifi": false, "food": true, "restroom": true}'),

-- Lucknow
('Lucknow Bus Station', 'Lucknow', 'Uttar Pradesh', 26.8467, 80.9462, 'interstate', '{"wifi": false, "food": true, "restroom": true}'),

-- Vijayawada
('Vijayawada Bus Station', 'Vijayawada', 'Andhra Pradesh', 16.5062, 80.6480, 'interstate', '{"wifi": false, "food": true, "restroom": true}'),

-- Tirupati
('Tirupati Bus Stand', 'Tirupati', 'Andhra Pradesh', 13.6288, 79.4192, 'city', '{"wifi": false, "food": true, "restroom": true}');
