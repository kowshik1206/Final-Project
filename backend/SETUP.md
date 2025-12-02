# RouteIQ Backend Setup Guide

Complete setup instructions for the RouteIQ Laravel backend.

## ✅ Prerequisites

- **PHP**: 8.2+ with extensions: ctype, curl, dom, filter, hash, intl, json, mbstring, openssl, pdo, pdo_mysql, pcre, reflection, session, tokenizer, xml, xmlwriter
- **MySQL**: 8.0+ with spatial index support
- **Redis**: 6.0+ (for caching and queues, optional but recommended)
- **Composer**: Latest version
- **Node.js**: 18+ (for frontend development)

## 🚀 Installation Steps

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/RouteIQ.git
cd RouteIQ/backend
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Environment Configuration

```bash
# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

Edit `.env` and configure:

```env
# Application
APP_NAME=RouteIQ
APP_ENV=local
APP_DEBUG=true

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

# Maps (choose: 'fake' for testing, 'google' for production)
MAPS_PROVIDER=fake
# GOOGLE_MAPS_API_KEY=your_key_here

# JWT
JWT_ALGORITHM=HS256
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### 4. Create Database

```bash
# Create MySQL database
mysql -u root -p -e "CREATE DATABASE routeiq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Run Migrations

```bash
php artisan migrate
```

This creates 7 tables:
- `users` - User accounts with JWT support
- `vehicles` - Vehicle profiles per user
- `trips` - Saved trips with costs
- `pois` - Points of Interest with spatial indexing
- `cost_configs` - Configurable pricing parameters
- `analytics_events` - Event tracking
- `api_logs` - Request/response audit trail

### 6. Seed Sample Data (Optional)

```bash
# Seed all data (users, vehicles, trips, POIs, cost configs)
php artisan db:seed

# Or use custom command
php artisan routeiq:seed-sample
```

This creates:
- 5 sample users with different vehicles
- 10+ sample trips
- 50+ POIs (fuel stations, chargers, tolls, restaurants, hospitals, temples)
- Cost configuration presets

### 7. Start Development Server

```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Redis (if available)
redis-server

# Terminal 3: Start queue worker (for background jobs)
php artisan queue:work
```

Server runs at: `http://localhost:8000`

## 📋 API Endpoints Overview

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - User login (returns JWT token)
- `POST /api/auth/logout` - Logout user
- `POST /api/auth/refresh` - Refresh JWT token
- `GET /api/auth/me` - Get user profile

### Route Calculation
- `POST /api/route/calc` - Calculate route between points
- `POST /api/route/parse-geometry` - Parse polyline geometry

### Cost Calculation
- `POST /api/cost/calculate` - Calculate costs for all modes
- `POST /api/cost/recommend` - Get AI recommendation

### Multi-Stop Optimization
- `POST /api/optimize/multi-stop` - Optimize waypoint order (TSP)

### Points of Interest
- `GET /api/pois` - List POIs (with filtering)
- `POST /api/pois` - Create new POI
- `GET /api/pois/{id}` - Get specific POI
- `PUT /api/pois/{id}` - Update POI
- `DELETE /api/pois/{id}` - Delete POI
- `POST /api/pois/near-route` - Find POIs near a route

### Trip Management
- `GET /api/trips` - List user's trips
- `POST /api/trips` - Save new trip
- `GET /api/trips/{id}` - Get trip details
- `DELETE /api/trips/{id}` - Delete trip

### Analytics
- `GET /api/analytics/summary` - Get user analytics summary

## 🧪 Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage report
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run unit tests only
php artisan test tests/Unit

# Run feature tests only
php artisan test tests/Feature
```

### Test Coverage

- **Feature Tests**: All API endpoints, authentication flow, authorization
- **Unit Tests**: Cost formulas, TSP algorithm, service calculations
- **Coverage Goal**: >80% across core services

Tests included:
- `AuthTest` - Authentication and JWT flow
- `CostTest` - Cost calculation for all transportation modes
- `OptimizeTest` - Multi-stop TSP optimization
- `PoiTest` - POI CRUD and spatial queries
- `TripTest` - Trip management and authorization
- `CostServiceTest` - Service layer business logic

## 🔧 Configuration

### Cost Formulas (Configurable via Database)

**Car (Petrol/Diesel)**:
```
fuel_cost = (distance_km / efficiency_km_per_l) * fuel_price_per_l
driver_cost = distance_km * driver_cost_per_km (₹10/km)
toll_cost = (distance_km / 100) * 5
tax = 5% of subtotal
total = fuel_cost + driver_cost + toll_cost + tax
```

**EV (Electric Vehicle)**:
```
energy_cost = distance_km * kwh_per_km * charging_loss * price_per_kwh
degradation = distance_km * battery_degradation_per_km (₹0.5/km)
maintenance = distance_km * maintenance_per_km (₹0.2/km)
total = energy_cost + degradation + maintenance
```

**Train**:
```
base_fare = ₹100
rate = ₹2/km (standard)
if distance > 200km: rate = ₹1.5/km
if distance > 500km: rate = ₹1.2/km
total = base_fare + (distance_km * rate)
```

**Flight**:
```
airport_fee = ₹200
distance_cost = distance_km * 2.5
tax = ₹500
fuel_surcharge = 10% of total
total = airport_fee + distance_cost + tax + fuel_surcharge
```

### AI Recommendation Algorithm

Weighted scoring (0-1 scale):
```
score = (0.5 * cost_score) + (0.3 * time_score) + (0.2 * convenience_score)

where:
- cost_score = 1 - (mode_cost / max_cost)
- time_score = 1 - (mode_time / max_time)
- convenience_score = based on mode type and distance
```

### TSP Optimization

- **≤7 waypoints**: Brute-force permutation evaluation (optimal)
- **>7 waypoints**: En-route heuristic with 2-opt improvement
- **Distance Calculation**: Haversine formula (great-circle distance)

## 📦 Deployment

### Production Checklist

```
□ Set APP_ENV=production
□ Set APP_DEBUG=false
□ Enable HTTPS
□ Configure MAPS_PROVIDER=google with API key
□ Set up Redis for caching
□ Configure queue worker with Supervisor
□ Enable Redis for sessions
□ Set up database backups
□ Enable rate limiting
□ Configure CORS appropriately
□ Use .env.production with secure values
□ Test all endpoints with load testing
□ Set up monitoring/alerts
```

### Docker Deployment

```dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip

RUN docker-php-ext-install pdo pdo_mysql zip

WORKDIR /app

COPY composer.lock composer.json ./
RUN composer install --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

RUN php artisan migrate --force
RUN php artisan config:cache
RUN php artisan route:cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
```

### Supervisor Configuration (for Queue Worker)

Create `/etc/supervisor/conf.d/routeiq-worker.conf`:

```ini
[program:routeiq-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/routeiq/backend/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/routeiq/backend/storage/logs/queue-worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start routeiq-worker:*
```

## 🗺️ Map Services

### Using Fake Service (Development/Testing)

Default for local development. Provides deterministic, reproducible results.

```bash
MAPS_PROVIDER=fake
```

### Switching to Google Maps (Production)

```bash
MAPS_PROVIDER=google
GOOGLE_MAPS_API_KEY=your_api_key_here
```

To switch at runtime:

```php
// In controller or service
$mapService = config('services.maps.provider') === 'google'
    ? new MapServiceGoogle()
    : new MapServiceFake();
```

## 🔐 Security Best Practices

1. **JWT Secrets**: Never commit JWT secret to version control
2. **API Keys**: Store Google Maps key in `.env.production`
3. **Rate Limiting**: Expensive endpoints throttled (30 req/min for route/cost, 20 for optimize, 10 for analytics)
4. **Input Validation**: All endpoints validate input via FormRequest classes
5. **Authentication**: JWT-based with token refresh mechanism
6. **Authorization**: Row-level authorization on user resources
7. **CORS**: Configure `cors.php` for frontend domain
8. **HTTPS**: Required in production (enforce via middleware)
9. **Logging**: All API requests logged to `api_logs` table
10. **Database**: Use prepared statements via Eloquent ORM

## 📊 Monitoring & Logging

### View Recent API Logs

```bash
php artisan tinker
>>> App\Models\ApiLog::latest()->limit(10)->get()
```

### Analytics Dashboard (Sample Query)

```bash
php artisan tinker
>>> App\Models\Trip::where('user_id', 1)->selectRaw('mode, count(*) as count')->groupBy('mode')->get()
```

### Redis Cache Stats

```bash
redis-cli info stats
```

## 🐛 Troubleshooting

### JWT Token Expired
- Refresh token: `POST /api/auth/refresh`
- Configure `JWT_TTL` in `.env` (default: 60 minutes)

### Database Connection Error
- Verify MySQL is running: `mysql -u root -p`
- Check `DB_*` variables in `.env`
- Ensure database exists: `SHOW DATABASES;`

### Redis Connection Failed
- Start Redis: `redis-server`
- Or use file cache: `CACHE_DRIVER=file` in `.env`

### Route Caching Issues
- Clear all caches: `php artisan cache:clear`
- Rebuild config: `php artisan config:cache`

### Queue Not Processing
- Start queue worker: `php artisan queue:work`
- Check queue failed: `php artisan queue:failed`
- Retry failed jobs: `php artisan queue:retry all`

### Spatial Index Not Working
- Ensure MySQL version ≥ 8.0
- Verify SPATIAL INDEX created: `SHOW INDEXES FROM pois;`
- Recalculate indices: `php artisan routeiq:recalc-pois`

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [JWT-Auth Package](https://github.com/tymondesigns/jwt-auth)
- [MySQL Spatial Extensions](https://dev.mysql.com/doc/refman/8.0/en/spatial-extensions.html)
- [Redis Caching](https://redis.io/docs/)
- [Haversine Formula](https://en.wikipedia.org/wiki/Haversine_formula)

## 📞 Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Run tests: `php artisan test`
3. Review `.env` configuration
4. Check database schema: `php artisan migrate:status`

---

**Setup Complete!** 🎉

Your RouteIQ backend is now ready for development and testing.
