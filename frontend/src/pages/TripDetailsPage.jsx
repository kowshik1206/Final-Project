import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import tripsAPI from '../api/trips';
import MapView from '../components/MapView';
import { formatCurrency, formatKm, formatMinutes } from '../utils/formatters';
import { ArrowLeft, Car, MapPin, Navigation } from 'lucide-react';

export default function TripDetailsPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [trip, setTrip] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    // Hydrate markers for the map
    const [markers, setMarkers] = useState([]);
    const [polyline, setPolyline] = useState([]);

    useEffect(() => {
        async function loadTrip() {
            try {
                setLoading(true);
                const res = await tripsAPI.getTrip(id);
                const data = res.data?.trip;
                if (!data) throw new Error("Trip not found");

                setTrip(data);

                // Parse Polyline
                let poly = [];
                try {
                    // Try geometry_geojson first (from trips table)
                    if (data.geometry_geojson) {
                        const geojson = typeof data.geometry_geojson === 'string'
                            ? JSON.parse(data.geometry_geojson)
                            : data.geometry_geojson;
                        if (Array.isArray(geojson) && geojson.length > 0) {
                            poly = geojson;
                        }
                    }
                    // Fallback to polyline field if available
                    if (poly.length === 0 && data.polyline) {
                        poly = typeof data.polyline === 'string'
                            ? JSON.parse(data.polyline)
                            : (Array.isArray(data.polyline) ? data.polyline : []);
                    }
                } catch (e) { console.error("Polyline parse error", e); }
                setPolyline(poly);

                // Parse Stops
                let stops = [];
                try {
                    stops = typeof data.stops_json === 'string'
                        ? JSON.parse(data.stops_json)
                        : (data.stops_json || []);
                } catch (e) { console.error("Stops parse error", e); }

                // Build Markers
                const m = [];
                // Source
                if (data.origin_lat && data.origin_lng) {
                    m.push({ id: 'src', lat: Number(data.origin_lat), lng: Number(data.origin_lng), label: data.source || 'Source', type: 'source' });
                }
                // Destination
                if (data.dest_lat && data.dest_lng) {
                    m.push({ id: 'dst', lat: Number(data.dest_lat), lng: Number(data.dest_lng), label: data.destination || 'Destination', type: 'destination' });
                }
                // Stops
                if (Array.isArray(stops)) {
                    stops.forEach((s, idx) => {
                        m.push({
                            id: s.id || `stop-${idx}`,
                            lat: Number(s.lat),
                            lng: Number(s.lng),
                            label: s.name || `Stop ${idx + 1}`,
                            type: 'poi-' + (s.category || 'misc')
                        });
                    });
                }
                setMarkers(m);

            } catch (err) {
                setError(err.message || "Failed to load trip");
            } finally {
                setLoading(false);
            }
        }
        loadTrip();
    }, [id]);

    if (loading) return <div className="p-8 text-center text-slate-500">Loading trip details...</div>;
    if (error) return <div className="p-8 text-center text-red-500">Error: {error}</div>;
    if (!trip) return null;

    // Costs parsing - handle both formats
    let costs = {};
    let vehicle = {};
    try {
        costs = typeof trip.costs_json === 'string' ? JSON.parse(trip.costs_json) : (trip.costs_json || {});
        // Ensure fuel_cost is populated from total or cost_amount
        if (!costs.fuel_cost && trip.cost_amount) {
            costs.fuel_cost = parseFloat(trip.cost_amount);
        }
        if (!costs.total && trip.cost_amount) {
            costs.total = parseFloat(trip.cost_amount);
        }
        
        vehicle = typeof trip.vehicle_json === 'string' ? JSON.parse(trip.vehicle_json) : (trip.vehicle_json || {});
    } catch (e) { 
        // Fallback to cost_amount if JSON parsing fails
        costs = {
            fuel_cost: parseFloat(trip.cost_amount || 0),
            total: parseFloat(trip.cost_amount || 0)
        };
    }

    return (
        <div className="flex flex-col bg-white dark:bg-slate-900 md:h-[calc(100vh-3.5rem)] md:overflow-hidden">
            {/* Header */}
            <div className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 px-4 md:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 z-10 shadow-sm flex-shrink-0">
                <div className="flex items-center gap-3">
                    <button onClick={() => navigate('/trips')} className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full flex-shrink-0">
                        <ArrowLeft className="text-slate-600 dark:text-slate-400" size={20} />
                    </button>
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 mb-0.5 flex-wrap">
                            <h1 className="text-lg md:text-xl font-bold text-slate-900 dark:text-slate-100 truncate">{trip.title || 'Trip Details'}</h1>
                            <span className="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded text-xs text-green-700 dark:text-green-300 font-semibold whitespace-nowrap">
                              ✓ Verified
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                          {new Date(trip.created_at).toLocaleDateString()}
                        </p>
                    </div>
                </div>
                <div className="flex gap-3 text-sm flex-wrap">
                    <div className="text-right">
                        <p className="text-slate-500 dark:text-slate-400 text-xs">Distance</p>
                        <p className="font-semibold text-slate-900 dark:text-slate-100">{formatKm(trip.distance_km)}</p>
                    </div>
                    <div className="text-right border-l px-3">
                        <p className="text-slate-500 dark:text-slate-400 text-xs">Est. Time</p>
                        <p className="font-semibold text-slate-900 dark:text-slate-100">{formatMinutes(trip.duration_min)}</p>
                    </div>
                    <div className="text-right border-l pl-3">
                        <p className="text-slate-500 dark:text-slate-400 text-xs">Cost</p>
                        <p className="font-semibold text-blue-600 dark:text-blue-400 font-mono">{formatCurrency(costs.total || 0)}</p>
                    </div>
                </div>
            </div>

            {/* Main Content: Sidebar + Map */}
            <div className="flex flex-1 flex-col md:flex-row overflow-hidden">

                {/* Sidebar Info */}
                <div className="riq-trip-sidebar w-full md:w-96 bg-white dark:bg-slate-900 border-b md:border-b-0 md:border-r border-slate-200 dark:border-slate-700 overflow-y-auto p-4 md:shrink-0 shadow-sm z-[5]">
                    {/* PHASE 5: Trip Status & Integrity */}
                    <div className="mb-6 p-4 bg-green-50 border-2 border-green-300 rounded-lg">
                      <div className="flex items-start gap-3">
                        <div className="text-2xl">✅</div>
                        <div className="flex-1">
                          <h3 className="font-semibold text-green-900 mb-1">Trip Status</h3>
                          <p className="text-sm text-green-800 mb-2">This trip has been saved and verified in our system.</p>
                          <div className="space-y-1 text-xs text-green-700">
                            <p>📍 <strong>Route:</strong> {trip.source} → {trip.destination}</p>
                            <p>🗓️ <strong>Saved:</strong> {new Date(trip.created_at).toLocaleDateString()}</p>
                            {trip.is_cached && (
                              <p>💾 <strong>Status:</strong> Based on cached route data</p>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>

                    <h3 className="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <Navigation size={18} className="text-blue-600" /> Itinerary
                    </h3>

                    <div className="space-y-6 relative ml-2">
                        {/* Timeline Line */}
                        <div className="absolute left-[7px] top-6 bottom-6 w-[2px] bg-slate-200 "></div>

                        {/* Source */}
                        <div className="relative flex gap-4 items-start">
                            <div className="w-4 h-4 rounded-full bg-blue-600 border-2 border-white ring-2 ring-blue-100 z-10 shrink-0 mt-1"></div>
                            <div>
                                <p className="text-xs text-slate-500 uppercase tracking-wide">Start</p>
                                <p className="font-medium text-slate-900">{trip.source}</p>
                            </div>
                        </div>

                        {/* Stops */}
                        {markers.filter(m => m.type.startsWith('poi-')).map((stop, i) => (
                            <div key={i} className="relative flex gap-4 items-start">
                                <div className="w-4 h-4 rounded-full bg-green-500 border-2 border-white ring-2 ring-green-100 z-10 shrink-0 mt-1 flex items-center justify-center">
                                    <div className="w-1 h-1 bg-white rounded-full"></div>
                                </div>
                                <div className="bg-slate-50 p-3 rounded-lg flex-1 border border-slate-100">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <span className="text-xs font-bold px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-600 mb-1 inline-block">
                                                {stop.type.replace('poi-', '').toUpperCase()}
                                            </span>
                                            <p className="font-medium text-slate-800 text-sm mt-1">{stop.label}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}

                        {/* Dest */}
                        <div className="relative flex gap-4 items-start">
                            <div className="w-4 h-4 rounded-full bg-red-600 border-2 border-white ring-2 ring-red-100 z-10 shrink-0 mt-1"></div>
                            <div>
                                <p className="text-xs text-slate-500 uppercase tracking-wide">Finish</p>
                                <p className="font-medium text-slate-900">{trip.destination}</p>
                            </div>
                        </div>
                    </div>

                    <hr className="my-6 border-slate-100" />

                    {/* Vehicle Info */}
                    <h3 className="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                        <Car size={18} className="text-blue-600" /> Vehicle & Costs
                    </h3>
                    <div className="bg-slate-50 rounded-lg p-4 border border-slate-100 text-sm">
                        <div className="flex justify-between mb-2">
                            <span className="text-slate-500">Vehicle</span>
                            <span className="font-medium">{vehicle?.name || vehicle?.model || '—'}</span>
                        </div>
                        <div className="flex justify-between mb-2">
                            <span className="text-slate-500">Fuel/Charge Type</span>
                            <span className="capitalize">{vehicle?.fuel_type || vehicle?.fuel || '—'}</span>
                        </div>
                        <div className="border-t border-slate-200 my-2 pt-2 flex justify-between">
                            <span className="text-slate-600 font-medium">Estimated Fuel Cost</span>
                            <span className="font-bold text-slate-900">{formatCurrency(costs.fuel_cost || 0)}</span>
                        </div>
                    </div>

                </div>

                {/* Map */}
                <div className="flex-1 bg-slate-100 dark:bg-slate-800 relative riq-trip-map">
                    <MapView
                        markers={markers}
                        polyline={polyline}
                        interactive={true}
                        showUserLocation={false}
                    />
                </div>
            </div>
        </div>
    );
}
