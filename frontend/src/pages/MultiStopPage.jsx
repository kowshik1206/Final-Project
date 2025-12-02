import React, { useState } from 'react';
import MapView from '../components/MapView';
import optimizeAPI from '../api/optimize';
import { formatKm } from '../utils/formatters';

export default function MultiStopPage() {
  const [startLocation, setStartLocation] = useState('');
  const [endLocation, setEndLocation] = useState('');
  const [stops, setStops] = useState([{ name: '', lat: null, lng: null }]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState(null);
  const [markers, setMarkers] = useState([]);
  const [polylineData, setPolylineData] = useState([]);

  const addStop = () => {
    setStops([...stops, { name: '', lat: null, lng: null }]);
  };

  const removeStop = (index) => {
    if (stops.length > 1) {
      setStops(stops.filter((_, i) => i !== index));
    }
  };

  const updateStop = (index, field, value) => {
    const newStops = [...stops];
    newStops[index][field] = value;
    setStops(newStops);
  };

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

    setLoading(true);
    try {
      const optimizeData = {
        start: startLocation,
        end: endLocation || null,
        stops: stops.map((s) => ({
          lat: s.lat || 28.6139,
          lng: s.lng || 77.209,
          name: s.name,
        })),
      };

      const response = await optimizeAPI.optimizeStops(optimizeData);
      setResult(response.data);

      // Build markers from ordered stops
      const newMarkers = [];
      if (response.data.ordered_stops) {
        response.data.ordered_stops.forEach((stop, idx) => {
          newMarkers.push({
            lat: stop.lat || 28.6139,
            lng: stop.lng || 77.209,
            label: `${idx + 1}. ${stop.name}`,
            type: idx === response.data.ordered_stops.length - 1 ? 'destination' : 'source',
          });
        });
      }
      setMarkers(newMarkers);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to optimize route');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-4xl font-bold text-slate-900 mb-2">Multi-Stop Route Optimizer</h1>
        <p className="text-slate-600 mb-8">Add multiple stops and we'll optimize the best route</p>

        <div className="grid lg:grid-cols-3 gap-8">
          {/* Form */}
          <div className="lg:col-span-2">
            <form onSubmit={handleOptimize} className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
              <h2 className="text-2xl font-semibold text-slate-900 mb-6">Route Details</h2>

              {error && (
                <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                  <p className="text-red-700 text-sm">{error}</p>
                </div>
              )}

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Starting Point
                  </label>
                  <input
                    type="text"
                    value={startLocation}
                    onChange={(e) => setStartLocation(e.target.value)}
                    placeholder="e.g., New Delhi"
                    className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Ending Point (Optional)
                  </label>
                  <input
                    type="text"
                    value={endLocation}
                    onChange={(e) => setEndLocation(e.target.value)}
                    placeholder="e.g., Airport"
                    className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <div>
                  <div className="flex justify-between items-center mb-3">
                    <label className="block text-sm font-medium text-slate-700">Stops</label>
                    <button
                      type="button"
                      onClick={addStop}
                      className="text-sm px-3 py-1 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition"
                    >
                      + Add Stop
                    </button>
                  </div>

                  <div className="space-y-2">
                    {stops.map((stop, idx) => (
                      <div key={idx} className="flex gap-2">
                        <input
                          type="text"
                          value={stop.name}
                          onChange={(e) => updateStop(idx, 'name', e.target.value)}
                          placeholder={`Stop ${idx + 1} name`}
                          className="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                        />
                        {stops.length > 1 && (
                          <button
                            type="button"
                            onClick={() => removeStop(idx)}
                            className="px-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                          >
                            ✕
                          </button>
                        )}
                      </div>
                    ))}
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold disabled:bg-slate-400"
                >
                  {loading ? 'Optimizing...' : 'Optimize Route'}
                </button>
              </div>
            </form>

            {/* Map */}
            {result && (
              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
                <h3 className="text-lg font-semibold text-slate-900 mb-4">Optimized Route</h3>
                <MapView
                  center={[28.6139, 77.209]}
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
                        className="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium"
                      >
                        {idx + 1}. {stop.name}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Results Sidebar */}
          <div>
            {result && (
              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 sticky top-24">
                <h3 className="text-lg font-semibold text-slate-900 mb-6">Optimization Results</h3>

                <div className="space-y-4">
                  <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p className="text-xs text-slate-500 uppercase tracking-wide">Total Distance</p>
                    <p className="text-2xl font-bold text-blue-600">
                      {formatKm(result.total_distance_km)}
                    </p>
                  </div>

                  <div className="p-4 bg-green-50 rounded-lg border border-green-200">
                    <p className="text-xs text-slate-500 uppercase tracking-wide">Stops</p>
                    <p className="text-2xl font-bold text-green-600">
                      {result.ordered_stops?.length || 0}
                    </p>
                  </div>
                </div>

                <button
                  onClick={() => alert('Save functionality can be added here')}
                  className="w-full mt-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium"
                >
                  💾 Save Route
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

