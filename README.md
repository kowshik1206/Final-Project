# RouteIQ — Intelligent Multi-Mode Travel Planner & Cost Analyzer

RouteIQ is a comprehensive trip planning and route optimization platform that calculates fuel costs, finds optimal stops, and recommends the best travel mode (car, train, or flight) for any journey. Built with React + Vite frontend and custom PHP backend, it serves travelers and fleet managers who need data-driven trip planning.

## Tech Stack

**Frontend:**
- React 18 + Vite (dev server, bundling)
- Tailwind CSS (styling)
- Leaflet + React Leaflet (interactive maps)
- Axios (HTTP client)

**Backend:**
- Custom PHP (no framework)
- MySQL database
- Native REST API routing

**Infrastructure:**
- XAMPP (Apache + PHP + MySQL)
- Node.js 16+ (frontend build/dev)

## How to Run

### Quick Start (One Command Each)

**Terminal 1 — Start Backend:**
```powershell
cd C:\xampp\htdocs\RouteIQ\backend\public
C:\xampp\php\php.exe -S 127.0.0.1:8000 router.php
```

**Terminal 2 — Start Frontend:**
```powershell
cd C:\xampp\htdocs\RouteIQ\frontend
npm install  # (first time only)
npm run dev
```

Then open: **http://localhost:5173** (or 5174)

## Database Setup

### Database Credentials

- **Name:** `routeiq`
- **Host:** `127.0.0.1` (localhost)
- **Port:** `3306`
- **User:** `root`
- **Password:** (empty)

### Create Database & Import Schema

**Option A: Using MySQL Command Line**

```powershell
# Create database
C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE IF NOT EXISTS routeiq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
C:\xampp\mysql\bin\mysql -u root routeiq < C:\xampp\htdocs\RouteIQ\backend\schema.sql
```

**Option B: Using phpMyAdmin GUI**

1. Open **http://localhost/phpmyadmin**
2. Click **"New"** → Create database `routeiq`
3. Set **Collation** to `utf8mb4_unicode_ci`
4. Click **"Create"**
5. Select `routeiq` database
6. Go to **"Import"** tab
7. Upload file: `backend/schema.sql`
8. Click **"Import"**

### Schema Details

- **Tables auto-create** via `CREATE TABLE IF NOT EXISTS` in `schema.sql`
- **Core tables:** users, trips, bookings, uploads, vehicles, pois, etc.
- **Indexes:** On frequently queried columns (user_id, created_at, trip_id)
- **Charset:** UTF-8 MB4 (supports emojis and international characters)

### Full Setup Guide

For detailed prerequisites, database setup, and troubleshooting, see [HOW_TO_RUN.md](HOW_TO_RUN.md).

## v1 Features

### Trip Planning
- Multi-mode routing (car, train, flight)
- Route visualization on interactive map
- Turn-by-turn directions

### Cost Analysis
- Fuel cost calculation (petrol, diesel, CNG, EV)
- Compare costs across transport modes
- Display per-liter efficiency and total expense

### Intelligent Stops
- Automatic stop calculation based on vehicle range
- Comfort-based stop intervals (adjustable for families)
- Point of Interest (POI) search (fuel, food, hospitals, temples)
- Stop suggestion and pinning

### Trip Management
- Save trips with selected stops
- View trip history
- Persistent trip data with stops metadata

### Vehicle Management
- Register vehicles by fuel type and specifications
- Query vehicle profiles by efficiency
- Support for EV (electric vehicles)

### Route Optimization
- Multi-stop route optimization
- POI scoring and filtering
- Distance and rating-based recommendations

## Explicitly Excluded (Not v1)

- User authentication/accounts
- Real-time traffic data
- Navigation turn-by-turn guidance
- Booking/payment integration
- Machine learning recommendations
- Mobile app (web-only for v1)
- Multi-language support
- Offline maps
- Social features

## Project Structure

```
RouteIQ/
├── backend/           PHP API (custom router, no framework)
│   ├── public/        Entry point (index.php, router.php)
│   ├── api/           Route handlers (plan_route.php, calc_cost.php, etc.)
│   └── db.php         Database connection
├── frontend/          React + Vite SPA
│   ├── src/           JSX components, pages, utilities
│   └── package.json   npm scripts
├── HOW_TO_RUN.md      Step-by-step startup guide
└── README.md          This file
```

## Environment Variables

See `.env.example` files in frontend and backend folders for configuration options.

## v1 Status

✅ **Complete and defensible** — All core features implemented with proper edge case handling, environment-based configuration, and single-source-of-truth logic.

---

**Version:** 1.0.0  
**Last Updated:** December 21, 2025
