# RouteIQ Frontend - Complete React + Vite Build

A complete, production-ready frontend for RouteIQ - Intelligent Multi-Mode Travel Planner & Cost Analyzer.

## Tech Stack

- **React 18** - UI framework
- **Vite** - Build tool
- **React Router v6** - Client-side routing
- **Tailwind CSS** - Styling
- **Axios** - HTTP client
- **React-Leaflet** - Interactive maps
- **Leaflet** - Map library with OpenStreetMap

## Project Structure

```
src/
├── api/                    # API client modules
│   ├── axiosClient.js     # Axios instance with interceptors
│   ├── auth.js            # Authentication endpoints
│   ├── route.js           # Route planning endpoints
│   ├── cost.js            # Cost calculation endpoints
│   ├── poi.js             # POI endpoints
│   ├── optimize.js        # Route optimization endpoints
│   └── trips.js           # Trip management endpoints
│
├── components/            # Reusable React components
│   ├── Navbar.jsx         # Navigation bar with auth
│   ├── ProtectedRoute.jsx # Route protection HOC
│   ├── MapView.jsx        # Leaflet map component
│   ├── CostCard.jsx       # Cost comparison card
│   ├── RecommendationCard.jsx # Recommended mode card
│   ├── PoiLegend.jsx      # POI legend component
│   └── TripSummaryCard.jsx # Trip card component
│
├── pages/                 # Page components
│   ├── HomePage.jsx       # Landing page
│   ├── LoginPage.jsx      # User login
│   ├── RegisterPage.jsx   # User registration
│   ├── PlanTripPage.jsx   # Trip planning interface
│   ├── MultiStopPage.jsx  # Multi-stop optimizer
│   ├── TripsListPage.jsx  # User's trips list
│   └── AnalyticsPage.jsx  # Analytics dashboard
│
├── context/               # React Context
│   ├── AuthContext.jsx    # Global auth state
│   └── TripContext.jsx    # Global trip state
│
├── hooks/                 # Custom React hooks
│   └── useAuth.js         # useAuth hook
│
├── utils/                 # Utility functions
│   ├── formatters.js      # Currency, distance formatters
│   └── helpers.js         # Helper functions
│
├── App.jsx                # Root app component with routing
├── main.jsx               # Entry point
└── index.css              # Global styles + Tailwind
```

## Features Implemented

### 1. Authentication System
- User registration with email/password
- Login with email/password
- Token-based auth (localStorage)
- Protected routes with loading state
- Automatic redirect on 401

### 2. Trip Planning
- Source & destination input
- Passenger count selection
- Real-time route planning
- Distance & duration display
- Interactive map with markers and polylines

### 3. Cost Analysis
- Multi-mode cost comparison (Car, EV, Train, Flight)
- Cost breakdown display
- AI-powered recommendations
- Cost per kilometer calculation

### 4. Points of Interest (POIs)
- Auto-discovery along route
- 6 POI categories (temple, fuel, charger, toll, restaurant, hospital)
- Color-coded map markers
- POI legend display

### 5. Multi-Stop Optimization
- Dynamic stop addition/removal
- Route optimization algorithm
- Ordered stop display
- Total distance calculation

### 6. Trip Management
- Save planned trips
- View all trips
- Trip summary cards
- Trip filtering and sorting

### 7. Analytics Dashboard
- Total trips count
- Total distance traveled
- Total cost spent
- Most used transportation mode
- Average cost per kilometer
- Mode usage breakdown
- Monthly statistics table

### 8. Responsive Design
- Mobile-friendly UI
- Tablet and desktop layouts
- Touch-friendly buttons
- Responsive navigation

## Installation & Setup

### Prerequisites
- Node.js 16+ and npm/yarn
- Backend API running on `http://127.0.0.1:8000`

### Installation Steps

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install

# or with yarn
yarn install
```

### Install Additional Dependencies

The frontend requires these packages (should be in package.json):

```bash
npm install react-router-dom axios leaflet react-leaflet
```

### Development

```bash
# Start Vite dev server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

The app will be available at `http://localhost:5173` (default Vite port)

## API Configuration

The frontend expects the backend API at:
```
http://127.0.0.1:8000/api
```

Update this in `src/api/axiosClient.js` if your backend is on a different port.

### Authentication Flow

1. User registers/logs in
2. Backend returns `token` and `user` object
3. Token stored in `localStorage` as `routeiq_token`
4. Token automatically added to all requests via Axios interceptor
5. On 401 response, token cleared and user redirected to login

## Component API Reference

### MapView Component
```jsx
<MapView
  center={[28.6139, 77.209]}  // [lat, lng]
  zoom={12}
  markers={[
    { lat: 28.6139, lng: 77.209, label: "Start", type: "source" },
    { lat: 28.7041, lng: 77.1025, label: "End", type: "destination" },
    { lat: 28.5, lng: 77.1, label: "Fuel Station", type: "poi-fuel" }
  ]}
  polyline={[
    { lat: 28.6139, lng: 77.209 },
    { lat: 28.6150, lng: 77.2100 },
    // ... route points
  ]}
/>
```

### CostCard Component
```jsx
<CostCard
  mode="car"
  cost={450}
  duration={120}
  selected={false}
  onSelect={() => setSelectedMode('car')}
/>
```

### RecommendationCard Component
```jsx
<RecommendationCard
  mode="train"
  reason="Most economical option for this distance"
  cost={200}
  distance={250}
  duration={180}
/>
```

## API Endpoints Expected

### Auth
- `POST /register` → Register user
- `POST /login` → Login user
- `GET /me` → Get current user
- `POST /logout` → Logout user

### Routes & Trips
- `POST /trips/plan` → Plan route
- `POST /trips/calc-cost` → Calculate costs
- `POST /pois/for-route` → Get POIs for route
- `POST /trips/optimize-stops` → Optimize multi-stop route
- `POST /trips/save` → Save trip
- `GET /trips` → Get all trips
- `GET /trips/{id}` → Get trip details

### Analytics
- `GET /dashboard/summary` → Get dashboard statistics

## Styling

All components use Tailwind CSS utilities:
- Color palette: `slate` (neutral), `blue` (primary), `green`/`red` (accents)
- Rounded corners: `rounded-lg`
- Shadows: `shadow-sm`, `shadow-md`
- Responsive prefixes: `md:`, `lg:`

## Performance Optimizations

- Route-based code splitting with React Router
- Lazy loading of heavy components
- Memoization of expensive calculations
- Debounced API calls (can be added)
- Optimized re-renders with useContext

## Browser Support

- Chrome/Edge 88+
- Firefox 85+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Android)

## Future Enhancements

- Location autocomplete using Google Places API
- Real-time traffic data
- Weather along route
- Multiple route comparison
- Trip history with dates
- Export trip as PDF
- Social sharing features
- Offline support with service workers

## Troubleshooting

### Token not persisting
Check that `localStorage` is enabled in browser. Token is stored as `routeiq_token`.

### API errors (CORS)
Ensure backend has CORS enabled for `http://localhost:5173`.

### Map not loading
Check that Leaflet CSS is imported and tiles are loading from OpenStreetMap.

### Page shows "Loading..." forever
Check browser console for network errors and ensure backend is running.

## Environment Variables

Create a `.env` file in the `frontend/` directory:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_MAP_CENTER_LAT=28.6139
VITE_MAP_CENTER_LNG=77.2090
```

Update these values as needed for your deployment.

## License

MIT

## Support

For issues or feature requests, please contact the development team.
