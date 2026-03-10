import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Route, MapPin, NavigationIcon, Plus, Trash2, Loader2, Sparkles, CheckCircle } from 'lucide-react';
import MapView from '../components/MapView';
import ComparisonSummary from '../components/ComparisonSummary';
import optimizeAPI from '../api/optimize';
import axiosClient from '../api/axiosClient';
import { formatKm } from '../utils/formatters';

// Mode recommendation based on stops and distance
function recommendModeByStops({ stopCount, distanceKm }) {
  if (distanceKm > 600) return 'train';

  if (stopCount >= 7) return 'train';
  if (stopCount >= 5) return 'bus';
  if (stopCount >= 3) return 'car_bus';
  return 'car';
}

// NOTE: Multi-stop optimization is for planning only.
// Final route, distance, duration, and cost are recalculated
// by the backend authoritative journey planner.
export default function MultiStopPage() {
  const navigate = useNavigate();
  const [startLocation, setStartLocation] = useState('');
  const [endLocation, setEndLocation] = useState('');
  const [stops, setStops] = useState([{ id: Date.now(), name: '', lat: null, lng: null }]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState(null);
  const [markers, setMarkers] = useState([]);
  const [polylineData, setPolylineData] = useState([]);

  const addStop = () => {
    setStops([...stops, { id: Date.now() + Math.random(), name: '', lat: null, lng: null }]);
  };

  const removeStop = (index) => {
    if (stops.length > 1) {
      setStops(stops.filter((_, i) => i !== index));
    }
  };

  const updateStop = (index, field, value) => {
    console.log('updateStop called:', { index, field, value, currentStops: stops });
    const newStops = [...stops];
    newStops[index][field] = value;
    console.log('newStops:', newStops);
    setStops(newStops);
  };

  // normalize backend response to known shape
  function normalizeOptimizeResponse(data) {
    if (!data || typeof data !== 'object') return null;

    // 1) total distance: try several possible keys
    const total_distance_km =
      Number(data.total_distance_km ?? data.total_distance ?? data.distance_km ?? data.distance ?? (data.route && (data.route.total_distance_km ?? data.route.distance_km ?? data.route.distance))) || 0;

    // 2) ordered stops: several shapes
    let ordered_stops = null;
    if (Array.isArray(data.ordered_stops)) ordered_stops = data.ordered_stops;
    else if (Array.isArray(data.orderedStops)) ordered_stops = data.orderedStops;
    else if (Array.isArray(data.waypoints)) ordered_stops = data.waypoints;
    else if (Array.isArray(data.optimized_order)) ordered_stops = data.optimized_order;
    else if (data.route && Array.isArray(data.route.stops)) ordered_stops = data.route.stops;
    else ordered_stops = [];

    // ensure each stop has lat/lng/name
    ordered_stops = ordered_stops.map((s, idx) => {
      // allow either {lat,lng,name} or {latitude,longitude,label} etc.
      const lat = Number(s.lat ?? s.latitude ?? s.latitude_deg ?? s.lat_deg ?? 0) || 0;
      const lng = Number(s.lng ?? s.longitude ?? s.lon ?? s.lng_deg ?? 0) || 0;
      const name = s.name ?? s.title ?? s.label ?? s.address ?? `Stop ${idx + 1}`;
      return { ...s, lat, lng, name };
    });

    // 3) polyline: backend may return many shapes
    let polyline = null;
    if (Array.isArray(data.polyline)) polyline = data.polyline;
    else if (Array.isArray(data.route?.polyline)) polyline = data.route.polyline;
    else if (Array.isArray(data.geometry?.polyline)) polyline = data.geometry.polyline;
    else if (Array.isArray(data.path)) polyline = data.path;
    else polyline = [];

    // Normalize polyline to array of {lat,lng}
    const normalizedPolyline = (polyline || []).map(p => {
      if (Array.isArray(p)) {
        // [lat,lng] or [lng,lat] — assume [lat,lng] (common in your app)
        return { lat: Number(p[0]), lng: Number(p[1]) };
      }
      return { lat: Number(p.lat ?? p.latitude ?? 0), lng: Number(p.lng ?? p.longitude ?? p.lon ?? 0) };
    }).filter(pt => Number.isFinite(pt.lat) && Number.isFinite(pt.lng));

    return { total_distance_km, ordered_stops, polyline: normalizedPolyline, raw: data };
  }

  const handleOptimize = async (e) => {
    e.preventDefault();
    setError('');

    if (!startLocation) {
      setError('Please enter starting point');
      return;
    }

    if (stops.some((s) => !s.name)) {
      setError('Please fill in all stop names');
      return;
    }

    // Enforce minimum 2 stops for multi-stop optimization
    if (stops.length < 2) {
      setError('Add at least 2 stops to use Multi-Stop optimization.');
      return;
    }

    // Check for duplicate locations (normalized)
    const allNames = [
      startLocation,
      endLocation,
      ...stops.map(s => s.name)
    ]
      .filter(Boolean)
      .map(n => n.trim().toLowerCase());

    const duplicates = allNames.filter(
      (v, i, a) => a.indexOf(v) !== i
    );

    if (duplicates.length > 0) {
      setError(`Duplicate location detected: "${duplicates[0]}". Each location must be unique.`);
      return;
    }

    setLoading(true);
    try {
      // NOTE: Backend is authoritative for geocoding.
      // We geocode all locations, then pass coordinates to optimizer.


      const geocodePlace = async (placeName) => {
        const res = await axiosClient.get(`/geocode?q=${encodeURIComponent(placeName)}`);
        const data = res.data;
        if (!data.ok) {
          throw new Error(`Location not found: "${placeName}"`);
        }
        return { lat: data.lat, lng: data.lng };
      };

      // Geocode start
      const startCoords = await geocodePlace(startLocation);

      // Geocode all stops
      const stopsWithCoords = await Promise.all(
        stops.map(async (stop) => {
          const coords = await geocodePlace(stop.name);
          return {
            name: stop.name,
            lat: coords.lat,
            lng: coords.lng
          };
        })
      );

      // Geocode end if provided
      let endCoords = null;
      if (endLocation) {
        endCoords = await geocodePlace(endLocation);
      }

      // Now call optimize with full coordinates
      const optimizeData = {
        start: startLocation,
        start_coords: startCoords, // Add coordinates for start
        end: endLocation || null,
        end_coords: endCoords, // Add coordinates for end if exists
        stops: stopsWithCoords
      };

      console.log('[MultiStop] Optimize payload:', optimizeData);

      const response = await optimizeAPI.optimizeStops(optimizeData);

      // SAFETY GUARD: Detect if backend returned HTML instead of JSON
      if (typeof response.data === 'string' && response.data.startsWith('<')) {
        throw new Error('Backend returned HTML instead of JSON. API route may be misconfigured. Check /api/trips/optimize-stops endpoint.');
      }

      window.__last_optimize_raw = response.data;
      console.log("[DEBUG] RAW RESPONSE FROM BACKEND:", response.data);
      const raw = response.data ?? response; // defensive
      console.debug('[MultiStop] raw response', raw);

      const normalized = normalizeOptimizeResponse(raw);
      console.debug('[MultiStop] normalized response', normalized);

      // Guard: validate that optimization produced usable data
      if (
        normalized.total_distance_km <= 0 ||
        !normalized.ordered_stops ||
        normalized.ordered_stops.length === 0
      ) {
        throw new Error(
          'Unable to compute route. Please provide valid locations or coordinates.'
        );
      }

      if (!normalized) {
        throw new Error('Invalid response from optimize API');
      }

      // set normalized result for UI
      const recommendedMode = recommendModeByStops({
        stopCount: normalized.ordered_stops.length,
        distanceKm: normalized.total_distance_km,
      });

      // Only show recommendation if we have sufficient stops
      const shouldShowRecommendation = normalized.ordered_stops.length >= 3;

      setResult({
        total_distance_km: normalized.total_distance_km,
        ordered_stops: normalized.ordered_stops,
        algorithm: raw.algorithm || 'Route optimization',
        iterations: raw.iterations || 0,
        note: raw.note || '',
        raw: normalized.raw,
        recommendedMode: shouldShowRecommendation ? recommendedMode : null,
      });

      // set polyline for MapView
      setPolylineData(normalized.polyline || []);

      // Build markers from ordered stops (or fallback to polyline sampling)
      const newMarkers = [];
      if (Array.isArray(normalized.ordered_stops) && normalized.ordered_stops.length > 0) {
        normalized.ordered_stops.forEach((stop, idx) => {
          newMarkers.push({
            lat: stop.lat || (normalized.polyline?.[idx]?.lat) || 28.6139,
            lng: stop.lng || (normalized.polyline?.[idx]?.lng) || 77.209,
            label: `${idx + 1}. ${stop.name}`,
            type: idx === normalized.ordered_stops.length - 1 ? 'destination' : 'stop',
          });
        });
      } else if (normalized.polyline && normalized.polyline.length) {
        // fallback: create three markers: start / mid / end
        const pts = normalized.polyline;
        newMarkers.push({
          lat: pts[0].lat, lng: pts[0].lng, label: 'Start', type: 'source'
        });
        const mid = pts[Math.floor(pts.length / 2)];
        if (mid) newMarkers.push({ lat: mid.lat, lng: mid.lng, label: 'Mid', type: 'marker' });
        const last = pts[pts.length - 1];
        if (last) newMarkers.push({ lat: last.lat, lng: last.lng, label: 'End', type: 'destination' });
      }

      setMarkers(newMarkers);
    } catch (err) {
      // Log full backend error for debugging
      console.error(
        '[MultiStop] Error:',
        err.response?.data || err.message
      );

      // Provide user-friendly error message
      let errorMessage = err.message || 'Failed to optimize route';

      if (err.response?.status === 422) {
        errorMessage = err.response.data?.error || 'Could not optimize route with these locations';
      } else if (err.response?.status === 400) {
        errorMessage = err.response.data?.error || 'Invalid input';
      } else if (err.response?.status === 500) {
        errorMessage = 'Server error - please try again later';
      }

      setError(errorMessage);
      setResult(null);
      setMarkers([]);
      setPolylineData([]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen riq-page-shell pt-16 transition-colors">
      <div className="max-w-[1400px] mx-auto px-6 sm:px-8 lg:px-12 py-10 relative z-10">
        <section className="riq-hero-strip riq-fade-up p-10 sm:p-12 mb-10" style={{
          background: 'linear-gradient(135deg, rgba(99,102,241,0.08), rgba(20,184,166,0.06))',
          boxShadow: '0 4px 24px rgba(0,0,0,0.06)'
        }}>
          <div className="flex items-center gap-4 mb-5">
            <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-teal-500 flex items-center justify-center shadow-xl" style={{
              boxShadow: '0 8px 24px rgba(99,102,241,0.3)'
            }}>
              <Route size={32} strokeWidth={2.5} className="text-white" />
            </div>
            <div>
              <div style={{
                display: 'inline-flex', alignItems: 'center', gap: '0.5rem',
                padding: '0.4rem 1rem', borderRadius: '1.5rem',
                background: 'rgba(99,102,241,0.1)', border: '1px solid rgba(99,102,241,0.2)',
                marginBottom: '0.5rem'
              }}>
                <Sparkles size={14} style={{ color: '#6366f1' }} />
                <span style={{ fontSize: '0.85rem', fontWeight: 700, color: '#6366f1' }}>Optimization Workspace</span>
              </div>
              <h1 className="text-slate-900 dark:text-slate-100 font-black" style={{ fontSize: 'clamp(1.75rem, 3vw, 2.5rem)', lineHeight: 1.2 }}>
                🎯 Multi-Stop Route <span className="riq-gradient-text">Optimizer</span>
              </h1>
            </div>
          </div>
          <p className="text-slate-600 dark:text-slate-300 text-xl mb-5 max-w-3xl" style={{ lineHeight: 1.7 }}>
            🗺️ Add multiple locations and get an optimized stop order. Perfect for road trips with several destinations.
          </p>
          <div className="flex flex-wrap gap-3">
            <span className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 rounded-xl text-base font-bold" style={{
              boxShadow: '0 2px 8px rgba(99,102,241,0.15)'
            }}>
              <Sparkles size={18} /> Smart Routing
            </span>
            <span className="inline-flex items-center gap-2 px-4 py-2 bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 rounded-xl text-base font-bold" style={{
              boxShadow: '0 2px 8px rgba(20,184,166,0.15)'
            }}>
              <CheckCircle size={18} /> Distance Optimized
            </span>
          </div>
        </section>

        <div className="grid lg:grid-cols-3 gap-10">
          {/* Form */}
          <div className="lg:col-span-2">
            <form onSubmit={handleOptimize} className="riq-panel p-8 mb-10 transition-colors" style={{
              boxShadow: '0 4px 20px rgba(0,0,0,0.06)'
            }}>
              <div className="flex items-center gap-3 mb-7">
                <NavigationIcon size={24} style={{ color: '#6366f1' }} />
                <h2 className="text-3xl font-black text-slate-900 dark:text-slate-100">
                  📍 Route Details
                </h2>
              </div>

              {error && (
                <div className="mb-6 p-5 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-xl" style={{
                  boxShadow: '0 2px 12px rgba(239,68,68,0.15)'
                }}>
                  <div className="flex items-center gap-2">
                    <span style={{ fontSize: '1.5rem' }}>⚠️</span>
                    <p className="text-red-700 dark:text-red-300 font-semibold">{error}</p>
                  </div>
                </div>
              )}

              <div className="space-y-5">
                <div>
                  <label className="flex items-center gap-2 text-base font-bold text-slate-700 dark:text-slate-300 mb-2">
                    <MapPin size={18} style={{ color: '#10b981' }} />
                    🏁 Starting Point
                  </label>
                  <input
                    type="text"
                    value={startLocation || ''}
                    onChange={(e) => {
                      console.log('Start location onChange:', e.target.value);
                      setStartLocation(e.target.value);
                    }}
                    onFocus={() => console.log('Start location focused')}
                    placeholder="e.g., New Delhi"
                    autoComplete="off"
                    className="w-full px-5 py-3.5 border-2 border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 text-base font-medium"
                    style={{ boxShadow: '0 2px 8px rgba(0,0,0,0.04)' }}
                  />
                </div>

                <div>
                  <label className="flex items-center gap-2 text-base font-bold text-slate-700 dark:text-slate-300 mb-2">
                    <MapPin size={18} style={{ color: '#f59e0b' }} />
                    🏁 Ending Point (Optional)
                  </label>
                  <input
                    type="text"
                    value={endLocation || ''}
                    onChange={(e) => {
                      console.log('End location onChange:', e.target.value);
                      setEndLocation(e.target.value);
                    }}
                    onFocus={() => console.log('End location focused')}
                    placeholder="e.g., Airport"
                    autoComplete="off"
                    className="w-full px-5 py-3.5 border-2 border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 text-base font-medium"
                    style={{ boxShadow: '0 2px 8px rgba(0,0,0,0.04)' }}
                  />
                </div>

                <div>
                  <div className="flex justify-between items-center mb-4">
                    <label className="flex items-center gap-2 text-base font-bold text-slate-700 dark:text-slate-300">
                      <Route size={18} style={{ color: '#6366f1' }} />
                      🛣️ Stops
                    </label>
                    <button
                      type="button"
                      onClick={addStop}
                      className="inline-flex items-center gap-2 px-4 py-2 bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 rounded-lg hover:bg-teal-200 dark:hover:bg-teal-900/50 transition font-bold text-base"
                      style={{ boxShadow: '0 2px 8px rgba(20,184,166,0.2)' }}
                    >
                      <Plus size={18} /> Add Stop
                    </button>
                  </div>

                  <div className="space-y-3">
                    {stops.map((stop, idx) => (
                      <div key={stop.id} className="flex gap-3 items-center">
                        <div className="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-teal-500 flex items-center justify-center text-white font-bold text-base" style={{
                          boxShadow: '0 2px 8px rgba(99,102,241,0.3)'
                        }}>
                          {idx + 1}
                        </div>
                        <input
                          type="text"
                          value={stop.name || ''}
                          onChange={(e) => {
                            console.log('Input onChange fired:', e.target.value);
                            updateStop(idx, 'name', e.target.value);
                          }}
                          onInput={(e) => console.log('Input onInput fired:', e.target.value)}
                          onFocus={() => console.log('Input focused:', idx)}
                          placeholder={`Stop ${idx + 1} name`}
                          autoComplete="off"
                          className="flex-1 px-5 py-3.5 border-2 border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 dark:focus:ring-teal-500 text-base font-medium"
                          style={{ boxShadow: '0 2px 8px rgba(0,0,0,0.04)', position: 'relative', zIndex: 10 }}
                          data-testid={`stop-input-${idx}`}
                        />

                        {stops.length > 1 && (
                          <button
                            type="button"
                            onClick={() => removeStop(idx)}
                            className="flex-shrink-0 w-10 h-10 flex items-center justify-center text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition"
                            title="Remove this stop"
                          >
                            <Trash2 size={20} />
                          </button>
                        )}
                      </div>
                    ))}
                  </div>
                </div>

                {/* Helper text for guidance */}
                <div className="mb-5 p-4 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl" style={{
                  boxShadow: '0 2px 8px rgba(59,130,246,0.15)'
                }}>
                  <div className="flex items-start gap-2">
                    <span style={{ fontSize: '1.25rem' }}>💡</span>
                    <p className="text-base font-medium text-blue-700 dark:text-blue-300">
                      Enter location names. Backend will geocode and optimize automatically.
                    </p>
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading || !startLocation || stops.length < 2 || stops.some(s => !s.name)}
                  className="w-full py-4 bg-gradient-to-r from-teal-600 to-sky-600 hover:from-teal-700 hover:to-sky-700 text-white rounded-xl transition font-black text-lg disabled:bg-slate-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                  style={{ boxShadow: '0 6px 20px rgba(20,184,166,0.3)' }}
                >
                  {loading ? (
                    <>
                      <Loader2 className="animate-spin" size={22} />
                      Optimizing...
                    </>
                  ) : (
                    <>
                      🚀 Optimize Route
                    </>
                  )}
                </button>

                <p className="text-xs text-slate-500 dark:text-slate-400 text-center mt-3">
                  ℹ️ Multi-Stop optimization provides route planning and cost estimates. Click on a mode below for detailed journey planning.
                </p>
              </div>
            </form>

            {/* Map */}
            {result && (
              <div className="riq-panel riq-map-shell p-6 mb-8">
                <h3 className="text-lg font-semibold text-slate-900 mb-4">Optimized Route</h3>
                <MapView
                  center={polylineData?.[0] ? [polylineData[0].lat, polylineData[0].lng] : [28.6139, 77.209]}
                  zoom={10}
                  markers={markers}
                  polyline={polylineData}
                />
                <div className="mt-6">
                  <p className="text-sm font-medium text-slate-700 mb-3">Optimized Order:</p>
                  <div className="flex flex-wrap gap-2">
                    {result.ordered_stops?.map((stop, idx) => (
                      <div
                        key={idx}
                        className="px-4 py-2 bg-gradient-to-r from-teal-100 to-cyan-50 dark:from-teal-900/40 dark:to-cyan-900/20 text-teal-700 dark:text-teal-300 rounded-full text-sm font-medium border border-teal-200 dark:border-teal-700"
                      >
                        {idx + 1}. {stop.name}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}

            {/* Comparison Summary - Show mode options */}
            {result && result.total_distance_km > 0 && (
              <ComparisonSummary
                source={startLocation}
                destination={endLocation || result.ordered_stops?.[result.ordered_stops.length - 1]?.name}
                distance_km={result.total_distance_km}
                recommendation={{
                  recommended_mode: result.recommendedMode,
                  reason: result.total_distance_km > 600
                    ? 'Very long distance strongly favors train; Train highly optimal for distances >600 km'
                    : result.ordered_stops?.length >= 7
                    ? 'Multiple stops - train provides better comfort and cost efficiency'
                    : result.ordered_stops?.length >= 5
                    ? 'Several stops - bus offers good balance of cost and flexibility'
                    : 'Moderate stops - car provides convenient point-to-point travel'
                }}
                car={{
                  cost: (() => {
                    // More realistic car cost calculation
                    // Base: ₹8/km fuel (assuming 15 km/l @ ₹120/l)
                    // Add: Stop penalties (₹50 per stop for idling/restart)
                    const fuelCost = result.total_distance_km * 8;
                    const stopPenalty = (result.ordered_stops?.length || 0) * 50;
                    return fuelCost + stopPenalty;
                  })(),
                  duration: (() => {
                    // Average 50 km/h + 10 min per stop
                    const drivingTime = (result.total_distance_km / 50) * 60;
                    const stopTime = (result.ordered_stops?.length || 0) * 10;
                    return drivingTime + stopTime;
                  })(),
                }}
                ev={{
                  cost: (() => {
                    // EV: ₹2.5/km electricity (assuming 5 km/kWh @ ₹12.5/kWh)
                    // Add: Charging time cost (₹100 per charging stop)
                    const electricityCost = result.total_distance_km * 2.5;
                    const chargingStops = Math.ceil(result.total_distance_km / 300);
                    const chargingCost = chargingStops * 100;
                    return electricityCost + chargingCost;
                  })(),
                  chargingStops: Math.ceil(result.total_distance_km / 300), // Every 300km
                  duration: (() => {
                    // Similar to car but add charging time (30 min per charge)
                    const drivingTime = (result.total_distance_km / 50) * 60;
                    const stopTime = (result.ordered_stops?.length || 0) * 10;
                    const chargingTime = Math.ceil(result.total_distance_km / 300) * 30;
                    return drivingTime + stopTime + chargingTime;
                  })(),
                  feasible: true
                }}
                train={{
                  cost: (() => {
                    // Train: ₹0.7-1.2/km (sleeper class)
                    // Use ₹1.0/km average + ₹200 base fare
                    const baseFare = 200;
                    const distanceFare = result.total_distance_km * 1.0;
                    return baseFare + distanceFare;
                  })(),
                  duration: (() => {
                    // Average train speed 60 km/h + 30 min buffer per major stop
                    const travelTime = (result.total_distance_km / 60) * 60;
                    const stopBuffer = Math.min((result.ordered_stops?.length || 0) * 30, 180); // Max 3 hours
                    return travelTime + stopBuffer;
                  })(),
                  available: result.total_distance_km > 50 // Trains viable for longer distances
                }}
                bus={{
                  cost: (() => {
                    // Bus: ₹1.5-2.5/km
                    // Use ₹2.0/km average + ₹50 base fare
                    const baseFare = 50;
                    const distanceFare = result.total_distance_km * 2.0;
                    return baseFare + distanceFare;
                  })(),
                  duration: (() => {
                    // Average bus speed 40 km/h + 15 min per stop
                    const travelTime = (result.total_distance_km / 40) * 60;
                    const stopTime = (result.ordered_stops?.length || 0) * 15;
                    return travelTime + stopTime;
                  })(),
                  available: true
                }}
                flight={result.total_distance_km > 500 ? {
                  cost: (() => {
                    // Flight: Base ₹3000 + ₹5/km
                    const baseFare = 3000;
                    const distanceFare = result.total_distance_km * 5;
                    return baseFare + distanceFare;
                  })(),
                  duration: (() => {
                    // Flight speed 700 km/h + 3 hours airport/security time
                    const flightTime = (result.total_distance_km / 700) * 60;
                    const airportTime = 180; // 3 hours buffer
                    return flightTime + airportTime;
                  })(),
                  available: true
                } : null}
              />
            )}
          </div>

          {/* Results Sidebar */}
          <div>
            {result && (
              <div className="riq-panel p-6 sticky top-24 transition-colors">
                <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6">Optimization Results</h3>

                <div className="space-y-4">
                  <div className="p-4 bg-gradient-to-br from-teal-50 to-cyan-100/50 dark:from-teal-900/30 dark:to-cyan-900/10 rounded-lg border border-teal-200 dark:border-teal-700">
                    <p className="text-xs text-slate-600 dark:text-slate-400 uppercase tracking-wide">Total Distance</p>
                    <p className="text-2xl font-bold text-teal-600 dark:text-teal-400">
                      {formatKm(result.total_distance_km ?? 0)}
                    </p>
                  </div>

                  <div className="p-4 bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-900/30 dark:to-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-700">
                    <p className="text-xs text-slate-600 dark:text-slate-400 uppercase tracking-wide">Stops</p>
                    <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                      {result.ordered_stops?.length || 0}
                    </p>
                  </div>

                  {/* Algorithm Info */}
                  {result.algorithm && (
                    <div className="p-4 bg-gradient-to-br from-sky-50 to-blue-100/50 dark:from-sky-900/30 dark:to-blue-900/10 rounded-lg border border-sky-200 dark:border-sky-700">
                      <p className="text-xs text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-1">Algorithm</p>
                      <p className="text-sm font-semibold text-sky-900 dark:text-sky-200">{result.algorithm}</p>
                      {result.iterations > 0 && (
                        <p className="text-xs text-sky-700 dark:text-sky-300 mt-1">
                          {result.iterations} optimization iterations
                        </p>
                      )}
                      {result.note && (
                        <p className="text-xs text-sky-800 dark:text-sky-200 mt-2 italic">{result.note}</p>
                      )}
                    </div>
                  )}
                </div>

                {/* Mode Recommendation (only if minimum stops) */}
                {result.total_distance_km > 0 &&
                  result.ordered_stops.length >= 3 && (
                    <>
                      <div className="mt-6 p-4 bg-gradient-to-br from-amber-50 to-amber-100/50 dark:from-amber-900/30 dark:to-amber-900/10 border border-amber-300 dark:border-amber-700 rounded-lg">
                        <p className="text-xs text-amber-800 dark:text-amber-300 uppercase font-semibold mb-1">
                          Suggested Mode (planning hint only)
                        </p>

                        {result.recommendedMode === 'car' && (
                          <p className="text-lg font-bold text-gray-900">🚗 Car</p>
                        )}

                        {result.recommendedMode === 'car_bus' && (
                          <p className="text-lg font-bold text-gray-900">
                            🚗 Car or 🚌 Bus
                          </p>
                        )}

                        {result.recommendedMode === 'bus' && (
                          <p className="text-lg font-bold text-gray-900">🚌 Bus</p>
                        )}

                        {result.recommendedMode === 'train' && (
                          <p className="text-lg font-bold text-gray-900">🚆 Train</p>
                        )}

                        <p className="text-sm text-slate-600 mt-2">
                          Based on {result.ordered_stops.length} stops and total distance.
                        </p>
                      </div>

                      {/* Mode-Specific Navigation */}
                      <div className="mt-4 space-y-2">
                        {(result.recommendedMode === 'car' || result.recommendedMode === 'car_bus') && (
                          <button
                            onClick={() => {
                              const planIntent = {
                                mode: 'car',
                                source: startLocation,
                                destination: endLocation || stops[stops.length - 1]?.name || 'Round Trip',
                                stops: result.ordered_stops.map(s => s.name)
                              };
                              navigate('/plan', { state: { planIntent } });
                            }}
                            className="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition"
                          >
                            🚗 Continue with Car
                          </button>
                        )}

                        {(result.recommendedMode === 'bus' || result.recommendedMode === 'car_bus') && (
                          <button
                            onClick={() => {
                              const planIntent = {
                                mode: 'bus',
                                source: startLocation,
                                destination: endLocation || stops[stops.length - 1]?.name || 'Round Trip',
                                stops: result.ordered_stops.map(s => s.name)
                              };
                              navigate('/plan', { state: { planIntent } });
                            }}
                            className="w-full py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition"
                          >
                            🚌 Continue with Bus
                          </button>
                        )}

                        {result.recommendedMode === 'train' && (
                          <button
                            onClick={() => {
                              const planIntent = {
                                mode: 'train',
                                source: startLocation,
                                destination: endLocation || stops[stops.length - 1]?.name || 'Round Trip',
                                stops: result.ordered_stops.map(s => s.name)
                              };
                              navigate('/plan', { state: { planIntent } });
                            }}
                            className="w-full py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition"
                          >
                            🚆 Continue with Train
                          </button>
                        )}
                      </div>
                    </>
                  )}

                {/* Message when not enough stops */}
                {result.total_distance_km > 0 &&
                  result.ordered_stops.length > 0 &&
                  result.ordered_stops.length < 3 && (
                    <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                      <p className="text-sm text-blue-800">
                        <strong>ℹ️ Tip:</strong> Add at least 3 stops to get a mode recommendation.
                      </p>
                    </div>
                  )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
