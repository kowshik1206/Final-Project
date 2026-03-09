-- Migration: Seed all district railway stations
-- Purpose: Provide comprehensive nodes for routing graph
-- Batch 1: South & West India

INSERT INTO railway_stations (name, code, city, state, latitude, longitude, importance, created_at) VALUES
-- ANDHRA PRADESH
('Visakhapatnam Junction', 'VSKP', 'Visakhapatnam', 'Andhra Pradesh', 17.7266, 83.2847, 'junction', NOW()),
('Vijayawada Junction', 'BZA', 'Vijayawada', 'Andhra Pradesh', 16.5186, 80.6200, 'junction', NOW()),
('Guntur Junction', 'GNT', 'Guntur', 'Andhra Pradesh', 16.2995, 80.4433, 'junction', NOW()),
('Tirupati', 'TPTY', 'Tirupati', 'Andhra Pradesh', 13.6288, 79.4192, 'major', NOW()),
('Rajahmundry', 'RJY', 'Rajahmundry', 'Andhra Pradesh', 16.9922, 81.7828, 'major', NOW()),
('Nellore', 'NLR', 'Nellore', 'Andhra Pradesh', 14.4426, 79.9865, 'district', NOW()),
('Kurnool City', 'KRNT', 'Kurnool', 'Andhra Pradesh', 15.8281, 78.0373, 'district', NOW()),
('Kakinada Town', 'CCT', 'Kakinada', 'Andhra Pradesh', 16.9587, 82.2343, 'district', NOW()),
('Kadapa', 'HX', 'Kadapa', 'Andhra Pradesh', 14.4673, 78.8242, 'district', NOW()),
('Anantapur', 'ATP', 'Anantapur', 'Andhra Pradesh', 14.6819, 77.6006, 'district', NOW()),
('Ongole', 'OGL', 'Ongole', 'Andhra Pradesh', 15.5057, 80.0496, 'district', NOW()),
('Eluru', 'EE', 'Eluru', 'Andhra Pradesh', 16.7107, 81.0952, 'district', NOW()),
('Srikakulam Road', 'CHE', 'Srikakulam', 'Andhra Pradesh', 18.2949, 83.8968, 'district', NOW()),
('Guntakal Junction', 'GTL', 'Guntakal', 'Andhra Pradesh', 15.1740, 77.3688, 'junction', NOW()),

-- TELANGANA
('Secunderabad Junction', 'SC', 'Hyderabad', 'Telangana', 17.4339, 78.5020, 'junction', NOW()),
('Hyderabad Deccan', 'HYB', 'Hyderabad', 'Telangana', 17.3926, 78.4716, 'major', NOW()),
('Kazipet Junction', 'KZJ', 'Warangal', 'Telangana', 17.9784, 79.5085, 'junction', NOW()),
('Warangal', 'WL', 'Warangal', 'Telangana', 17.9546, 79.6053, 'major', NOW()),
('Khammam', 'KMT', 'Khammam', 'Telangana', 17.2473, 80.1514, 'district', NOW()),
('Nizamabad Junction', 'NZB', 'Nizamabad', 'Telangana', 18.6750, 78.0934, 'district', NOW()),
('Karimnagar', 'KRMR', 'Karimnagar', 'Telangana', 18.4419, 79.1176, 'district', NOW()),
('Mahbubnagar', 'MBNR', 'Mahbubnagar', 'Telangana', 16.7436, 77.9866, 'district', NOW()),
('Nalgonda', 'NLDA', 'Nalgonda', 'Telangana', 17.0577, 79.2625, 'district', NOW()),
('Ramagundam', 'RDM', 'Ramagundam', 'Telangana', 18.7735, 79.4891, 'district', NOW()),

-- KARNATAKA
('KSR Bengaluru', 'SBC', 'Bengaluru', 'Karnataka', 12.9781, 77.5695, 'junction', NOW()),
('Yesvantpur Junction', 'YPR', 'Bengaluru', 'Karnataka', 13.0238, 77.5504, 'junction', NOW()),
('Mysuru Junction', 'MYS', 'Mysuru', 'Karnataka', 12.3168, 76.6430, 'major', NOW()),
('Hubballi Junction', 'UBL', 'Hubballi', 'Karnataka', 15.3435, 75.1489, 'junction', NOW()),
('Mangaluru Central', 'MAQ', 'Mangaluru', 'Karnataka', 12.8647, 74.8361, 'major', NOW()),
('Belagavi', 'BGM', 'Belagavi', 'Karnataka', 15.8617, 74.5097, 'district', NOW()),
('Kalaburagi', 'KLBG', 'Kalaburagi', 'Karnataka', 17.3297, 76.8488, 'district', NOW()),
('Davanagere', 'DVG', 'Davanagere', 'Karnataka', 14.4695, 75.9142, 'district', NOW()),
('Ballari Junction', 'BAY', 'Ballari', 'Karnataka', 15.1420, 76.9242, 'district', NOW()),
('Shivamogga Town', 'SMET', 'Shivamogga', 'Karnataka', 13.9329, 75.5663, 'district', NOW()),
('Hassan Junction', 'HAS', 'Hassan', 'Karnataka', 13.0033, 76.1004, 'district', NOW()),
('Tumakuru', 'TK', 'Tumakuru', 'Karnataka', 13.3379, 77.1173, 'district', NOW()),
('Vijayapura', 'BJP', 'Vijayapura', 'Karnataka', 16.8280, 75.7134, 'district', NOW()),
('Raichur', 'RC', 'Raichur', 'Karnataka', 16.2045, 77.3551, 'district', NOW()),

-- TAMIL NADU
('Chennai Central', 'MAS', 'Chennai', 'Tamil Nadu', 13.0827, 80.2707, 'junction', NOW()),
('Chennai Egmore', 'MS', 'Chennai', 'Tamil Nadu', 13.0784, 80.2584, 'major', NOW()),
('Coimbatore Junction', 'CBE', 'Coimbatore', 'Tamil Nadu', 11.0016, 76.9662, 'junction', NOW()),
('Madurai Junction', 'MDU', 'Madurai', 'Tamil Nadu', 9.9208, 78.1099, 'junction', NOW()),
('Tiruchirappalli Junction', 'TPJ', 'Tiruchirappalli', 'Tamil Nadu', 10.7876, 78.6872, 'junction', NOW()),
('Salem Junction', 'SA', 'Salem', 'Tamil Nadu', 11.6667, 78.1252, 'junction', NOW()),
('Erode Junction', 'ED', 'Erode', 'Tamil Nadu', 11.3323, 77.7172, 'junction', NOW()),
('Tirunelveli Junction', 'TEN', 'Tirunelveli', 'Tamil Nadu', 8.7294, 77.6974, 'junction', NOW()),
('Vellore Katpadi', 'KPD', 'Vellore', 'Tamil Nadu', 12.9691, 79.1415, 'junction', NOW()),
('Thanjavur Junction', 'TJ', 'Thanjavur', 'Tamil Nadu', 10.7744, 79.1309, 'district', NOW()),
('Tuticorin', 'TN', 'Thoothukudi', 'Tamil Nadu', 8.7946, 78.1362, 'district', NOW()),
('Nagercoil Junction', 'NCJ', 'Nagercoil', 'Tamil Nadu', 8.1887, 77.4429, 'major', NOW()),
('Dindigul Junction', 'DG', 'Dindigul', 'Tamil Nadu', 10.3546, 77.9624, 'district', NOW()),
('Karur Junction', 'KRR', 'Karur', 'Tamil Nadu', 10.9601, 78.0766, 'district', NOW()),

-- KERALA
('Thiruvananthapuram Central', 'TVC', 'Thiruvananthapuram', 'Kerala', 8.4862, 76.9537, 'major', NOW()),
('Ernakulam Junction', 'ERS', 'Kochi', 'Kerala', 9.9676, 76.2908, 'junction', NOW()),
('Kozhikode', 'CLT', 'Kozhikode', 'Kerala', 11.2393, 75.7766, 'major', NOW()),
('Thrissur', 'TCR', 'Thrissur', 'Kerala', 10.5186, 76.2104, 'district', NOW()),
('Kollam Junction', 'QLN', 'Kollam', 'Kerala', 8.8879, 76.5947, 'junction', NOW()),
('Palakkad Junction', 'PGT', 'Palakkad', 'Kerala', 10.7937, 76.6433, 'junction', NOW()),
('Kannur', 'CAN', 'Kannur', 'Kerala', 11.8706, 75.3721, 'district', NOW()),
('Alappuzha', 'ALLP', 'Alappuzha', 'Kerala', 9.4938, 76.3262, 'district', NOW()),
('Kottayam', 'KTYM', 'Kottayam', 'Kerala', 9.5898, 76.5273, 'district', NOW()),
('Shoranur Junction', 'SRR', 'Palakkad', 'Kerala', 10.7602, 76.2730, 'junction', NOW()),

-- MAHARASHTRA
('Mumbai CSMT', 'CSMT', 'Mumbai', 'Maharashtra', 18.9400, 72.8353, 'major', NOW()),
('Pune Junction', 'PUNE', 'Pune', 'Maharashtra', 18.5284, 73.8739, 'junction', NOW()),
('Nagpur Junction', 'NGP', 'Nagpur', 'Maharashtra', 21.1523, 79.0888, 'junction', NOW()),
('Nashik Road', 'NK', 'Nashik', 'Maharashtra', 19.9575, 73.8347, 'major', NOW()),
('Aurangabad', 'AWB', 'Aurangabad', 'Maharashtra', 19.8660, 75.3129, 'district', NOW()),
('Thane', 'TNA', 'Thane', 'Maharashtra', 19.1860, 72.9750, 'major', NOW()),
('Solapur', 'SUR', 'Solapur', 'Maharashtra', 17.6698, 75.9126, 'district', NOW()),
('Kolhapur SCJM', 'KOP', 'Kolhapur', 'Maharashtra', 16.7032, 74.2435, 'district', NOW()),
('Amravati', 'AMI', 'Amravati', 'Maharashtra', 20.9304, 77.7667, 'district', NOW()),
('Akola Junction', 'AK', 'Akola', 'Maharashtra', 20.7035, 77.0094, 'junction', NOW()),
('Bhusaval Junction', 'BSL', 'Jalgaon', 'Maharashtra', 21.0456, 75.7915, 'junction', NOW()),
('Manmad Junction', 'MMR', 'Nashik', 'Maharashtra', 20.2520, 74.4377, 'junction', NOW()),
('Ratnagiri', 'RN', 'Ratnagiri', 'Maharashtra', 16.9944, 73.3364, 'district', NOW()),
('Jalgaon Junction', 'JL', 'Jalgaon', 'Maharashtra', 21.0027, 75.5562, 'district', NOW()),
('Nanded', 'NED', 'Nanded', 'Maharashtra', 19.1687, 77.3182, 'district', NOW()),

-- GUJARAT
('Ahmedabad Junction', 'ADI', 'Ahmedabad', 'Gujarat', 23.0263, 72.5991, 'junction', NOW()),
('Vadodara Junction', 'BRC', 'Vadodara', 'Gujarat', 22.3106, 73.1812, 'junction', NOW()),
('Surat', 'ST', 'Surat', 'Gujarat', 21.2052, 72.8407, 'major', NOW()),
('Rajkot Junction', 'RJT', 'Rajkot', 'Gujarat', 22.3126, 70.8033, 'major', NOW()),
('Bhavnagar Terminus', 'BVC', 'Bhavnagar', 'Gujarat', 21.7776, 72.1384, 'district', NOW()),
('Jamnagar', 'JAM', 'Jamnagar', 'Gujarat', 22.4839, 70.0543, 'district', NOW()),
('Junagadh Junction', 'JND', 'Junagadh', 'Gujarat', 21.5222, 70.4579, 'district', NOW()),
('Gandhidham Junction', 'GIMB', 'Kutch', 'Gujarat', 23.0772, 70.1348, 'major', NOW()),
('Bhuj', 'BHUJ', 'Kutch', 'Gujarat', 23.2553, 69.6738, 'district', NOW()),
('Anand Junction', 'ANND', 'Anand', 'Gujarat', 22.5606, 72.9602, 'district', NOW()),
('Bharuch Junction', 'BH', 'Bharuch', 'Gujarat', 21.7061, 72.9733, 'district', NOW()),
('Valsad', 'BL', 'Valsad', 'Gujarat', 20.6096, 72.9342, 'district', NOW()),

-- GOA
('Madgaon Junction', 'MAO', 'Goa', 'Goa', 15.2736, 73.9691, 'junction', NOW()),
('Vasco-da-Gama', 'VSG', 'Goa', 'Goa', 15.3944, 73.8188, 'major', NOW());

-- Batch 2: North & Central India

INSERT INTO railway_stations (name, code, city, state, latitude, longitude, importance, created_at) VALUES
-- UTTAR PRADESH
('Lucknow Charbagh NR', 'LKO', 'Lucknow', 'Uttar Pradesh', 26.8320, 80.9205, 'junction', NOW()),
('Kanpur Central', 'CNB', 'Kanpur', 'Uttar Pradesh', 26.4539, 80.3507, 'junction', NOW()),
('Varanasi Junction', 'BSB', 'Varanasi', 'Uttar Pradesh', 25.3263, 82.9860, 'junction', NOW()),
('Prayagraj Junction', 'PRYJ', 'Prayagraj', 'Uttar Pradesh', 25.4448, 81.8282, 'junction', NOW()),
('Agra Cantt', 'AGC', 'Agra', 'Uttar Pradesh', 27.1569, 77.9942, 'major', NOW()),
('Gorakhpur Junction', 'GKP', 'Gorakhpur', 'Uttar Pradesh', 26.7648, 83.3820, 'junction', NOW()),
('Jhansi Junction', 'VGLJ', 'Jhansi', 'Uttar Pradesh', 25.4526, 78.5583, 'junction', NOW()),
('Meerut City', 'MTC', 'Meerut', 'Uttar Pradesh', 28.9839, 77.6960, 'district', NOW()),
('Aligarh Junction', 'ALJN', 'Aligarh', 'Uttar Pradesh', 27.8924, 78.0837, 'district', NOW()),
('Moradabad Junction', 'MB', 'Moradabad', 'Uttar Pradesh', 28.8350, 78.7758, 'junction', NOW()),
('Bareilly Junction', 'BE', 'Bareilly', 'Uttar Pradesh', 28.3496, 79.4101, 'district', NOW()),
('Ayodhya Cantt', 'AYC', 'Ayodhya', 'Uttar Pradesh', 26.7794, 82.1643, 'district', NOW()),
('Mathura Junction', 'MTJ', 'Mathura', 'Uttar Pradesh', 27.4891, 77.6749, 'junction', NOW()),
('Saharanpur Junction', 'SRE', 'Saharanpur', 'Uttar Pradesh', 29.9658, 77.5518, 'junction', NOW()),

-- MADHYA PRADESH
('Bhopal Junction', 'BPL', 'Bhopal', 'Madhya Pradesh', 23.2625, 77.4116, 'junction', NOW()),
('Indore Junction', 'INDB', 'Indore', 'Madhya Pradesh', 22.7161, 75.8710, 'major', NOW()),
('Gwalior Junction', 'GWL', 'Gwalior', 'Madhya Pradesh', 26.2163, 78.1884, 'junction', NOW()),
('Jabalpur Junction', 'JBP', 'Jabalpur', 'Madhya Pradesh', 23.1678, 79.9472, 'junction', NOW()),
('Ujjain Junction', 'UJN', 'Ujjain', 'Madhya Pradesh', 23.1784, 75.7828, 'junction', NOW()),
('Ratlam Junction', 'RTM', 'Ratlam', 'Madhya Pradesh', 23.3364, 75.0536, 'junction', NOW()),
('Katni Junction', 'KTE', 'Katni', 'Madhya Pradesh', 23.8290, 80.3952, 'junction', NOW()),
('Satna Junction', 'STA', 'Satna', 'Madhya Pradesh', 24.5772, 80.8256, 'district', NOW()),
('Rewa', 'REWA', 'Rewa', 'Madhya Pradesh', 24.5324, 81.2828, 'district', NOW()),
('Itarsi Junction', 'ET', 'Itarsi', 'Madhya Pradesh', 22.6111, 77.7601, 'junction', NOW()),

-- RAJASTHAN
('Jaipur Junction', 'JP', 'Jaipur', 'Rajasthan', 26.9196, 75.7880, 'junction', NOW()),
('Jodhpur Junction', 'JU', 'Jodhpur', 'Rajasthan', 26.2845, 73.0298, 'major', NOW()),
('Udaipur City', 'UDZ', 'Udaipur', 'Rajasthan', 24.5714, 73.6974, 'major', NOW()),
('Kota Junction', 'KOTA', 'Kota', 'Rajasthan', 25.2215, 75.8700, 'junction', NOW()),
('Ajmer Junction', 'AII', 'Ajmer', 'Rajasthan', 26.4499, 74.6399, 'major', NOW()),
('Bikaner Junction', 'BKN', 'Bikaner', 'Rajasthan', 28.0264, 73.3087, 'major', NOW()),
('Jaisalmer', 'JSM', 'Jaisalmer', 'Rajasthan', 26.9157, 70.9238, 'district', NOW()),
('Sawai Madhopur', 'SWM', 'Sawai Madhopur', 'Rajasthan', 26.0267, 76.3575, 'junction', NOW()),
('Abu Road', 'ABR', 'Abu Road', 'Rajasthan', 24.4786, 72.7758, 'district', NOW()),

-- DELHI
('New Delhi', 'NDLS', 'Delhi', 'Delhi', 28.6429, 77.2191, 'junction', NOW()),
('Old Delhi', 'DLI', 'Delhi', 'Delhi', 28.6601, 77.2274, 'major', NOW()),
('Hazrat Nizamuddin', 'NZM', 'Delhi', 'Delhi', 28.5866, 77.2536, 'major', NOW()),
('Anand Vihar Terminal', 'ANVT', 'Delhi', 'Delhi', 28.6473, 77.3149, 'major', NOW()),

-- PUNJAB
('Amritsar Junction', 'ASR', 'Amritsar', 'Punjab', 31.6341, 74.8723, 'junction', NOW()),
('Ludhiana Junction', 'LDH', 'Ludhiana', 'Punjab', 30.9168, 75.8569, 'junction', NOW()),
('Jalandhar City', 'JUC', 'Jalandhar', 'Punjab', 31.3260, 75.5868, 'major', NOW()),
('Bathinda Junction', 'BTI', 'Bathinda', 'Punjab', 30.2098, 74.9388, 'junction', NOW()),
('Pathankot Junction', 'PTK', 'Pathankot', 'Punjab', 32.2743, 75.6416, 'junction', NOW()),
('Patiala', 'PTA', 'Patiala', 'Punjab', 30.3424, 76.3980, 'district', NOW()),

-- HARYANA
('Ambala Cantt', 'UMB', 'Ambala', 'Haryana', 30.3541, 76.8377, 'junction', NOW()),
('Panipat Junction', 'PNP', 'Panipat', 'Haryana', 29.3909, 76.9635, 'district', NOW()),
('Hisar Junction', 'HSR', 'Hisar', 'Haryana', 29.1481, 75.7144, 'district', NOW()),
('Rohtak Junction', 'ROK', 'Rohtak', 'Haryana', 28.8955, 76.5779, 'district', NOW()),
('Kurukshetra Junction', 'KKDE', 'Kurukshetra', 'Haryana', 29.9695, 76.8197, 'district', NOW()),

-- UTTARAKHAND
('Dehradun', 'DDN', 'Dehradun', 'Uttarakhand', 30.3165, 78.0322, 'major', NOW()),
('Haridwar', 'HW', 'Haridwar', 'Uttarakhand', 29.9457, 78.1642, 'major', NOW()),
('Kathgodam', 'KGM', 'Nainital', 'Uttarakhand', 29.2683, 79.5484, 'major', NOW()),

-- HIMACHAL PRADESH
('Kalka', 'KLK', 'Panchkula', 'Haryana', 30.8354, 76.9360, 'major', NOW()), -- Gateway to HP
('Shimla', 'SML', 'Shimla', 'Himachal Pradesh', 31.1048, 77.1734, 'district', NOW()),

-- CHHATTISGARH
('Raipur Junction', 'R', 'Raipur', 'Chhattisgarh', 21.2514, 81.6296, 'junction', NOW()),
('Bilaspur Junction', 'BSP', 'Bilaspur', 'Chhattisgarh', 22.0797, 82.1409, 'junction', NOW()),
('Durg Junction', 'DURG', 'Durg', 'Chhattisgarh', 21.1904, 81.2849, 'junction', NOW()),
('Korba', 'KRBA', 'Korba', 'Chhattisgarh', 22.3562, 82.6841, 'district', NOW()),

-- JAMMU & KASHMIR
('Jammu Tawi', 'JAT', 'Jammu', 'Jammu & Kashmir', 32.7058, 74.8817, 'major', NOW()),
('Shri Mata Vaishno Devi Katra', 'SVDK', 'Katra', 'Jammu & Kashmir', 32.9920, 74.9319, 'major', NOW()),
('Udhampur', 'UHP', 'Udhampur', 'Jammu & Kashmir', 32.9234, 75.1436, 'district', NOW());

-- Batch 3: East & North East India & Others

INSERT INTO railway_stations (name, code, city, state, latitude, longitude, importance, created_at) VALUES
-- WEST BENGAL
('Howrah Junction', 'HWH', 'Kolkata', 'West Bengal', 22.5851, 88.3468, 'major', NOW()),
('Sealdah', 'SDAH', 'Kolkata', 'West Bengal', 22.5645, 88.3713, 'major', NOW()),
('Kolkata Chitpur', 'KOAA', 'Kolkata', 'West Bengal', 22.6074, 88.3845, 'major', NOW()),
('Asansol Junction', 'ASN', 'Asansol', 'West Bengal', 23.6889, 86.9749, 'junction', NOW()),
('Malda Town', 'MLDT', 'Malda', 'West Bengal', 25.0118, 88.1396, 'district', NOW()),
('New Jalpaiguri', 'NJP', 'Siliguri', 'West Bengal', 26.6905, 88.4357, 'junction', NOW()),
('Kharagpur Junction', 'KGP', 'Kharagpur', 'West Bengal', 22.3398, 87.3235, 'junction', NOW()),
('Durgapur', 'DGR', 'Durgapur', 'West Bengal', 23.5181, 87.3119, 'district', NOW()),
('Barddhaman Junction', 'BWN', 'Bardhaman', 'West Bengal', 23.2324, 87.8615, 'junction', NOW()),
('Siliguri Junction', 'SGUJ', 'Siliguri', 'West Bengal', 26.7118, 88.4239, 'junction', NOW()),

-- ODISHA
('Bhubaneswar', 'BBS', 'Bhubaneswar', 'Odisha', 20.2666, 85.8436, 'major', NOW()),
('Cuttack Junction', 'CTC', 'Cuttack', 'Odisha', 20.4735, 85.8828, 'junction', NOW()),
('Puri', 'PURI', 'Puri', 'Odisha', 19.8135, 85.8312, 'major', NOW()),
('Rourkela Junction', 'ROU', 'Rourkela', 'Odisha', 22.2263, 84.8690, 'junction', NOW()),
('Sambalpur Junction', 'SBP', 'Sambalpur', 'Odisha', 21.4669, 83.9812, 'junction', NOW()),
('Balasore', 'BLS', 'Balasore', 'Odisha', 21.4939, 86.9317, 'district', NOW()),
('Berhampur', 'BAM', 'Berhampur', 'Odisha', 19.3088, 84.8020, 'district', NOW()),
('Jharsuguda Junction', 'JSG', 'Jharsuguda', 'Odisha', 21.8550, 84.0048, 'junction', NOW()),

-- BIHAR
('Patna Junction', 'PNBE', 'Patna', 'Bihar', 25.6022, 85.1376, 'junction', NOW()),
('Gaya Junction', 'GAYA', 'Gaya', 'Bihar', 24.8048, 84.9995, 'junction', NOW()),
('Muzaffarpur Junction', 'MFP', 'Muzaffarpur', 'Bihar', 26.1197, 85.3910, 'junction', NOW()),
('Bhagalpur Junction', 'BGP', 'Bhagalpur', 'Bihar', 25.2443, 86.9715, 'major', NOW()),
('Darbhanga Junction', 'DBG', 'Darbhanga', 'Bihar', 26.1627, 85.8890, 'junction', NOW()),
('Chhapra Junction', 'CPR', 'Chhapra', 'Bihar', 25.7796, 84.7352, 'junction', NOW()),
('Katihar Junction', 'KIR', 'Katihar', 'Bihar', 25.5390, 87.5645, 'junction', NOW()),

-- JHARKHAND
('Ranchi Junction', 'RNC', 'Ranchi', 'Jharkhand', 23.3441, 85.3096, 'junction', NOW()),
('Tatanagar Junction', 'TATA', 'Jamshedpur', 'Jharkhand', 22.7681, 86.1976, 'junction', NOW()),
('Dhanbad Junction', 'DHN', 'Dhanbad', 'Jharkhand', 23.7911, 86.4299, 'junction', NOW()),
('Bokaro Steel City', 'BKSC', 'Bokaro', 'Jharkhand', 23.6706, 86.1362, 'major', NOW()),
('Jasidih Junction', 'JSME', 'Deoghar', 'Jharkhand', 24.5167, 86.6468, 'junction', NOW()),

-- ASSAM & NORTH EAST
('Guwahati', 'GHY', 'Guwahati', 'Assam', 26.1837, 91.7479, 'major', NOW()),
('Kamakhya Junction', 'KYQ', 'Guwahati', 'Assam', 26.1601, 91.7029, 'junction', NOW()),
('Dibrugarh', 'DBRG', 'Dibrugarh', 'Assam', 27.4728, 94.9118, 'district', NOW()),
('Jorhat Town', 'JTTN', 'Jorhat', 'Assam', 26.7588, 94.2081, 'district', NOW()),
('Silchar', 'SCL', 'Silchar', 'Assam', 24.8333, 92.7960, 'district', NOW()),
('New Tinsukia', 'NTSK', 'Tinsukia', 'Assam', 27.5052, 95.3712, 'junction', NOW()),
('Agartala', 'AGTL', 'Agartala', 'Tripura', 23.8315, 91.2868, 'district', NOW()),
('Dimapur', 'DMV', 'Dimapur', 'Nagaland', 25.9088, 93.7337, 'district', NOW()), -- Gateway to Kohima
('Naharlagun', 'NHLN', 'Itanagar', 'Arunachal Pradesh', 27.1084, 93.6920, 'district', NOW()),

-- UNION TERRITORIES
('Puducherry', 'PDY', 'Puducherry', 'Puducherry', 11.9216, 79.8290, 'major', NOW()),
('Chandigarh Junction', 'CDG', 'Chandigarh', 'Chandigarh', 30.7067, 76.8322, 'junction', NOW());
