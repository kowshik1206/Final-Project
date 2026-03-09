import React, { useState } from 'react';
import { MapContainer, TileLayer, Polyline, Popup, CircleMarker } from 'react-leaflet';
import L from 'leaflet';

export default function PlanRoute() {
  const [origin, setOrigin] = useState('');
  const [destination, setDestination] = useState('');
  const [route, setRoute] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Common Indian cities with coords (lon, lat)
  const cityCoords = {
    Delhi: '77.1025,28.7041',
    Mumbai: '72.8777,19.0760',
    Bangalore: '77.5946,12.9716',
    Hyderabad: '78.4744,17.3850',
    Pune: '73.8567,18.5204',
    Chennai: '80.2707,13.0827',
    Kolkata: '88.3639,22.5726',
    Jaipur: '75.7873,26.9124',
    Ahmedabad: '72.5479,23.0225',
    Lucknow: '80.9462,26.8467',
  };

  const handlePlanRoute = async () => {
    if (!origin || !destination) {
      setError('Please enter both origin and destination');
      return;
    }

    setLoading(true);
    setError('');
    setRoute(null);

    try {
      const apiBase = window.location.hostname === 'localhost' 
        ? 'http://localhost/RouteIQ/backend/public'
        : '/api';

      const response = await fetch(
        `${apiBase}/plan_route.php?from=${origin}&to=${destination}`
      );

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();

      if (!data.ok) {
        setError(data.error || 'Failed to plan route');
        return;
      }

      setRoute(data.route);
    } catch (err) {
      setError(`Error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const formatDistance = (meters) => {
    const km = (meters / 1000).toFixed(2);
    return `${km} km`;
  };

  const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours}h ${minutes}m`;
  };

  const parseCoordinates = (coords) => {
    const [lng, lat] = coords.split(',').map(Number);
    return { lat, lng };
  };

  return (
    <div className="min-h-screen bg-slate-50 p-6">
      <div className="max-w-7xl mx-auto">
        <h1 className="text-4xl font-bold text-slate-900 mb-8">Plan Your Route</h1>

        <div className="grid lg:grid-cols-3 gap-6">
          {/* Left Panel - Input */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-md p-6 space-y-4">
              <h2 className="text-xl font-semibold text-slate-900">Route Details</h2>

              {/* Origin Input */}
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  Origin
                </label>
                <select
                  value={origin}
                  onChange={(e) => setOrigin(e.target.value)}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Select origin city</option>
                  {Object.entries(cityCoords).map(([city, coords]) => (
                    <option key={city} value={coords}>
                      {city}
                    </option>
                  ))}
                </select>
              </div>

              {/* Destination Input */}
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-2">
                  Destination
                </label>
                <select
                  value={destination}
                  onChange={(e) => setDestination(e.target.value)}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Select destination city</option>
                  {Object.entries(cityCoords).map(([city, coords]) => (
                    <option key={city} value={coords}>
                      {city}
                    </option>
                  ))}
                </select>
              </div>

              {/* Plan Button */}
              <button
                onClick={handlePlanRoute}
                disabled={loading}
                className="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:bg-slate-400 transition font-semibold"
              >
                {loading ? 'Planning...' : 'Plan Route'}
              </button>

              {/* Error Display */}
              {error && (
                <div className="bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
                  {error}
                </div>
              )}

              {/* Route Details */}
              {route && (
                <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg space-y-3">
                  <h3 className="font-semibold text-slate-900">Route Summary</h3>
                  <div className="space-y-2 text-sm">
                    <p>
                      <strong>Distance:</strong> {formatDistance(route.distance_m)}
                    </p>
                    <p>
                      <strong>Duration:</strong> {formatDuration(route.duration_s)}
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Right Panel - Map */}
          <div className="lg:col-span-2">
            {route ? (
              <MapContainer
                center={[route.from.lat, route.from.lng]}
                zoom={6}
                className="w-full h-96 rounded-lg shadow-md"
              >
                <TileLayer
                  url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                  attribution='&copy; OpenStreetMap'
                />

                {/* Origin Marker */}
                <CircleMarker
                  center={[route.from.lat, route.from.lng]}
                  radius={8}
                  fillColor="green"
                  color="darkgreen"
                  weight={2}
                  fillOpacity={0.8}
                >
                  <Popup>Origin</Popup>
                </CircleMarker>

                {/* Destination Marker */}
                <CircleMarker
                  center={[route.to.lat, route.to.lng]}
                  radius={8}
                  fillColor="red"
                  color="darkred"
                  weight={2}
                  fillOpacity={0.8}
                >
                  <Popup>Destination</Popup>
                </CircleMarker>

                {/* Route Polyline */}
                {route.geometry && route.geometry.coordinates && (
                  <Polyline
                    positions={route.geometry.coordinates.map(([lng, lat]) => [lat, lng])}
                    color="blue"
                    weight={3}
                    opacity={0.8}
                  />
                )}
              </MapContainer>
            ) : (
              <div className="w-full h-96 bg-slate-200 rounded-lg shadow-md flex items-center justify-center">
                <p className="text-slate-600 text-center">
                  Select origin and destination, then plan your route to see the map
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
