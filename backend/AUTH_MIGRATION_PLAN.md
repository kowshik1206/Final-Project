# RouteIQ - Authentication Migration Plan

## When to Add Authentication

**ADD AUTHENTICATION ONLY AFTER:**
- ✅ All public features (route planning, map, contacts, uploads) are 100% working
- ✅ Smoke tests pass (php smoke_test.php --mode=full)
- ✅ Frontend components load and save/display data correctly
- ✅ You've thoroughly tested the app as a public site

---

## Step 1: Create User Registration & Login Endpoints

### Copy the auth template:
```bash
cp backend/public/auth.php.template backend/public/auth.php
```

### Run migrations to add password_hash to users table:
```sql
ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) AFTER email;
ALTER TABLE users ADD UNIQUE INDEX idx_email_unique (email);
```

---

## Step 2: Update Public Endpoints to Accept Optional user_id

Update these files to check `$_SESSION['user_id']` if set:

### backend/public/save_trip.php
```php
// After successful route, capture user_id:
$userId = $_SESSION['user_id'] ?? null;

$stmt = $db->prepare("
    INSERT INTO trips 
    (title, origin_lat, origin_lng, dest_lat, dest_lng, distance_m, duration_s, geometry_geojson, user_id, created_at) 
    VALUES 
    (:title, :origin_lat, :origin_lng, :dest_lat, :dest_lng, :distance_m, :duration_s, :geometry, :user_id, NOW())
");

$stmt->execute([
    // ... existing bindings ...
    ':user_id' => $userId,
]);
```

### backend/public/upload_profile.php
```php
$userId = $_SESSION['user_id'] ?? null;

$stmt = $db->prepare("
    INSERT INTO uploads (filename, path, mime_type, size_bytes, user_id, uploaded_at) 
    VALUES (:filename, :path, :mime_type, :size_bytes, :user_id, NOW())
");

$stmt->execute([
    ':filename' => $fileName,
    ':path' => 'uploads/test/' . $uniqueName,
    ':mime_type' => $fileMime,
    ':size_bytes' => $fileSize,
    ':user_id' => $userId,
]);
```

### backend/public/contact.php
No change needed (contact form always anonymous).

---

## Step 3: Create Protected Endpoints

Create these new files for auth-only operations:

### backend/public/save_booking.php
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
session_start();
require_once 'db.php';

// REQUIRE AUTH
if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$tripId = (int)($input['trip_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($tripId <= 0) {
    json_response(['ok' => false, 'error' => 'Invalid trip_id'], 400);
}

try {
    $db = get_db_connection();
    
    // Verify trip exists
    $tripStmt = $db->prepare("SELECT id FROM trips WHERE id = :id");
    $tripStmt->execute([':id' => $tripId]);
    if (!$tripStmt->fetch()) {
        json_response(['ok' => false, 'error' => 'Trip not found'], 404);
    }
    
    // Create booking
    $bookStmt = $db->prepare("
        INSERT INTO bookings (trip_id, user_id, status, created_at) 
        VALUES (:trip_id, :user_id, 'pending', NOW())
    ");
    $bookStmt->execute([
        ':trip_id' => $tripId,
        ':user_id' => $userId,
    ]);
    
    $bookingId = $db->lastInsertId();
    json_response([
        'ok' => true,
        'booking_id' => (int)$bookingId,
        'status' => 'pending',
    ], 201);
    
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
?>
```

### backend/public/user_trips.php
```php
<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
session_start();
require_once 'db.php';

// REQUIRE AUTH
if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];
$limit = (int)($_GET['limit'] ?? 20);
if ($limit > 100) $limit = 100;

try {
    $db = get_db_connection();
    
    $stmt = $db->prepare("
        SELECT id, title, origin_lat, origin_lng, dest_lat, dest_lng, 
               distance_m, duration_s, created_at 
        FROM trips 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $trips = $stmt->fetchAll();
    json_response(['ok' => true, 'trips' => $trips], 200);
    
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
?>
```

---

## Step 4: Create React Login/Register Components

### frontend/src/pages/LoginPage.jsx
```jsx
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const apiBase = window.location.hostname === 'localhost' 
        ? 'http://localhost/RouteIQ/backend/public'
        : '/api';

      const response = await fetch(`${apiBase}/auth.php?action=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
        credentials: 'include',
      });

      if (!response.ok) {
        throw new Error('Login failed');
      }

      const data = await response.json();
      if (data.ok) {
        localStorage.setItem('user', JSON.stringify(data.user));
        navigate('/plan');
      } else {
        setError(data.error);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-6">
      <div className="bg-white rounded-lg shadow-md p-8 max-w-md w-full">
        <h1 className="text-3xl font-bold text-slate-900 mb-6 text-center">Login</h1>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg mb-4 text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2">Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-2">Password</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:bg-slate-400 font-semibold"
          >
            {loading ? 'Logging in...' : 'Login'}
          </button>
        </form>

        <p className="text-center text-sm text-slate-600 mt-4">
          Don't have an account? <a href="/register" className="text-blue-600 hover:text-blue-700">Register here</a>
        </p>
      </div>
    </div>
  );
}
```

### frontend/src/pages/RegisterPage.jsx
Similar structure to LoginPage, but calls `auth.php?action=register` and has name field.

---

## Step 5: Update Frontend Routes to Check Auth

Update `frontend/src/App.jsx`:
```jsx
import ProtectedRoute from './components/ProtectedRoute';

<Route 
  path="/bookings" 
  element={
    <ProtectedRoute>
      <BookingsPage />
    </ProtectedRoute>
  } 
/>
```

---

## Step 6: Add Rate Limiting to plan_route.php

```php
// After validating coordinates, add rate limit check:
if (isset($_SESSION['user_id'])) {
    // Limit authenticated users: 100 requests/hour
    $key = "rate_limit_plan_" . $_SESSION['user_id'];
    $count = apcu_fetch($key) ?? 0;
    if ($count >= 100) {
        json_response(['ok' => false, 'error' => 'Rate limit exceeded'], 429);
    }
    apcu_store($key, $count + 1, 3600);
} else {
    // Limit anonymous users: 10 requests/hour per IP
    $key = "rate_limit_anon_" . $_SERVER['REMOTE_ADDR'];
    $count = apcu_fetch($key) ?? 0;
    if ($count >= 10) {
        json_response(['ok' => false, 'error' => 'Rate limit exceeded'], 429);
    }
    apcu_store($key, $count + 1, 3600);
}
```

---

## Step 7: Add Payment Webhook Endpoint (Future)

Create `backend/public/webhook_payment.php`:
```php
<?php
// Handle payment gateway webhooks (Stripe, Razorpay, etc.)
// Update booking status from 'pending' -> 'paid'
// Trigger email notifications

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = $input['booking_id'] ?? null;

if ($input['status'] === 'paid') {
    $db = get_db_connection();
    $stmt = $db->prepare("UPDATE bookings SET status = 'paid' WHERE id = :id");
    $stmt->execute([':id' => $bookingId]);
    json_response(['ok' => true], 200);
}
?>
```

---

## Checklist: From Public to Auth-Protected

- [ ] Copy `auth.php.template` → `auth.php`
- [ ] Run user table migration (add password_hash)
- [ ] Test registration: `curl -X POST -H "Content-Type: application/json" -d '{"name":"John","email":"john@test.com","password":"pass123"}' http://localhost/RouteIQ/backend/public/auth.php?action=register`
- [ ] Test login endpoint
- [ ] Update `save_trip.php` to capture `user_id` from session
- [ ] Update `upload_profile.php` to capture `user_id` from session
- [ ] Create `save_booking.php` (protected endpoint)
- [ ] Create `user_trips.php` (protected endpoint)
- [ ] Add React LoginPage and RegisterPage
- [ ] Test protected routes with auth token/session
- [ ] Add rate limiting to `plan_route.php`
- [ ] Smoke tests pass with auth enabled: `php smoke_test.php --mode=full`

---

## Rollback if Needed

If auth causes issues before completion, revert:
```bash
# Remove auth.php
rm backend/public/auth.php

# Revert user table:
ALTER TABLE users DROP COLUMN password_hash;
ALTER TABLE users DROP INDEX idx_email_unique;

# App is back to fully-public mode
```

All public features continue to work anonymously. Auth adds *user identity* only; doesn't break public access.
