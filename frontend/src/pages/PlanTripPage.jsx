import React, { useState, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import MapView from '../components/MapView';
import CostCard from '../components/CostCard';
import RecommendationCard from '../components/RecommendationCard';
import PoiLegend from '../components/PoiLegend';
import { TripContext } from '../context/TripContext';
import routeAPI from '../api/route';
import costAPI from '../api/cost';
import poiAPI from '../api/poi';
import tripsAPI from '../api/trips';
import { formatKm, formatMinutes } from '../utils/formatters';

export default function PlanTripPage() {
  const { setCurrentTrip } = useContext(TripContext);
  const navigate = useNavigate();

  const [source, setSource] = useState('');
  const [destination, setDestination] = useState('');
  const [passengers, setPassengers] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [routeData, setRouteData] = useState(null);
  const [costs, setCosts] = useState(null);
  const [recommendation, setRecommendation] = useState(null);
  const [pois, setPois] = useState([]);
  const [selectedMode, setSelectedMode] = useState(null);
  const [savingTrip, setSavingTrip] = useState(false);
  const [markers, setMarkers] = useState([]);
  const [polylineData, setPolylineData] = useState([]);

  const handlePlanRoute = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    if (!source || !destination) {
      setError('Please enter both source and destination');
      setLoading(false);
      return;
    }

    try {
      // Plan route
      const routeRes = await routeAPI.planRoute({ source, destination });
      const route = routeRes.data;
      setRouteData(route);

      // Build markers and polyline
      const newMarkers = [];
      if (route.source_coords) {
        newMarkers.push({
          lat: route.source_coords.lat,
          lng: route.source_coords.lng,
          label: source,
          type: 'source',
        });
      }
      if (route.destination_coords) {
        newMarkers.push({
          lat: route.destination_coords.lat,
          lng: route.destination_coords.lng,
          label: destination,
          type: 'destination',
        });
      }
      setMarkers(newMarkers);

      // Parse polyline if available (for now, assume it's an array of points)
      if (route.polyline) {
        setPolylineData(route.polyline);
      }

      // Calculate costs
      const costRes = await costAPI.calculateCost({
        distance_km: route.distance_km,
        passengers,
      });
      setCosts(costRes.data);
      if (costRes.data.recommendation) {
        setRecommendation(costRes.data.recommendation);
        setSelectedMode(costRes.data.recommendation.mode);
      }

      // Get POIs
      try {
        const poisRes = await poiAPI.getPoisForRoute({
          polyline: route.polyline,
          radius_km: 3,
          categories: ['temple', 'fuel', 'charger', 'toll', 'restaurant', 'hospital'],
        });
        if (poisRes.data.pois && Array.isArray(poisRes.data.pois)) {
          setPois(poisRes.data.pois);
          const poiMarkers = poisRes.data.pois.map((poi) => ({
            lat: poi.lat,
            lng: poi.lng,
            label: poi.name,
            type: `poi-${poi.type}`,
          }));
          setMarkers([...newMarkers, ...poiMarkers]);
        }
      } catch (poiError) {
        console.warn('POI fetch failed:', poiError);
      }

      setCurrentTrip({
        source,
        destination,
        distance_km: route.distance_km,
        duration_min: route.duration_min,
        passengers,
        polyline: route.polyline,
      });
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to plan route');
    } finally {
      setLoading(false);
    }
  };

  const handleSaveTrip = async () => {
    if (!selectedMode || !routeData) {
      setError('Please select a transportation mode');
      return;
    }

    setSavingTrip(true);
    try {
      await tripsAPI.saveTrip({
        source,
        destination,
        distance_km: routeData.distance_km,
        duration_min: routeData.duration_min,
        selected_mode: selectedMode,
        costs,
        passengers,
      });
      navigate('/trips');
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to save trip');
    } finally {
      setSavingTrip(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-4xl font-bold text-slate-900 mb-2">Plan Your Trip</h1>
        <p className="text-slate-600 mb-8">
          Find the best route and transportation mode for your journey
        </p>

        <div className="grid lg:grid-cols-3 gap-8">
          {/* Form and Results */}
          <div className="lg:col-span-2">
            {/* Form */}
            <form onSubmit={handlePlanRoute} className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
              <h2 className="text-2xl font-semibold text-slate-900 mb-6">Trip Details</h2>

              {error && (
                <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                  <p className="text-red-700 text-sm">{error}</p>
                </div>
              )}

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Source
                  </label>
                  <input
                    type="text"
                    value={source}
                    onChange={(e) => setSource(e.target.value)}
                    placeholder="e.g., Delhi"
                    className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Destination
                  </label>
                  <input
                    type="text"
                    value={destination}
                    onChange={(e) => setDestination(e.target.value)}
                    placeholder="e.g., Mumbai"
                    className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">
                    Number of Passengers
                  </label>
                  <input
                    type="number"
                    value={passengers}
                    onChange={(e) => setPassengers(Math.max(1, parseInt(e.target.value) || 1))}
                    min="1"
                    className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                  />
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold disabled:bg-slate-400"
                >
                  {loading ? 'Planning Route...' : 'Plan Route'}
                </button>
              </div>
            </form>

            {/* Map */}
            {routeData && (
              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
                <h3 className="text-lg font-semibold text-slate-900 mb-4">Route Map</h3>
                <MapView
                  center={
                    routeData.source_coords
                      ? [routeData.source_coords.lat, routeData.source_coords.lng]
                      : [28.6139, 77.209]
                  }
                  zoom={10}
                  markers={markers}
                  polyline={polylineData}
                />
                <div className="mt-4 grid grid-cols-3 gap-4 text-center">
                  <div>
                    <p className="text-xs text-slate-500 uppercase tracking-wide">Distance</p>
                    <p className="text-xl font-bold text-slate-900">
                      {formatKm(routeData.distance_km)}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs text-slate-500 uppercase tracking-wide">Duration</p>
                    <p className="text-xl font-bold text-slate-900">
                      {formatMinutes(routeData.duration_min)}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs text-slate-500 uppercase tracking-wide">POIs</p>
                    <p className="text-xl font-bold text-slate-900">{pois.length}</p>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-8">
            {/* Recommendation Card */}
            {recommendation && routeData && (
              <RecommendationCard
                mode={recommendation.mode}
                reason={recommendation.reason}
                cost={costs[recommendation.mode]}
                distance={routeData.distance_km}
                duration={routeData.duration_min}
              />
            )}

            {/* Cost Cards */}
            {costs && (
              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h3 className="text-lg font-semibold text-slate-900 mb-4">Cost Comparison</h3>
                <div className="space-y-3">
                  {Object.entries(costs)
                    .filter(([key]) => ['car', 'ev', 'train', 'flight'].includes(key))
                    .map(([mode, cost]) => (
                      <CostCard
                        key={mode}
                        mode={mode}
                        cost={cost}
                        duration={routeData.duration_min}
                        selected={selectedMode === mode}
                        onSelect={() => setSelectedMode(mode)}
                      />
                    ))}
                </div>

                {selectedMode && (
                  <button
                    onClick={handleSaveTrip}
                    disabled={savingTrip}
                    className="w-full mt-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold disabled:bg-slate-400"
                  >
                    {savingTrip ? 'Saving...' : '💾 Save This Trip'}
                  </button>
                )}
              </div>
            )}

            {/* POI Legend */}
            {pois.length > 0 && <PoiLegend />}
          </div>
        </div>
      </div>
    </div>
  );
}

