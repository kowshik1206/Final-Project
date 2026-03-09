-- Seed data: Major railway stations in India
-- Source: Curated list of major and junction stations for routing
-- Coverage: Primary metro cities and major junction points

INSERT INTO railway_stations (name, code, city, state, latitude, longitude, importance) VALUES
-- Delhi NCR
('New Delhi Railway Station', 'NDLS', 'Delhi', 'Delhi', 28.6429, 77.2195, 'major'),
('Old Delhi Railway Station', 'DLI', 'Delhi', 'Delhi', 28.6644, 77.2295, 'major'),
('Hazrat Nizamuddin', 'NZM', 'Delhi', 'Delhi', 28.5875, 77.2509, 'major'),
('Anand Vihar Terminal', 'ANVT', 'Delhi', 'Delhi', 28.6469, 77.3159, 'junction'),

-- Mumbai
('Chhatrapati Shivaji Maharaj Terminus', 'CSMT', 'Mumbai', 'Maharashtra', 18.9402, 72.8356, 'major'),
('Mumbai Central', 'MMCT', 'Mumbai', 'Maharashtra', 18.9696, 72.8194, 'major'),
('Lokmanya Tilak Terminus', 'LTT', 'Mumbai', 'Maharashtra', 19.0689, 72.8908, 'major'),
('Bandra Terminus', 'BDTS', 'Mumbai', 'Maharashtra', 19.0544, 72.8408, 'junction'),

-- Bangalore
('Krantivira Sangolli Rayanna', 'SBC', 'Bangalore', 'Karnataka', 12.9766, 77.5721, 'major'),
('Yesvantpur Junction', 'YPR', 'Bangalore', 'Karnataka', 13.0222, 77.5404, 'junction'),

-- Chennai
('Chennai Central', 'MAS', 'Chennai', 'Tamil Nadu', 13.0821, 80.2750, 'major'),
('Chennai Egmore', 'MS', 'Chennai', 'Tamil Nadu', 13.0750, 80.2606, 'major'),

-- Kolkata
('Howrah Junction', 'HWH', 'Kolkata', 'West Bengal', 22.5836, 88.3426, 'major'),
('Sealdah', 'SDAH', 'Kolkata', 'West Bengal', 22.5693, 88.3701, 'major'),

-- Hyderabad
('Secunderabad Junction', 'SC', 'Hyderabad', 'Telangana', 17.4345, 78.5012, 'major'),
('Hyderabad Deccan', 'HYB', 'Hyderabad', 'Telangana', 17.3753, 78.4744, 'junction'),

-- Pune
('Pune Junction', 'PUNE', 'Pune', 'Maharashtra', 18.5287, 73.8742, 'major'),

-- Ahmedabad
('Ahmedabad Junction', 'ADI', 'Ahmedabad', 'Gujarat', 23.0319, 72.5849, 'major'),

-- Jaipur
('Jaipur Junction', 'JP', 'Jaipur', 'Rajasthan', 26.9186, 75.7871, 'major'),

-- Lucknow
('Lucknow Charbagh', 'LKO', 'Lucknow', 'Uttar Pradesh', 26.8394, 80.9229, 'major'),

-- Chandigarh
('Chandigarh Railway Station', 'CDG', 'Chandigarh', 'Chandigarh', 30.7101, 76.8014, 'major'),

-- Bhopal
('Bhopal Junction', 'BPL', 'Bhopal', 'Madhya Pradesh', 23.2686, 77.4120, 'major'),

-- Nagpur (important junction)
('Nagpur Junction', 'NGP', 'Nagpur', 'Maharashtra', 21.1509, 79.0809, 'junction'),

-- Patna
('Patna Junction', 'PNBE', 'Patna', 'Bihar', 25.6025, 85.1239, 'major'),

-- Kanpur
('Kanpur Central', 'CNB', 'Kanpur', 'Uttar Pradesh', 26.4487, 80.3524, 'junction'),

-- Surat
('Surat Railway Station', 'ST', 'Surat', 'Gujarat', 21.2018, 72.8417, 'major'),

-- Visakhapatnam
('Visakhapatnam Junction', 'VSKP', 'Visakhapatnam', 'Andhra Pradesh', 17.7011, 83.2979, 'major'),

-- Bhubaneswar
('Bhubaneswar Railway Station', 'BBS', 'Bhubaneswar', 'Odisha', 20.2489, 85.8103, 'major'),

-- Guwahati
('Guwahati Railway Station', 'GHY', 'Guwahati', 'Assam', 26.1862, 91.7469, 'major'),

-- Trivandrum
('Trivandrum Central', 'TVC', 'Trivandrum', 'Kerala', 8.4888, 76.9496, 'major'),

-- Kochi
('Ernakulam Junction', 'ERS', 'Kochi', 'Kerala', 9.9817, 76.2815, 'major'),

-- Indore
('Indore Junction', 'INDB', 'Indore', 'Madhya Pradesh', 22.7156, 75.8628, 'major'),

-- Vadodara
('Vadodara Junction', 'BRC', 'Vadodara', 'Gujarat', 22.3023, 73.1812, 'junction'),

-- Agra
('Agra Cantt', 'AGC', 'Agra', 'Uttar Pradesh', 27.1481, 77.9782, 'major'),

-- Varanasi
('Varanasi Junction', 'BSB', 'Varanasi', 'Uttar Pradesh', 25.3181, 82.9989, 'major'),

-- Amritsar
('Amritsar Junction', 'ASR', 'Amritsar', 'Punjab', 31.6354, 74.8763, 'major'),

-- Jammu
('Jammu Tawi', 'JAT', 'Jammu', 'Jammu and Kashmir', 32.7095, 74.8647, 'major');
