# 🚀 RouteIQ Frontend - Quick Reference

## Installation & Running

```bash
# Install dependencies
cd frontend
npm install

# Start development server
npm run dev
# Access at: http://localhost:5173

# Build for production
npm run build

# Preview production build
npm run preview
```

## File Organization

```
src/
├── api/           # 7 API modules with Axios
├── components/    # 7 reusable React components
├── pages/         # 7 page components (routes)
├── context/       # AuthContext, TripContext
├── hooks/         # useAuth custom hook
├── utils/         # formatters, helpers
├── App.jsx        # Root with routing
├── main.jsx       # Entry point
└── index.css      # Global Tailwind styles
```

## 7 Routes

| URL | Component | Protected | Purpose |
|-----|-----------|-----------|---------|
| `/` | HomePage | ❌ | Landing |
| `/login` | LoginPage | ❌ | Login |
| `/register` | RegisterPage | ❌ | Signup |
| `/plan` | PlanTripPage | ✅ | Plan trip |
| `/multi-stop` | MultiStopPage | ✅ | Optimize route |
| `/trips` | TripsListPage | ✅ | View trips |
| `/analytics` | AnalyticsPage | ✅ | Dashboard |

## API Endpoints (Backend Required)

```
POST   /register           → { token, user }
POST   /login              → { token, user }
GET    /me                 → { user }
POST   /logout             → { success }
POST   /trips/plan         → { distance_km, duration_min, polyline, coords }
POST   /trips/calc-cost    → { car, ev, train, flight, recommendation }
POST   /pois/for-route     → { pois: [...] }
POST   /trips/optimize-stops → { ordered_stops, total_distance_km }
POST   /trips/save         → { trip }
GET    /trips              → [trips]
GET    /trips/:id          → { trip }
GET    /dashboard/summary  → { stats, modes, monthly }
```

## Authentication Flow

```javascript
// 1. Login/Register
POST /login with { email, password }
← Receive { token, user }

// 2. Save to localStorage
localStorage.setItem('routeiq_token', token)
localStorage.setItem('routeiq_user', JSON.stringify(user))

// 3. Axios sends with all requests
Authorization: Bearer <token>

// 4. 401 response handler
Clear localStorage & redirect to /login
```

## Key Components Usage

### AuthContext
```jsx
import { useAuth } from './hooks/useAuth';

function MyComponent() {
  const { user, isAuthenticated, login, logout } = useAuth();
  return user ? <p>Hello {user.name}</p> : <p>Not logged in</p>;
}
```

### Protected Routes
```jsx
// Automatically used in App.jsx
// Routes wrapped with ProtectedRoute redirect to /login if not authenticated
<ProtectedRoute>
  <PlanTripPage />
</ProtectedRoute>
```

### MapView
```jsx
import MapView from './components/MapView';

<MapView
  center={[28.7041, 77.1025]}
  zoom={12}
  markers={[
    { type: 'source', coords: [28.7, 77.1], label: 'Start' },
    { type: 'destination', coords: [28.8, 77.2], label: 'End' }
  ]}
  polyline={[[28.7, 77.1], [28.8, 77.2]]}
/>
```

## Formatters

```javascript
import { formatCurrency, formatKm, formatMinutes } from './utils/formatters';

formatCurrency(12500)      // "₹12,500"
formatKm(250.5)            // "250.5 km"
formatMinutes(250)         // "4h 10m"
```

## Common Tasks

### Add New Page
1. Create `src/pages/NewPage.jsx`
2. Add import in `App.jsx`
3. Add route: `<Route path="/new" element={<NewPage />} />`

### Add API Endpoint
1. Create `src/api/newModule.js`
2. Export function: `export default { function: (params) => axiosClient.get(...) }`
3. Import in component and call

### Access Auth State
```javascript
import { useAuth } from './hooks/useAuth';

const { user, token, isAuthenticated } = useAuth();
```

### Show Loading State
```javascript
const [loading, setLoading] = useState(false);

const handleClick = async () => {
  setLoading(true);
  try {
    await apiCall();
  } finally {
    setLoading(false);
  }
};

{loading && <div>Loading...</div>}
```

## Environment Variables

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_API_TIMEOUT=10000
```

Access in code:
```javascript
const baseURL = import.meta.env.VITE_API_BASE_URL;
```

## Tailwind Classes Quick Ref

```jsx
// Layout
<div className="max-w-7xl mx-auto px-4">

// Responsive
<div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">

// Colors
className="bg-blue-600 text-white hover:bg-blue-700"

// Spacing
className="p-4 mb-6 space-y-4"

// Typography
className="text-2xl font-bold text-slate-900"

// Utilities
className="rounded-lg shadow-md border border-slate-200"
```

## State Management

### AuthContext
- `user` - Current user object
- `token` - JWT token string
- `isAuthenticated` - Boolean
- `loading` - Initial load state
- `login(email, password)` - Login method
- `register(name, email, password)` - Register method
- `logout()` - Logout method

### TripContext
- `currentTrip` - Current trip object
- `setCurrentTrip(trip)` - Update current trip

## Debugging

```javascript
// Check auth in console
localStorage.getItem('routeiq_token')
localStorage.getItem('routeiq_user')

// Check API calls
// Open Network tab in DevTools
// Look for Authorization header: "Bearer <token>"

// Check component state
// Use React DevTools extension
// Inspect props and context values
```

## Scripts

```bash
npm run dev              # Development server (HMR enabled)
npm run build           # Production build
npm run preview         # Preview prod build
npm run lint            # Check code quality
npm run format          # Auto-format code
```

## Responsive Breakpoints

- Mobile: default (< 768px)
- Tablet: `md:` (768px+)
- Desktop: `lg:` (1024px+)
- Large: `xl:` (1280px+)

```jsx
<div className="md:hidden">Mobile only</div>
<div className="hidden md:flex">Desktop+</div>
```

## Colors

Primary: `#2563eb` (blue-600)
Secondary: `#64748b` (slate-500)
Success: `#16a34a` (green-600)
Error: `#dc2626` (red-600)

## Performance Tips

1. Use `React.memo()` for expensive components
2. Use `useCallback()` for event handlers
3. Avoid inline object creation in JSX
4. Lazy load pages with `React.lazy()`
5. Use `loading` spinner while fetching

## Deployment

```bash
# 1. Build
npm run build

# 2. Deploy dist/ folder to:
# - Netlify: Drag & drop dist/
# - Vercel: `vercel` command
# - Traditional server: FTP/SCP dist/ contents
```

## Troubleshooting

| Error | Solution |
|-------|----------|
| 401 Unauthorized | Check localStorage for token, verify backend auth |
| Cannot GET /api | Backend not running, check API_BASE_URL |
| Map not showing | Ensure Leaflet CSS loaded in index.css |
| Styles not applied | Restart dev server after installing Tailwind |
| Form validation error | Check helpers.js exists and is imported |

## Full Documentation

- `SETUP_COMPLETE.md` - Complete setup guide
- `IMPLEMENTATION_GUIDE.md` - Detailed implementation
- `FRONTEND_README.md` - Feature overview

---

**Status**: ✅ Ready to Use
**Total Files**: 40+
**Lines of Code**: 5,000+
**Tech Stack**: React 18 + Vite + Tailwind + Leaflet
