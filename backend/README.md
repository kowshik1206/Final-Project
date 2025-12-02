# RouteIQ Backend API

Production-ready Laravel 10 backend for the RouteIQ intelligent multi-mode travel planning platform.

## Features

✅ JWT Authentication (register, login, logout, refresh)  
✅ Multi-mode route calculation (car, EV, train, flight)  
✅ Intelligent cost calculation with exact formulas  
✅ AI-powered recommendation engine  
✅ Points of Interest (POI) CRUD & proximity search  
✅ Multi-stop route optimization (TSP, ≤7 stops)  
✅ Trip saving & analytics  
✅ MapService abstraction (Google Maps + Fake for testing)  
✅ Caching & rate limiting  
✅ Comprehensive test coverage  
✅ Background job support  

## Stack

- **Framework:** Laravel 10
- **Database:** MySQL 8
- **Authentication:** JWT (tymon/jwt-auth)
- **Caching:** Redis (optional, falls back to file)
- **Task Queue:** Redis (optional, falls back to sync)
- **Testing:** PHPUnit

## Setup

### Prerequisites

- PHP 8.1+
- MySQL 8.0+
- Redis (optional)
- Composer

### Installation

```bash
cd backend

# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=routeiq
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed sample data
php artisan routeiq:seed-sample

# Start the server
php artisan serve --port=8000
```

## Environment Variables

```env
# App
APP_NAME=RouteIQ
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=routeiq
DB_USERNAME=root
DB_PASSWORD=

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# JWT
JWT_SECRET=your-jwt-secret-here
JWT_ALGORITHM=HS256
JWT_TTL=60

# Map Service
MAPS_PROVIDER=fake  # or 'google'
GOOGLE_MAPS_API_KEY=your-api-key-here

# Frontend URL (CORS)
FRONTEND_URL=http://localhost:5173
```

## API Endpoints

### Authentication

```bash
# Register
POST /api/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password",
  "phone": "9876543210"
}

# Login
POST /api/auth/login
{
  "email": "john@example.com",
  "password": "password"
}

# Logout (requires auth)
POST /api/auth/logout

# Refresh token (requires auth)
POST /api/auth/refresh

# Get user profile (requires auth)
GET /api/user
```

### Route Calculation

```bash
# Calculate route
POST /api/route/calc
{
  "points": [
    {"lat": 28.6139, "lng": 77.2090},
    {"lat": 19.0760, "lng": 72.8777}
  ],
  "preference": "fastest",
  "mode": "car"
}

# Parse polyline
POST /api/route/parse-geometry
{
  "polyline": "encoded_polyline_string"
}
```

### Cost Calculation

```bash
# Calculate costs for all modes
POST /api/calc-cost
{
  "route": {
    "distance_meters": 100000,
    "duration_seconds": 7200,
    "polyline": "..."
  },
  "mode_preferences": ["car", "train", "flight"],
  "passengers": 2,
  "vehicle": {
    "type": "petrol",
    "fuel_efficiency_km_per_l": 15,
    "fuel_price_per_l": 95
  },
  "options": {
    "tolls_included": true,
    "fuel_price_override": null,
    "employment_surcharge": 0.0
  }
}

# Response
{
  "status": "success",
  "data": {
    "costs": {
      "car": {
        "mode": "car",
        "total_cost": 1234.50,
        "breakdown": {
          "fuel_cost": 666.67,
          "driver_fare": 500.00,
          "tolls": 5.00,
          "taxes": 58.33,
          "per_passenger": 617.25
        },
        "assumptions": {...},
        "explanation": "..."
      }
    }
  }
}
```

### AI Recommendation

```bash
# Get AI recommendation
POST /api/recommend
{
  "route": {...},
  "mode_preferences": ["car", "ev", "train", "flight"],
  "passengers": 2,
  "vehicle": {...},
  "options": {...}
}

# Response
{
  "status": "success",
  "data": {
    "best_mode": "ev",
    "best_score": 0.92,
    "ranking": [
      {
        "mode": "ev",
        "score": 0.92,
        "total_cost": 1100.00,
        "reason": "Low energy cost; eco-friendly"
      },
      {...}
    ],
    "explanation": "We recommend EV for this 100 km journey..."
  }
}
```

### POI Management

```bash
# List POIs
GET /api/pois?page=1&per_page=20

# Get POI details
GET /api/pois/{id}

# Create POI (admin)
POST /api/pois
{
  "name": "EV Charger Station",
  "type": "charger",
  "latitude": 28.6139,
  "longitude": 77.2090,
  "address": "Delhi, India",
  "attributes": {
    "plug_types": ["Type-2", "CCS"],
    "power_kW": 50
  }
}

# Update POI
PUT /api/pois/{id}
{...}

# Delete POI
DELETE /api/pois/{id}

# Find POIs near route
POST /api/pois/near-route
{
  "polyline": "encoded_polyline",
  "radius_meters": 5000,
  "types": ["charger", "fuel", "restaurant"]
}
```

### Multi-stop Optimization

```bash
# Optimize multi-stop route
POST /api/optimize/multi-stop
{
  "origin": {"lat": 28.6139, "lng": 77.2090},
  "destination": {"lat": 19.0760, "lng": 72.8777},
  "waypoints": [
    {"lat": 28.4089, "lng": 77.3178, "name": "Noida"},
    {"lat": 28.5244, "lng": 77.1855, "name": "Gurugram"}
  ],
  "optimize_for": "distance"
}

# Response
{
  "status": "success",
  "data": {
    "ordered_waypoints": [...],
    "total_distance_m": 250000,
    "total_duration_s": 9000,
    "explanation": "Optimized route for 2 waypoints"
  }
}
```

### Trip Management

```bash
# Save trip
POST /api/trips/save
{
  "name": "Delhi to Mumbai",
  "origin": {"lat": 28.6139, "lng": 77.2090, "address": "Delhi"},
  "destination": {"lat": 19.0760, "lng": 72.8777, "address": "Mumbai"},
  "distance_meters": 1400000,
  "duration_seconds": 50400,
  "mode": "flight",
  "cost": {...},
  "passengers": 2
}

# List user trips
GET /api/trips?page=1&per_page=20

# Get trip details
GET /api/trips/{id}

# Delete trip
DELETE /api/trips/{id}
```

### Analytics

```bash
# Get dashboard summary
GET /api/analytics/summary

# Response
{
  "status": "success",
  "data": {
    "total_trips": 42,
    "total_distance_km": 5250.00,
    "total_cost": 45000.00,
    "most_used_mode": "car",
    "avg_cost_per_km": 8.57,
    "mode_usage": [
      {"mode": "car", "count": 25},
      {"mode": "train", "count": 12},
      {"mode": "flight", "count": 5}
    ],
    "monthly_stats": [
      {
        "month": "2025-11",
        "trips": 10,
        "distance_km": 1200,
        "cost": 10000
      }
    ]
  }
}
```

## Cost Calculation Formulas

### Car (Petrol/Diesel)

```
distance_km = distance_meters / 1000
fuel_needed_l = distance_km / fuel_efficiency_km_per_l
fuel_cost = fuel_needed_l * fuel_price_per_l
driver_cost = distance_km * driver_rate_per_km (default ₹10/km)
toll_cost = (distance_km / 100) * 5  (₹5 per 100km)
taxes = (fuel_cost + driver_cost) * 0.05
total_cost = fuel_cost + driver_cost + toll_cost + taxes
per_passenger = total_cost / passengers
```

Example:
```
distance: 100km, passengers: 2, fuel_efficiency: 15 km/l, fuel_price: ₹95/l
distance_km = 100
fuel_needed_l = 100 / 15 = 6.667l
fuel_cost = 6.667 * 95 = ₹633.33
driver_cost = 100 * 10 = ₹1000
toll_cost = (100/100) * 5 = ₹5
taxes = (633.33 + 1000) * 0.05 = ₹81.67
total = 633.33 + 1000 + 5 + 81.67 = ₹1720
per_passenger = 1720 / 2 = ₹860
```

### EV

```
energy_kwh = distance_km * ev_kwh_per_km * charging_loss_factor (1.1)
energy_cost = energy_kwh * price_per_kwh
battery_degradation = distance_km * 0.5 (₹0.5/km)
maintenance_cost = distance_km * 0.2 (₹0.2/km)
total_cost = energy_cost + battery_degradation + maintenance_cost
```

### Train

```
base_fare = ₹100
fare_per_km = ₹2/km (slab: ₹1.5/km for >200km, ₹1.2/km for >500km)
total_per_person = base_fare + (distance_km * fare_per_km)
booking_charge = ₹50 per person
taxes = 3%
total_cost = (total_per_person * passengers) + booking_charge + taxes
```

### Flight

```
base_airport_fee = ₹200
cost_per_km = ₹2.5/km
avg_tax_per_person = ₹500
fuel_surcharge = distance_km * 0.1 * passengers
total_per_person = base_airport_fee + (distance_km * 2.5) + avg_tax
total_cost = (total_per_person * passengers) + fuel_surcharge
```

## Database Schema

### Users
```sql
- id (PK)
- name
- email (UNIQUE)
- phone
- email_verified_at
- password
- settings (JSON)
- remember_token
- timestamps
```

### Vehicles
```sql
- id (PK)
- user_id (FK)
- type (ENUM: petrol, diesel, ev)
- fuel_efficiency_km_per_l (DECIMAL)
- ev_kwh_per_km (DECIMAL)
- fuel_price_per_l (DECIMAL)
- ev_price_per_kwh (DECIMAL)
- capacity_passengers (INT)
- timestamps
```

### Trips
```sql
- id (PK)
- user_id (FK, nullable)
- name
- origin (JSON: {lat, lng, address})
- destination (JSON: {lat, lng, address})
- waypoints (JSON: array)
- polyline (TEXT)
- distance_meters (INT)
- duration_seconds (INT)
- mode (ENUM: car, ev, train, flight, auto)
- cost (JSON: {car: {...}, ev: {...}, ...})
- passengers (INT)
- saved_preferences (JSON)
- timestamps
```

### POIs
```sql
- id (PK)
- name
- type (ENUM: temple, fuel, charger, toll, restaurant, hospital, other)
- latitude (DECIMAL)
- longitude (DECIMAL)
- address
- tags (JSON)
- attributes (JSON)
- created_by (FK, nullable)
- timestamps
- SPATIAL INDEX on (latitude, longitude)
```

### CostConfigs
```sql
- id (PK)
- key (UNIQUE)
- value (JSON)
- effective_from (DATE)
- effective_to (DATE)
- active (BOOLEAN)
- timestamps
```

### AnalyticsEvents
```sql
- id (PK)
- user_id (FK)
- event_type
- meta (JSON)
- timestamps
```

### APILogs
```sql
- id (PK)
- user_id (FK)
- endpoint
- payload (JSON)
- response (JSON)
- status_code (INT)
- duration_ms (INT)
- timestamps
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage
php artisan test --coverage

# Run without database reset
php artisan test --without-migrations
```

### Test Coverage

- ✅ Auth (register, login, logout, refresh)
- ✅ Route calculation (with MapServiceFake)
- ✅ Cost calculation (deterministic formulas)
- ✅ TSP optimization (3 and 7 waypoints)
- ✅ POI near-route queries
- ✅ Recommendation engine
- ✅ Trip management
- ✅ Analytics queries

## Artisan Commands

```bash
# Seed complete sample data
php artisan routeiq:seed-sample

# Recalculate POI spatial indexes
php artisan routeiq:recalc-pois

# Create JWT secret
php artisan jwt:secret
```

## MapService Implementation

### Using Fake Service (Development/Testing)

Default configuration uses `MapServiceFake` which returns deterministic results:

```php
// In .env
MAPS_PROVIDER=fake
```

### Using Google Maps (Production)

```php
// In .env
MAPS_PROVIDER=google
GOOGLE_MAPS_API_KEY=your-api-key-here
```

### Switching Providers

Register in `config/services.php`:

```php
'maps' => [
    'provider' => env('MAPS_PROVIDER', 'fake'),
],
```

Bind in `AppServiceProvider`:

```php
use App\Services\Maps\MapServiceFake;
use App\Services\Maps\MapServiceGoogle;

public function register(): void
{
    if (config('services.maps.provider') === 'google') {
        $this->app->bind(MapServiceInterface::class, MapServiceGoogle::class);
    } else {
        $this->app->bind(MapServiceInterface::class, MapServiceFake::class);
    }
}
```

## Performance Optimization

### Caching

Route calculations are cached for 24 hours:

```php
$cacheKey = hash('sha256', json_encode($request->all()));
return Cache::remember($cacheKey, 86400, function () {
    return $mapService->calculateRoute(...);
});
```

### Database Indexes

- `users(email)` - Fast lookups
- `vehicles(user_id)` - User vehicle queries
- `trips(user_id, created_at)` - Analytics queries
- `pois(type, created_at)` - POI filtering
- `pois` - SPATIAL INDEX on (latitude, longitude)

### Query Optimization

Use Eager Loading to prevent N+1 problems:

```php
Trip::with('user', 'vehicles')->paginate();
```

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `JWT_SECRET`
- [ ] Configure MySQL with proper backups
- [ ] Set up Redis for caching/queues
- [ ] Configure CORS for frontend domain
- [ ] Set `MAPS_PROVIDER=google` with valid API key
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Set up supervisor for queue workers

### Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/routeiq.conf`:

```ini
[program:routeiq-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/routeiq/queue.log
stopwaitsecs=3600
```

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start routeiq-worker:*
```

## Security

- ✅ CORS configured for frontend domain
- ✅ Input validation on all endpoints
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ JWT token expiration (60 minutes)
- ✅ Password hashing with bcrypt
- ✅ Rate limiting on cost/route endpoints
- ✅ Request logging for auditing

## Rate Limiting

Apply to expensive endpoints:

```php
Route::post('/calc-cost', [CostController::class, 'calculateCost'])
    ->middleware('throttle:60,1')  // 60 requests per minute
    ->middleware('auth:api');
```

## Troubleshooting

### JWT Token Expired

```
POST /api/auth/refresh
```

### Database Connection Failed

Check `.env` and ensure MySQL is running:

```bash
mysql -u root -p -e "SELECT 1"
```

### Cache Issues

Clear all caches:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Migration Rollback

```bash
php artisan migrate:rollback
php artisan migrate:refresh --seed
```

## License

Proprietary - RouteIQ

## Support

For API documentation, see `openapi.yaml` or visit `/api/documentation`
