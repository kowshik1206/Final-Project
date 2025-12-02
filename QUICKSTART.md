# 🚀 RouteIQ - Quick Start Guide

Get the complete RouteIQ platform running in 5 minutes.

## Frontend (React + Vite + Tailwind)

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev

# Open browser
# http://localhost:5173
```

**Frontend Status**: ✅ Production-ready, all pages implemented

---

## Backend (Laravel + MySQL)

### Quick Setup

```bash
cd backend

# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 3. Create database
mysql -u root -p -e "CREATE DATABASE routeiq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Run migrations
php artisan migrate

# 5. Seed sample data (optional)
php artisan db:seed

# 6. Start server
php artisan serve

# Open browser
# http://localhost:8000/api/health
```

### Start Services

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Redis (for caching/queue)
redis-server

# Terminal 3: Queue worker
php artisan queue:work
```

**Backend Status**: ✅ Production-ready, all endpoints tested

---

## Test the API

### Register & Login

```bash
# 1. Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "9876543210"
  }'

# Response includes JWT token
# {
#   "status": "success",
#   "data": {
#     "access_token": "eyJ0eXAi...",
#     "token_type": "Bearer",
#     "user": {...}
#   }
# }

# Save the token as: TOKEN="eyJ0eXAi..."

# 2. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Calculate Cost

```bash
curl -X POST http://localhost:8000/api/cost/calculate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "route": {
      "distance_meters": 100000,
      "duration_seconds": 7200,
      "polyline": "test",
      "points": []
    },
    "vehicle_type": "petrol",
    "modes": ["car", "train", "flight"],
    "passengers": 2,
    "options": {}
  }'

# Response: Cost breakdown for all modes
```

### Get AI Recommendation

```bash
curl -X POST http://localhost:8000/api/cost/recommend \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "route": {
      "distance_meters": 100000,
      "duration_seconds": 7200,
      "polyline": "test",
      "points": []
    },
    "vehicle_type": "petrol",
    "passengers": 2,
    "options": {}
  }'

# Response: Best mode recommendation with explanation
```

### Optimize Multi-Stop Route

```bash
curl -X POST http://localhost:8000/api/optimize/multi-stop \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "origin": {"lat": 28.6139, "lng": 77.2090},
    "destination": {"lat": 28.5244, "lng": 77.1855},
    "waypoints": [
      {"lat": 28.6142, "lng": 77.2100},
      {"lat": 28.5500, "lng": 77.2000}
    ],
    "optimize_for": "distance"
  }'

# Response: Optimized waypoint order with total distance
```

---

## Run Tests

```bash
# All tests
php artisan test

# With coverage
php artisan test --coverage

# Specific test file
php artisan test tests/Feature/AuthTest.php

# Watch mode (re-run on changes)
php artisan test --watch
```

---

## Environment Variables

Edit `.env` to customize:

```env
# Maps (choose: 'fake' for testing, 'google' for production)
MAPS_PROVIDER=fake
# GOOGLE_MAPS_API_KEY=your_key_here

# Cost formulas (INR)
CAR_FUEL_PRICE_PER_L=95
EV_PRICE_PER_KWH=15
TRAIN_BASE_FARE=100
FLIGHT_AIRPORT_FEE=200

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# JWT
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

---

## Project Structure

```
RouteIQ/
├── frontend/                    (React + Vite + Tailwind)
│   ├── src/
│   │   ├── components/          (40+ React components)
│   │   ├── pages/               (6 pages)
│   │   ├── context/             (Auth + Trip context)
│   │   ├── api/                 (Axios services)
│   │   └── utils/               (Helpers + formatters)
│   ├── package.json
│   └── vite.config.js
│
└── backend/                     (Laravel + MySQL)
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/     (7 controllers, 30+ endpoints)
    │   │   └── Requests/        (5 FormRequest validation)
    │   ├── Models/              (7 Eloquent models)
    │   ├── Services/            (6 business logic services)
    │   └── Console/Commands/    (2 Artisan commands)
    ├── database/
    │   ├── migrations/          (7 migrations)
    │   └── seeders/             (2 seeders)
    ├── tests/
    │   ├── Feature/             (6 feature test files, 40+ cases)
    │   └── Unit/                (Service unit tests)
    ├── routes/
    │   └── api.php              (30+ endpoints)
    ├── .env.example
    ├── README.md
    ├── SETUP.md
    └── BACKEND_COMPLETE.md
```

---

## Key Features

### 🚗 Route Optimization
- Calculate routes between multiple points
- Support for waypoints optimization (TSP)
- Haversine-based distance calculations

### 💰 Cost Calculation
- 4 transportation modes: Car, EV, Train, Flight
- Exact formulas per specification
- Configurable pricing parameters

### 🤖 AI Recommendations
- Weighted scoring algorithm (cost + time + convenience)
- Human-readable explanations
- Mode ranking with rationale

### 🗺️ Points of Interest
- 50+ POIs across major Indian routes
- Fuel stations, EV chargers, restaurants, hospitals, temples
- Spatial indexing for efficient queries
- Near-route POI discovery

### 📊 Trip Management
- Save and manage trip history
- Cost breakdowns per trip
- Personal analytics dashboard

### 🔐 Security
- JWT-based authentication
- Row-level authorization
- Input validation
- Rate limiting

---

## Troubleshooting

### MySQL Connection Error
```bash
# Start MySQL
# Linux/Mac:
brew services start mysql-server

# Windows:
# Start from Services or XAMPP control panel

# Verify
mysql -u root -p
```

### Redis Connection Error
```bash
# Start Redis
redis-server

# Or use file cache
# Edit .env: CACHE_DRIVER=file
```

### Port Already in Use
```bash
# Change Laravel port
php artisan serve --port=8001

# Change frontend port (vite.config.js)
# Configure port in Vite config
```

### JWT Secret Not Set
```bash
# Generate
php artisan jwt:secret

# Should output JWT_SECRET in .env
```

---

## Common Commands

```bash
# Database
php artisan migrate                    # Run migrations
php artisan migrate:refresh            # Reset and migrate (dev only)
php artisan db:seed                    # Run seeders
php artisan routeiq:seed-sample        # Seed sample data

# Cache
php artisan cache:clear                # Clear all caches
php artisan config:cache               # Cache config

# Queue
php artisan queue:work                 # Start queue worker
php artisan queue:failed                # Show failed jobs
php artisan queue:retry all            # Retry failed jobs

# Testing
php artisan test                       # Run all tests
php artisan test --coverage            # With coverage report
php artisan test --watch               # Watch mode

# Development
php artisan tinker                     # PHP REPL
php artisan make:migration migration_name   # Create migration
php artisan make:model Model           # Create model
php artisan make:controller Controller  # Create controller
```

---

## API Documentation

### Full Documentation Available

- **Frontend**: `frontend/README_START_HERE.md`
- **Backend**: `backend/SETUP.md` (installation) + `backend/README.md` (API reference)
- **Implementation**: `backend/BACKEND_COMPLETE.md` (technical details)

---

## Deployment Checklist

```
✅ Frontend
  - npm run build
  - Deploy to Vercel/Netlify/AWS S3
  
✅ Backend
  - Set APP_ENV=production
  - Configure production .env
  - Set MAPS_PROVIDER=google
  - Run: php artisan migrate --force
  - Set up Redis for cache/queue
  - Configure Supervisor for queue worker
  - Enable HTTPS
```

---

## Support

### Documentation
- Frontend README: `frontend/README_START_HERE.md`
- Backend Setup: `backend/SETUP.md`
- API Reference: `backend/README.md`
- Implementation: `backend/BACKEND_COMPLETE.md`

### Logs
- Backend: `backend/storage/logs/laravel.log`
- Queue: `backend/storage/logs/queue.log`

### Commands
```bash
# View recent API logs
php artisan tinker
>>> App\Models\ApiLog::latest(10)->get()

# Check migrations
php artisan migrate:status

# Database connections
php artisan db
```

---

## Success Criteria

✅ Frontend runs at http://localhost:5173  
✅ Backend runs at http://localhost:8000  
✅ API health check: GET http://localhost:8000/api/health → "ok"  
✅ Can register user: POST /api/auth/register  
✅ Can login: POST /api/auth/login  
✅ Can calculate costs: POST /api/cost/calculate  
✅ Can optimize routes: POST /api/optimize/multi-stop  
✅ Tests pass: php artisan test  

**All criteria met? 🎉 You're ready to deploy!**

---

**Need help?** Check the detailed guides in the documentation files.

**Ready to deploy?** Follow the deployment checklist above.

**Want to contribute?** Fork, create feature branch, submit PR.

---

**Version**: 1.0  
**Last Updated**: 2024  
**Status**: Production Ready ✅
