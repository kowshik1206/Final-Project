# 🚗 Vehicle Setup Guide

## Problem
The CarConfigurator shows "❌ No vehicles available" because the `vehicle_profiles` table doesn't exist yet.

## Solution - Choose One Method

### Method 1: Quick PHP Setup (Recommended)
Run this in PowerShell:

```powershell
cd C:\xampp\htdocs\RouteIQ\backend
C:\xampp\php\php.exe setup_vehicles.php
```

This will:
- ✅ Create the `vehicle_profiles` table
- ✅ Seed 12 sample vehicles (Petrol, Diesel, CNG, Electric)
- ✅ Display all vehicles created

### Method 2: MySQL Command Line
Run in PowerShell:

```powershell
$sql = @"
CREATE TABLE IF NOT EXISTS vehicle_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    fuel ENUM('petrol', 'diesel', 'cng', 'electric') NOT NULL,
    efficiency_km_per_unit DECIMAL(5, 2) NOT NULL,
    unit ENUM('L', 'kg', 'kWh') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO vehicle_profiles (name, fuel, efficiency_km_per_unit, unit) VALUES 
('Maruti Swift', 'petrol', 18.5, 'L'),
('Hyundai i20', 'petrol', 17.2, 'L'),
('Tata Nexon', 'petrol', 16.8, 'L'),
('Toyota Innova', 'diesel', 12.5, 'L'),
('Mahindra XUV500', 'diesel', 14.2, 'L'),
('Ford Endeavour', 'diesel', 11.8, 'L'),
('Maruti Alto CNG', 'cng', 22.5, 'kg'),
('Tata Tiago CNG', 'cng', 21.0, 'kg'),
('Hyundai Santro CNG', 'cng', 20.5, 'kg'),
('Tesla Model 3', 'electric', 6.0, 'kWh'),
('Tata Nexon EV', 'electric', 5.2, 'kWh'),
('MG ZS EV', 'electric', 5.5, 'kWh');
"@

$sql | & "C:\xampp\mysql\bin\mysql" -u root routeiq
```

### Method 3: Verify & Test

After setup, verify vehicles are loaded:

```powershell
& "C:\xampp\mysql\bin\mysql" -u root routeiq -e "SELECT * FROM vehicle_profiles;"
```

Test the API:

```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/vehicle-profiles" -UseBasicParsing | Select-Object -ExpandProperty Content | ConvertFrom-Json | ConvertTo-Json
```

## Vehicles Included

| Vehicle | Fuel | Efficiency | Unit |
|---------|------|-----------|------|
| Maruti Swift | Petrol | 18.5 | L |
| Hyundai i20 | Petrol | 17.2 | L |
| Tata Nexon | Petrol | 16.8 | L |
| Toyota Innova | Diesel | 12.5 | L |
| Mahindra XUV500 | Diesel | 14.2 | L |
| Ford Endeavour | Diesel | 11.8 | L |
| Maruti Alto CNG | CNG | 22.5 | kg |
| Tata Tiago CNG | CNG | 21.0 | kg |
| Hyundai Santro CNG | CNG | 20.5 | kg |
| Tesla Model 3 | Electric | 6.0 | kWh |
| Tata Nexon EV | Electric | 5.2 | kWh |
| MG ZS EV | Electric | 5.5 | kWh |

## What Happens Next

1. ✅ Refresh CarConfigurator page
2. ✅ Loading spinner disappears
3. ✅ Vehicle selection buttons appear
4. ✅ Click a vehicle to select it
5. ✅ Fuel costs calculate
6. ✅ Map shows route with markers

---

**Run Method 1 now!** 🚀
