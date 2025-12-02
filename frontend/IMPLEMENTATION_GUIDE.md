# RouteIQ Frontend - Complete Implementation Guide

## Overview

This document provides a complete guide to the RouteIQ frontend implementation built with React, Vite, Tailwind CSS, and Leaflet maps.

## ✅ What's Been Built

### 1. API Layer (`src/api/`)
- ✅ `axiosClient.js` - Axios instance with auth interceptors
- ✅ `auth.js` - Login, register, logout, getMe
- ✅ `route.js` - planRoute endpoint
- ✅ `cost.js` - calculateCost endpoint
- ✅ `poi.js` - getPoisForRoute endpoint
- ✅ `optimize.js` - optimizeStops endpoint
- ✅ `trips.js` - saveTrip, getTrips, getTrip, getDashboardSummary

### 2. Components (`src/components/`)
- ✅ `Navbar.jsx` - Responsive navigation with mobile menu
- ✅ `ProtectedRoute.jsx` - Route protection with loading state
- ✅ `MapView.jsx` - Leaflet map with markers and polylines
- ✅ `CostCard.jsx` - Transportation mode cost card
- ✅ `RecommendationCard.jsx` - Highlighted recommendation
- ✅ `PoiLegend.jsx` - POI type legend
- ✅ `TripSummaryCard.jsx` - Trip history card

### 3. Pages (`src/pages/`)
- ✅ `HomePage.jsx` - Landing page with hero & features
- ✅ `LoginPage.jsx` - User login form
- ✅ `RegisterPage.jsx` - User registration form
- ✅ `PlanTripPage.jsx` - Main trip planning interface
- ✅ `MultiStopPage.jsx` - Multi-stop optimizer
- ✅ `TripsListPage.jsx` - User's saved trips
- ✅ `AnalyticsPage.jsx` - Analytics dashboard

### 4. Context & Hooks (`src/context/`, `src/hooks/`)
- ✅ `AuthContext.jsx` - Global auth state management
- ✅ `TripContext.jsx` - Global trip state management
- ✅ `useAuth.js` - Custom auth hook

### 5. Utils (`src/utils/`)
- ✅ `formatters.js` - Currency, distance, time formatters
- ✅ `helpers.js` - Email validation, distance calculations

### 6. Core Files
- ✅ `App.jsx` - Root component with routing
- ✅ `main.jsx` - Entry point
- ✅ `index.css` - Global Tailwind styles

### 7. Configuration Files
- ✅ `vite.config.js` - Vite build config
- ✅ `tailwind.config.js` - Tailwind configuration
- ✅ `postcss.config.js` - PostCSS config

## 🚀 Quick Start

### Prerequisites
```bash
- Node.js 16+
- npm or yarn
- Backend API running on http://127.0.0.1:8000
```

### Installation

1. **Navigate to frontend directory:**
```bash
cd frontend
```

2. **Install dependencies:**
```bash
npm install react-router-dom axios leaflet react-leaflet
npm install -D tailwindcss postcss autoprefixer @vitejs/plugin-react
```

3. **Start development server:**
```bash
npm run dev
```

4. **Access the app:**
```
http://localhost:5173
```

## 🔑 Key Features

### Authentication Flow
```
1. User visits "/" (HomePage)
   ↓
2. User clicks "Get Started" → /register or /login
   ↓
3. Form submission → API call
   ↓
4. Token + User saved to localStorage
   ↓
5. Redirect to /plan (PlanTripPage)
   ↓
6. User redirects to home/auth protected routes
```

### Trip Planning Flow
```
1. User fills: source, destination, passengers
   ↓
2. Click "Plan Route"
   ↓
3. API calls:
   - planRoute() → get distance, duration, polyline, coords
   - calculateCost() → get costs for all modes
   - getPoisForRoute() → get POI markers
   ↓
4. Display:
   - Map with markers and polyline
   - Cost cards (selectable)
   - Recommendation card
   ↓
5. User selects mode + clicks "Save Trip"
   ↓
6. saveTrip() API call
   ↓
7. Redirect to /trips
```

### Multi-Stop Optimization Flow
```
1. User fills: start, end (optional), stops list
   ↓
2. Click "Optimize Route"
   ↓
3. optimizeStops() API call
   ↓
4. Display:
   - Map with ordered stop markers
   - Total distance
   - Optimized order
   ↓
5. User can save route (optional implementation)
```

## 📁 File Structure Explanation

### API Layer Pattern
Each module in `src/api/` follows the same pattern:
```javascript
import axiosClient from './axiosClient';

const moduleAPI = {
  method: (params) => axiosClient.method('/endpoint', params),
};

export default moduleAPI;
```

### Component Patterns

**Functional Components with Hooks:**
```jsx
import { useState, useEffect } from 'react';

export default function ComponentName() {
  const [state, setState] = useState(null);
  
  useEffect(() => {
    // side effects
  }, [dependencies]);
  
  return <div>JSX</div>;
}
```

**Using Custom Hooks:**
```jsx
import { useAuth } from '../hooks/useAuth';

export default function Component() {
  const { isAuthenticated, user, login } = useAuth();
  // ...
}
```

## 🎨 Tailwind CSS Classes Used

### Layout
- `min-h-screen` - Full viewport height
- `max-w-7xl` - Container width
- `mx-auto` - Center container
- `px-4 sm:px-6 lg:px-8` - Responsive padding

### Typography
- `text-4xl font-bold` - Large headings
- `text-slate-900` - Dark text
- `text-slate-600` - Muted text
- `uppercase tracking-wide` - Caps + letter spacing

### Colors
- `bg-blue-600` - Primary button
- `text-blue-600` - Links
- `bg-green-600` - Success actions
- `bg-red-600` - Destructive actions
- `bg-slate-50` - Light backgrounds
- `bg-white` - Cards

### Spacing
- `mb-4` - Margin bottom
- `space-y-4` - Vertical spacing
- `gap-6` - Grid gaps
- `p-6` - Padding

### Effects
- `rounded-lg` - Border radius
- `shadow-sm` - Light shadow
- `shadow-md` - Medium shadow
- `border border-slate-200` - Borders
- `hover:bg-blue-700` - Hover states
- `transition` - Smooth transitions

### Responsive
- `grid md:grid-cols-2 lg:grid-cols-3` - Responsive grid
- `hidden md:flex` - Hidden on mobile, flex on tablet+

## 🔄 Data Flow

### Auth Context Flow
```
AuthProvider (top-level)
    ↓
checks localStorage for routeiq_token
    ↓
if exists, calls getMe() to fetch user
    ↓
updates state: user, token, isAuthenticated, loading
    ↓
provides to all children via AuthContext
    ↓
useAuth() hook accesses context anywhere
```

### Trip Context Flow
```
TripProvider wraps app
    ↓
stores currentTrip state
    ↓
used by PlanTripPage to store planned trip
    ↓
can be accessed from other pages if needed
```

## 🗺️ Map Component Details

### Marker Types with Colors
- `source` (blue 🔵) - Starting point
- `destination` (red 🔴) - End point
- `poi-temple` (amber 🟡) - Religious site
- `poi-fuel` (gray ⚫) - Gas station
- `poi-charger` (green 🟢) - EV charger
- `poi-toll` (purple 🟣) - Toll booth
- `poi-restaurant` (pink 🔵) - Food
- `poi-hospital` (red 🔴) - Medical

### Map Features
- OpenStreetMap tiles (free, no API key)
- Zoom level control
- Pan & scroll support
- Responsive height (h-96)
- Popup on marker click

## 🛣️ Routing Structure

```
/                  → HomePage (public)
/login             → LoginPage (public)
/register          → RegisterPage (public)
/plan              → PlanTripPage (protected)
/multi-stop        → MultiStopPage (protected)
/trips             → TripsListPage (protected)
/analytics         → AnalyticsPage (protected)
/*                 → Redirect to /
```

Protected routes show loading spinner if not authenticated.

## 🔐 Authentication Flow Details

### Token Management
```javascript
// Stored in localStorage
localStorage.getItem('routeiq_token')
localStorage.getItem('routeiq_user')

// Sent with all requests
Authorization: Bearer <token>

// Cleared on 401
if (response.status === 401) {
  localStorage.removeItem('routeiq_token');
  redirect to /login
}
```

### Login/Register
```javascript
// User enters credentials
→ POST /register or /login
← Backend returns { token, user }
→ Save to localStorage
→ Update AuthContext
→ Redirect to /plan
```

## 📊 Analytics Implementation

### Dashboard Summary Endpoint Response
```javascript
{
  total_trips: 10,
  total_distance_km: 2500,
  total_cost: 15000,
  most_used_mode: "car",
  avg_cost_per_km: 6,
  mode_usage: [
    { mode: "car", count: 7 },
    { mode: "train", count: 2 },
    { mode: "flight", count: 1 }
  ],
  monthly_stats: [
    { month: "Oct 2024", trips: 5, distance_km: 1200, cost: 7000 },
    { month: "Nov 2024", trips: 5, distance_km: 1300, cost: 8000 }
  ]
}
```

## 🧪 Testing Checklist

- [ ] Can register new account
- [ ] Can login with credentials
- [ ] Token persists after page refresh
- [ ] Can access protected pages
- [ ] Redirect to login if token expired
- [ ] Can plan a trip
- [ ] Map displays correctly
- [ ] Cost cards show all modes
- [ ] Can select transportation mode
- [ ] Can save trip
- [ ] Can view trips list
- [ ] Can view analytics
- [ ] Can optimize multi-stop route
- [ ] Responsive on mobile
- [ ] Navbar mobile menu works

## 🐛 Debugging Tips

### Check Auth State
```javascript
// In browser console
localStorage.getItem('routeiq_token')
localStorage.getItem('routeiq_user')
```

### Check API Calls
```javascript
// Network tab in DevTools
// Check if Authorization header is present
// Check if CORS issues exist
```

### Check Component State
```javascript
// Use React DevTools
// Inspect props and state of components
// Check context values
```

## 📦 Build for Production

```bash
npm run build
# Creates dist/ folder with optimized build

npm run preview
# Preview production build locally
```

## 🚨 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Token not sent in requests | Check localStorage key is `routeiq_token` |
| CORS errors | Enable CORS in backend for `http://localhost:5173` |
| Map not loading | Ensure Leaflet CSS is imported in index.css |
| 401 redirects to blank page | ProtectedRoute component should handle loading |
| Page shows "Loading..." forever | Backend not responding, check network tab |
| Tailwind styles not applied | Rebuild with `npm run dev` after installing |

## 🔗 Backend Integration Checklist

- [ ] Backend API running on `http://127.0.0.1:8000`
- [ ] `/register` endpoint returns `{ token, user }`
- [ ] `/login` endpoint returns `{ token, user }`
- [ ] `/me` endpoint returns current `user` object
- [ ] Token auth works (Bearer token in header)
- [ ] `/trips/plan` returns `{ distance_km, duration_min, polyline, source_coords, destination_coords }`
- [ ] `/trips/calc-cost` returns `{ car, ev, train, flight, recommendation }`
- [ ] `/pois/for-route` returns `{ pois: [...] }`
- [ ] `/trips/optimize-stops` returns `{ ordered_stops, total_distance_km }`
- [ ] `/trips/save` accepts trip data and saves
- [ ] `/trips` returns array of trips
- [ ] `/dashboard/summary` returns analytics data

## 📝 Environment Setup

Create `.env` file in `frontend/` directory:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_API_TIMEOUT=10000
```

Update `axiosClient.js` to use:
```javascript
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api';
```

## 🎓 Learning Resources

- React Docs: https://react.dev
- React Router: https://reactrouter.com
- Tailwind CSS: https://tailwindcss.com
- Leaflet: https://leafletjs.com
- React-Leaflet: https://react-leaflet.js.org
- Vite: https://vitejs.dev

## ✨ What's Next

1. Connect to actual backend API
2. Test all API endpoints
3. Handle error cases gracefully
4. Add loading states for all API calls
5. Add success/error toast notifications
6. Implement location autocomplete
7. Add trip filtering/sorting
8. Implement pagination for trips list
9. Add export to PDF functionality
10. Deploy to production

## 📞 Support

For issues or questions:
1. Check the browser console for errors
2. Check the Network tab in DevTools
3. Review the API response format
4. Ensure backend is running
5. Check localStorage for token persistence
