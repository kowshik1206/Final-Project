import React, { useEffect, useState, useContext, useRef } from 'react';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { Car, MapPin, Navigation, Sparkles, Fuel, Zap, ChevronLeft, Loader2 } from 'lucide-react';
import MapView from '../components/MapView';
import FuelComparison from '../components/FuelComparison';
import StationsList from '../components/StationsList';
import JourneyStageCard from '../components/JourneyStageCard';
import { TripContext } from '../context/TripContext';
import { computeEffectiveRangeKm, getEffectiveEfficiency } from '../utils/vehicleRange';
import { computeStops, getStopIntervalKm } from '../utils/stops';
import axiosClient from '../api/axiosClient';
import fuelAPI from '../api/fuel';
import poiAPI from '../api/poi';
import tripsAPI from '../api/trips';
import L from 'leaflet';
import { Marker as LMarker, Popup as LPopup } from 'react-leaflet';

/* ------------------------------------------------------------
  Helpers bundled here: geometry, range, scoring
  These are intentionally kept inline so the page works out-of-box.
------------------------------------------------------------ */

/* ---------- Geometry helper: point -> polyline distance (meters) ---------- */
const R = 6371000;
function toRad(deg) {
    return deg * Math.PI / 180;
}
function haversineDistanceMeters(p1, p2) {
    const lat1 = toRad(p1.lat);
    const lat2 = toRad(p2.lat);
    const dLat = toRad(p2.lat - p1.lat);
    const dLng = toRad(p2.lng - p1.lng);
    const aa = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(aa), Math.sqrt(1 - aa));
    return R * c;
}
function pointToSegmentDistanceMeters(p, a, b) {
    const latRef = toRad(p.lat);
    const x = (toRad(p.lng) - toRad(a.lng)) * Math.cos(latRef) * R;
    const y = (toRad(p.lat) - toRad(a.lat)) * R;
    const ax = 0, ay = 0;
    const bx = (toRad(b.lng) - toRad(a.lng)) * Math.cos(latRef) * R;
    const by = (toRad(b.lat) - toRad(a.lat)) * R;
    const px = x - ax, py = y - ay;
    const vx = bx - ax, vy = by - ay;
    const vLen2 = vx * vx + vy * vy;
    if (vLen2 === 0) return Math.sqrt(px * px + py * py);
    const t = Math.max(0, Math.min(1, (px * vx + py * vy) / vLen2));
    const projx = ax + t * vx;
    const projy = ay + t * vy;
    const dx = px - (projx - ax);
    const dy = py - (projy - ay);
    return Math.sqrt(dx * dx + dy * dy);
}
function pointToPolylineDistanceMeters(point, polyline = []) {
    if (!polyline || polyline.length === 0) return null;
    const norm = polyline.map(p => Array.isArray(p) ? { lat: Number(p[0]), lng: Number(p[1]) } : { lat: Number(p.lat), lng: Number(p.lng) });
    let min = Infinity;
    for (let i = 0; i < norm.length - 1; i++) {
        const d = pointToSegmentDistanceMeters(point, norm[i], norm[i + 1]);
        if (d < min) min = d;
    }
    for (let i = 0; i < norm.length; i++) {
        const d = haversineDistanceMeters(point, norm[i]);
        if (d < min) min = d;
    }
    return min === Infinity ? null : min;
}

/* ---------- Range helpers ---------- */
/* ---------- Calculate stop interval based on passengers ---------- */
// Removed: getStopIntervalKm is now in utils/stops.js

/* ---------- DETERMINISTIC STOP CALCULATION ---------- */
/**
 * Compute stops needed using a single deterministic rule.
 * stopsNeeded = max(fuelStops, comfortStops)
 * 
 * fuelStops = ceil(distance / effectiveRangeKm) - 1
 * comfortStops = ceil(distance / comfortIntervalKm) - 1
 * comfortIntervalKm = 120 if elders/children, else 200
 */
// Removed: computeStops is now in utils/stops.js (imported above)

/* ---------- Scoring ---------- */
function normRating(r) {
    if (r == null) return 0.5;
    return Math.max(0, Math.min(1, Number(r) / 5));
}
function distanceScoreMeters(d, max = 30000) {
    if (d == null) return 0;
    const v = 1 - Math.min(d, max) / max;
    return Math.max(0, Math.min(1, v));
}
function scorePoi(poi, { vehicle, userPrefs = {}, maxDistance = 30000 } = {}) {
    const vFuel = (vehicle?.fuel_type || '').toString().toLowerCase();
    let vehicleMatch = 0;
    const pFuel = (poi.subtype || poi.fuel_type || '').toString().toLowerCase();
    if (pFuel && vFuel && pFuel === vFuel) vehicleMatch = 1;
    if (!pFuel) {
        if (poi.category === 'charger' && vFuel === 'ev') vehicleMatch = 1;
        if (poi.category === 'fuel' && ['petrol', 'diesel', 'cng'].includes(vFuel)) vehicleMatch = 1;
        if (poi.category === 'cng' && vFuel === 'cng') vehicleMatch = 1;
    }
    const dScore = distanceScoreMeters(poi.distance_to_route_m, maxDistance);
    const qScore = normRating(poi.rating);
    let categoryBoost = 0;
    if (poi.category === 'hospital') categoryBoost = userPrefs.eldersCount > 0 ? 0.9 : 0.6;
    if (poi.category === 'temple') {
        categoryBoost = userPrefs.templePriority === 'high' ? 0.8 : (userPrefs.templePriority === 'low' ? 0.1 : 0.5);
    }
    let userBoost = 0;
    if (userPrefs.eldersCount > 0 && poi.category === 'hospital') userBoost = 0.1;
    if (userPrefs.comfortMode === 'comfort' && poi.category === 'restaurant') userBoost = 0.05;
    const w = { wVehicle: 0.4, wDistance: 0.3, wQuality: 0.15, wCategory: 0.1, wUser: 0.05 };
    const score = w.wVehicle * vehicleMatch + w.wDistance * dScore + w.wQuality * qScore + w.wCategory * categoryBoost + w.wUser * userBoost;
    return Math.max(0, Math.min(1, score));
}

/* ---------- Component ---------- */
export default function CarConfigurator() {
    const { currentTrip, setCurrentTrip, addStop } = useContext(TripContext) || {};
    const location = useLocation();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();

    // Journey data from URL params or location state
    const source = searchParams.get('source') || location.state?.source;
    const destination = searchParams.get('destination') || location.state?.destination;
    const costParam = searchParams.get('cost') || location.state?.cost;
    const durationParam = searchParams.get('duration') || location.state?.duration;

    // prefer navigation state, then TripContext
    const trip = location?.state?.routeData ?? currentTrip ?? null;

    // Journey Data State
    const [journeyData, setJourneyData] = useState(null);
    const [journeyLoading, setJourneyLoading] = useState(false);
    const [journeyError, setJourneyError] = useState(null);
    const [saving, setSaving] = useState(false);

    // UI / data state
    const [vehicles, setVehicles] = useState([]);
    const [loadingVehicles, setLoadingVehicles] = useState(true);
    const [selectedVehicle, setSelectedVehicle] = useState(null);

    const [effectiveRangeKm, setEffectiveRangeKm] = useState(null);
    const [stopPlan, setStopPlan] = useState({ count: 0, positions: [], fuelStops: [], comfortStops: [] });

    // Passenger info
    const [eldersCount, setEldersCount] = useState(trip?.elders_count ?? 0);
    const [childrenCount, setChildrenCount] = useState(trip?.children_count ?? 0);
    const [stopInterval, setStopInterval] = useState(200);

    // UI State for Filters
    const [activeTab, setActiveTab] = useState('auto'); // 'auto' (Vehicle Fuel), 'food', 'hospital', 'temple'
    const [stationCategory, setStationCategory] = useState('fuel'); // derived from vehicle (fuel/charger/cng)

    const [stations, setStations] = useState([]); // scored stations
    const [topPois, setTopPois] = useState([]);
    const [stationsLoading, setStationsLoading] = useState(false);

    // Custom Mileage Override State (Cost calculation only)
    const [useCustomMileage, setUseCustomMileage] = useState(false);
    const [customMileage, setCustomMileage] = useState(null);

    const [searchRadiusKm, setSearchRadiusKm] = useState(5); // adjustable by user
    const [pinnedStops, setPinnedStops] = useState(trip?.stops ?? []);
    const [poiPrioritySettings, setPoiPrioritySettings] = useState({
        templePriority: 'normal',
        eldersCount: trip?.elders_count ?? 0,
        comfortMode: trip?.preferences?.comfort_mode ?? 'comfort'
    });

    const mapRef = useRef(null);

    /* ---------- Fetch Car Route (Journey Data) ---------- */
    const fetchCarRoute = async () => {
        if (!source || !destination) {
            console.warn('[fetchCarRoute] missing source or destination');
            return;
        }

        try {
            setJourneyLoading(true);
            setJourneyError(null);

            const response = await axiosClient.post('/plan-route-v2', {
                source,
                destination,
                mode: 'car',
            });

            const data = response.data;

            if (!data.ok) {
                throw new Error(data.message || 'Failed to plan route');
            }

            setJourneyData(data);
        } catch (err) {
            console.error('[fetchCarRoute] error:', err.message);
            setJourneyError(err.message || 'Failed to fetch route');
        } finally {
            setJourneyLoading(false);
        }
    };

    /* ---------- Handle Save Trip ---------- */
    const handleSaveTrip = async () => {
        if (!journeyData || !source || !destination) {
            alert('Journey data not loaded. Please wait.');
            return;
        }

        try {
            setSaving(true);

            // Get cost from URL params or use 0
            const tripCost = costParam ? parseFloat(costParam) : 0;

            const tripData = {
                source,
                destination,
                selected_mode: 'car',
                distance_km: journeyData.total_distance_km || 0,
                duration_min: journeyData.total_duration_min || 0,
                cost: tripCost,
                source_coords: journeyData.source_coords || { lat: 0, lng: 0 },
                destination_coords: journeyData.destination_coords || { lat: 0, lng: 0 },
                polyline: journeyData.polyline || journeyData.segments?.[0]?.polyline || [],
                stops_json: JSON.stringify(pinnedStops || []),
                vehicle_json: JSON.stringify(selectedVehicle || {}),
                segments: journeyData.segments || [],
            };

            const response = await tripsAPI.saveTrip(tripData);
            alert('✅ Trip saved successfully!');
            navigate('/trips');
        } catch (err) {
            console.error('Save error:', err);
            alert('❌ Failed to save trip: ' + (err.response?.data?.message || err.message));
        } finally {
            setSaving(false);
        }
    };

    /* ---------- fetchStationsForVehicle (single unified implementation, debug-friendly) ---------- */
    async function fetchStationsForVehicle(vehicle, effectiveRangeKmLocal, radiusKm = 5) {
        console.log('[Stations] fetchStationsForVehicle start', { vehicle, effectiveRangeKmLocal, radiusKm, tripSummary: { distance_km: trip?.distance_km, polylineLen: trip?.polyline?.length } });
        if (!trip?.polyline || !Array.isArray(trip.polyline) || trip.polyline.length === 0) {
            console.warn('[Stations] no trip.polyline available — cannot search POIs');
            setStations([]);
            setTopPois([]);
            setStationsLoading(false);
            return;
        }
        if (!vehicle) {
            console.warn('[Stations] no selectedVehicle — will fetch generic POIs (no vehicle filter)');
        }

        setStationsLoading(true);

        // Primary attempt: polite request with filters (POST preferred)
        // BUILD CATEGORIES BASED ON ACTIVE TAB
        let categories = [];
        if (activeTab === 'auto') {
            categories = [stationCategory]; // Just fuel/ev
        } else if (activeTab === 'food') {
            categories = ['restaurant'];
        } else if (activeTab === 'hospital') {
            categories = ['hospital'];
        } else if (activeTab === 'temple') {
            categories = ['temple'];
        } else {
            // Fallback or 'all'
            categories = [stationCategory, 'restaurant', 'hospital', 'temple'];
        }

        // FILTERS: Only apply fuel type filter if we are looking for fuel/chargers
        const filters = {};
        if (activeTab === 'auto') {
            filters.fuel_type = vehicle?.fuel_type;
        }

        try {
            console.log('[Stations] calling poiAPI.getPoisForRoute with categories, filters', { categories, filters, radiusKm });
            const res = await poiAPI.getPoisForRoute({
                polyline: trip.polyline,
                vehicle: vehicle || undefined,
                radius_km: Math.max(3, radiusKm),
                categories,
                filters
            });

            console.log('[Stations] raw response from poiAPI.getPoisForRoute', res);

            const listRaw = res.pois ?? res.data ?? res.raw ?? res; // defensive
            if (!Array.isArray(listRaw) || listRaw.length === 0) {
                console.warn('[Stations] backend returned no POIs for this query. Doing broad fallback fetch.');
                // Fallback 1: expand radius and request all categories
                const fallbackRes = await poiAPI.getPoisForRoute({
                    polyline: trip.polyline,
                    vehicle: vehicle || undefined,
                    radius_km: Math.max(20, Math.round((effectiveRangeKmLocal || 50) / 5)),
                    categories: ['restaurant', 'hospital', 'temple', stationCategory], // all categories (never empty)
                    filters: {} // remove fuel_type filter
                });
                console.log('[Stations] fallback response', fallbackRes);
                const fallbackList = fallbackRes.pois ?? fallbackRes.data ?? fallbackRes.raw ?? [];
                if (!Array.isArray(fallbackList) || fallbackList.length === 0) {
                    console.warn('[Stations] fallback returned no POIs either');
                    setStations([]);
                    setTopPois([]);
                    setStationsLoading(false);
                    return;
                }
                processRawList(fallbackList, vehicle);
                return;
            }

            processRawList(listRaw, vehicle);
        } catch (err) {
            console.error('[Stations] fetch failed', err);
            // Try a GET fallback if POST-based wrapper failed
            try {
                console.log('[Stations] trying direct GET fallback...');
                const qsCats = categories.length ? `&categories=${categories.join(',')}` : '';
                const qs = `?radius_km=${Math.max(3, radiusKm)}${qsCats}${filters.fuel_type ? `&fuel_type=${filters.fuel_type}` : ''}`;
                const raw = await (await fetch(`/api/pois-for-route${qs}`)).json();
                console.log('[Stations] raw GET fallback response', raw);
                const fallbackList = raw.pois ?? raw.data ?? raw;
                if (Array.isArray(fallbackList) && fallbackList.length) {
                    processRawList(fallbackList, vehicle);
                    return;
                }
            } catch (err2) {
                console.error('[Stations] GET fallback also failed', err2);
            }
            setStations([]);
            setTopPois([]);
        } finally {
            setStationsLoading(false);
        }

        // helper to normalize + score + set states
        function processRawList(listRawParam, vehicleParam) {
            try {
                const normalized = (listRawParam || []).map(p => {
                    const lat = Number(p.lat ?? p.latitude ?? p.latitude_deg ?? p.lat_deg ?? 0);
                    const lng = Number(p.lng ?? p.longitude ?? p.lon ?? p.lng ?? 0);
                    return {
                        id: p.id ?? p.poi_id ?? `${p.name ?? 'poi'}-${Math.random().toString(36).slice(2, 6)}`,
                        name: p.name ?? p.title ?? 'POI',
                        brand: p.brand || null,
                        phone: p.phone || null,
                        tags: typeof p.tags === 'string' ? JSON.parse(p.tags || '{}') : (p.tags || {}),
                        confidence: Number(p.confidence ?? 0.5),
                        category: p.category ?? p.type ?? 'poi',
                        subtype: p.subtype ?? p.fuel_type ?? null,
                        lat,
                        lng,
                        rating: typeof p.rating === 'number' ? p.rating : (p.rating ? Number(p.rating) : null),
                        distance_to_route_m: p.distance_to_route_m ?? p.distance_m ?? null,
                        raw: p
                    };
                });

                // compute distance if missing
                normalized.forEach(n => {
                    if (n.distance_to_route_m == null) {
                        n.distance_to_route_m = pointToPolylineDistanceMeters({ lat: n.lat, lng: n.lng }, trip.polyline);
                    }
                });

                // score
                const userPrefs = {
                    eldersCount: poiPrioritySettings.eldersCount,
                    templePriority: poiPrioritySettings.templePriority,
                    comfortMode: poiPrioritySettings.comfortMode
                };
                const scored = normalized.map(p => ({ ...p, score: scorePoi(p, { vehicle: vehicleParam, userPrefs, maxDistance: 30000 }) }))
                    .sort((a, b) => (b.score || 0) - (a.score || 0));

                console.log('[Stations] normalized + scored count:', scored.length, 'top sample:', scored.slice(0, 5));
                setStations(scored);
                setTopPois(scored.slice(0, Math.max(6, stopPlan.count * 2)));
            } catch (err) {
                console.error('[Stations] processRawList failed', err);
                setStations([]);
                setTopPois([]);
            } finally {
                setStationsLoading(false);
            }
        }
    } // end fetchStationsForVehicle

    useEffect(() => {
        let mounted = true;
        async function loadVehicles() {
            try {
                const res = await fuelAPI.getProfiles();
                const list = res?.vehicles ?? res?.data ?? res?.raw ?? res;
                if (Array.isArray(list)) {
                    if (mounted) setVehicles(list);
                } else if (Array.isArray(res.data?.data)) {
                    if (mounted) setVehicles(res.data.data);
                } else {
                    if (mounted) setVehicles(Array.isArray(res.data) ? res.data : []);
                }
            } catch (err) {
                console.error('Vehicle load failed', err);
                if (mounted) setVehicles([]);
            } finally {
                if (mounted) setLoadingVehicles(false);
            }
        }
        loadVehicles();
        return () => { mounted = false; };
    }, []);

    // Fetch journey data (route, distance, duration)
    useEffect(() => {
        if (source && destination) {
            fetchCarRoute();
        }
    }, [source, destination]);

    // When vehicle or trip changes compute range + stops + station category
    useEffect(() => {
        console.log('[DEBUG] Stops calculation useEffect triggered', {
            selectedVehicle: !!selectedVehicle,
            selectedVehicleId: selectedVehicle?.id,
            selectedVehicleFuel: selectedVehicle?.fuel_type,
            journeyDataDistance: journeyData?.total_distance_km,
            tripDistance: trip?.distance_km,
            journeyLoading,
            journeyDataOk: !!journeyData?.total_distance_km
        });

        if (!selectedVehicle) {
            console.log('[DEBUG] No vehicle selected, resetting stops');
            setEffectiveRangeKm(null);
            setStopPlan({ count: 0 });
            return;
        }

        // Use journeyData distance if available, fallback to trip distance
        const distanceKm = journeyData?.total_distance_km ?? trip?.distance_km;
        console.log('[DEBUG] Distance calculation: distanceKm=', distanceKm, 'from journeyData=', journeyData?.total_distance_km, 'or trip=', trip?.distance_km);

        if (!distanceKm || distanceKm <= 0) {
            console.log('[DEBUG] Distance is invalid/missing, resetting stops');
            setEffectiveRangeKm(null);
            setStopPlan({ count: 0 });
            return;
        }

        // Calculate effective range based on vehicle
        const r = computeEffectiveRangeKm(selectedVehicle, 0.8);
        console.log('[DEBUG] Effective range calculated:', r, 'from vehicle:', {
            fuel_type: selectedVehicle.fuel_type,
            mileage: selectedVehicle.mileage,
            tank_size: selectedVehicle.tank_size,
            ev_range: selectedVehicle.ev_range
        });

        if (!r || r <= 0) {
            console.log('[DEBUG] Range invalid, resetting stops');
            setEffectiveRangeKm(null);
            setStopPlan({ count: 0 });
            return;
        }

        setEffectiveRangeKm(r);
        // Calculate stop interval based on passengers
        const interval = getStopIntervalKm(eldersCount, childrenCount);
        setStopInterval(interval);
        // Compute stops using deterministic rule: max(fuelStops, comfortStops)
        const plan = computeStops(Number(distanceKm), r, eldersCount, childrenCount);
        console.log('[DEBUG] Stop plan computed:', plan);
        setStopPlan(plan);

        const fuel = (selectedVehicle.fuel_type || '').toLowerCase();
        const newCategory = fuel === 'ev' ? 'charger' : (fuel === 'cng' ? 'cng' : 'fuel');
        console.log('[DEBUG] Station category updated to:', newCategory);
        setStationCategory(newCategory);

        if (typeof setCurrentTrip === 'function') {
            setCurrentTrip(prev => ({ ...(prev || {}), selectedVehicle }));
        }
        // auto fetch stations
        console.log('[DEBUG] Fetching stations with range:', r);
        fetchStationsForVehicle(selectedVehicle, r, searchRadiusKm);

        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedVehicle, journeyData, trip?.distance_km, eldersCount, childrenCount]); // REMOVED activeTab - tabs don't change physics

    useEffect(() => {
        // if priority settings change, re-score existing stations
        if (!stations || stations.length === 0) return;
        const userPrefs = {
            eldersCount: poiPrioritySettings.eldersCount,
            templePriority: poiPrioritySettings.templePriority,
            comfortMode: poiPrioritySettings.comfortMode
        };
        const res = stations.map(s => ({ ...s, score: scorePoi(s, { vehicle: selectedVehicle, userPrefs, maxDistance: 30000 }) }))
            .sort((a, b) => (b.score || 0) - (a.score || 0));
        setStations(res);
        setTopPois(res.slice(0, Math.max(6, stopPlan.count * 2)));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [poiPrioritySettings]);

    // Re-fetch stations when tab (category filter) changes
    useEffect(() => {
        if (!selectedVehicle || !journeyData || !effectiveRangeKm) {
            console.log('[Tab Change] Missing context for refetch:', { selectedVehicle: !!selectedVehicle, journeyData: !!journeyData, effectiveRangeKm });
            return;
        }
        
        console.log('[Tab Change] Category filter changed to:', activeTab, '- fetching stations...');
        setStationsLoading(true);
        fetchStationsForVehicle(selectedVehicle, effectiveRangeKm, searchRadiusKm);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeTab, searchRadiusKm]);

    // Reset custom mileage when vehicle changes (cost calc only, not persisted)
    useEffect(() => {
        setUseCustomMileage(false);
        setCustomMileage(null);
    }, [selectedVehicle]);

    // Build suggested stops per anchor (accurate anchor detection by cumulative distance)
    function buildStopSuggestions() {
        const suggestions = [];
        if (!stations.length || !trip?.polyline || !effectiveRangeKm) return suggestions;
        const pts = trip.polyline.map(p => (Array.isArray(p) ? { lat: p[0], lng: p[1] } : p));
        const segLens = [];
        for (let i = 1; i < pts.length; i++) {
            const d = haversineDistanceMeters(pts[i - 1], pts[i]);
            segLens.push(d);
        }
        const intervalMeters = (effectiveRangeKm || 0) * 1000;
        if (intervalMeters <= 0) return suggestions;
        let acc = 0;
        let nextThreshold = intervalMeters;
        for (let i = 0; i < segLens.length; i++) {
            acc += segLens[i];
            while (acc >= nextThreshold) {
                const anchorPt = pts[i + 1];
                const candidates = stations.filter(s => {
                    const d = haversineDistanceMeters({ lat: s.lat, lng: s.lng }, anchorPt);
                    return d <= 15000;
                }).sort((a, b) => (b.score || 0) - (a.score || 0));
                if (candidates.length) {
                    suggestions.push({ anchor: anchorPt, station: candidates[0], kmFromStart: Math.round(nextThreshold / 1000) });
                }
                nextThreshold += intervalMeters;
            }
        }
        return suggestions;
    }

    // Filter stations by active tab category
    const filteredStations = stations.filter(s => {
        if (activeTab === 'auto') {
            return s.category === stationCategory; // Fuel/Charger/CNG
        } else if (activeTab === 'food') {
            return s.category === 'restaurant';
        } else if (activeTab === 'hospital') {
            return s.category === 'hospital';
        }
        return true;
    });

    // Focus map on POI (uses MapView onMapReady -> mapRef)
    function focusOnPoi(lat, lng, zoom = 14) {
        const m = mapRef.current || window.__routeiq_map;
        if (m && typeof m.setView === 'function') {
            m.setView([lat, lng], zoom, { animate: true });
        }
    }

    // Pin stop to TripContext and local pinnedStops
    function handleAddStop(poi) {
        const stop = {
            id: poi.id,
            name: poi.name,
            lat: poi.lat,
            lng: poi.lng,
            category: poi.category
        };
        setPinnedStops(prev => {
            const next = [...(prev || []), stop];
            if (typeof setCurrentTrip === 'function') {
                setCurrentTrip(prevTrip => ({ ...(prevTrip || {}), stops: next }));
            }
            if (typeof addStop === 'function') {
                try { addStop(stop); } catch (e) { }
            }
            return next;
        });
    }

    // Auto-assign stops (pin top suggestion per anchor)
    function autoAssignStops() {
        const suggestions = buildStopSuggestions();
        suggestions.forEach(s => {
            if (s.station) handleAddStop(s.station);
        });
    }

    // UI: Direct Save Trip (One-Click)
    async function handleDirectSave() {
        if (!selectedVehicle) {
            alert("Please select a vehicle first.");
            return;
        }
        if (!trip) {
            alert("Route data missing. Please restart planning.");
            return;
        }

        // Rough cost estimate for saving (can be refined via API if needed)
        // Formula: Distance / Efficiency * Price
        let estimatedCost = 0;
        const price = (selectedVehicle.fuel_type === 'cng' ? 75 : (selectedVehicle.fuel_type === 'diesel' ? 90 : 100)); // Approx defaults
        if (selectedVehicle.fuel_type === 'ev') {
            const range = selectedVehicle.ev_range;
            if (!range) {
                alert("EV range not available for this vehicle.");
                return;
            }
            estimatedCost = (trip.distance_km / range) * 100; // ~100 rs per charge unit
        } else {
            const efficiency = getEffectiveEfficiency(selectedVehicle);
            if (!efficiency) {
                alert("Fuel efficiency not available for this vehicle.");
                return;
            }
            estimatedCost = (trip.distance_km / efficiency) * price;
        }

        try {
            await tripsAPI.saveTrip({
                source: trip.source_address || trip.source || 'Unknown Source',
                destination: trip.destination_address || trip.destination || 'Unknown Dest',
                distance_km: trip.distance_km,
                duration_min: trip.duration_min,
                selected_mode: 'car',
                costs: {
                    total: Math.round(estimatedCost),
                    fuel_cost: Math.round(estimatedCost),
                    toll_cost: 0 // Optional: fetch toll if available
                },
                passengers: 1, // Default or pass from context
                polyline: trip.polyline,
                selected_vehicle: selectedVehicle,
                stops: pinnedStops.map(p => ({
                    id: p.id,
                    name: p.name,
                    lat: p.lat,
                    lng: p.lng,
                    category: p.category
                })),
                stops_meta: {
                    count: stopPlan.count
                }
            });
            // Navigate to My Trips after saving
            navigate('/trips');
        } catch (err) {
            console.error("Save failed", err);
            alert("Failed to save trip: " + (err.response?.data?.message || err.message));
        }
    }

    // Map ready hook
    function handleMapReady(mapInstance) {
        mapRef.current = mapInstance;
        window.__routeiq_map = mapInstance;
    }

    // Early guard: if no trip, show friendly message
    if (!trip || (typeof trip.distance_km === 'undefined' && typeof trip.polyline === 'undefined')) {
        return (
            <div className="min-h-screen p-10 bg-slate-50 dark:bg-slate-900 transition-colors">
                <div className="max-w-3xl mx-auto bg-white dark:bg-slate-800 p-8 rounded shadow border border-gray-200 dark:border-slate-700 transition-colors">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-slate-100 mb-2">Trip data missing</h2>
                    <p className="text-gray-600 dark:text-slate-400">Please plan a trip first. <button onClick={() => navigate('/plan-trip')} className="text-blue-600 dark:text-blue-400 underline ml-2 hover:text-blue-700 dark:hover:text-blue-300">Go to Plan Trip</button></p>
                </div>
            </div>
        );
    }

    const distance_km = Number(journeyData?.total_distance_km ?? trip?.distance_km ?? 0);
    const duration_min = Number(journeyData?.total_duration_min ?? trip?.duration_min ?? 0);
    const suggestions = buildStopSuggestions();

    return (
        <div className="min-h-screen riq-page-shell transition-colors pt-16">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Enhanced Journey Header */}
                {(source && destination) && (
                    <div className="riq-journey-header mb-6">
                        <div className="riq-journey-icon-lg">
                            <Car size={32} />
                        </div>
                        <div className="flex-1">
                            <button
                                onClick={() => navigate('/plan')}
                                className="flex items-center gap-1 text-sm font-medium hover:gap-2 transition-all mb-2"
                                style={{ color: '#6366f1', background: 'none', border: 'none', cursor: 'pointer', padding: 0 }}
                            >
                                <ChevronLeft size={16} /> Back to Planning
                            </button>
                            <h1 style={{ fontSize: 'clamp(1.75rem, 3.5vw, 2.25rem)', fontWeight: 900, color: 'var(--riq-text)', margin: 0, lineHeight: 1.1 }}>
                                Car <span className="riq-gradient-text">Journey</span>
                            </h1>
                            <div className="flex items-center gap-2 mt-2">
                                <MapPin size={16} style={{ color: 'var(--riq-text-muted)' }} />
                                <p style={{ fontSize: '1rem', color: 'var(--riq-text-muted)', margin: 0, fontWeight: 600 }}>
                                    {source} → {destination}
                                </p>
                            </div>
                            <div className="flex gap-2 mt-3">
                                <span className="riq-badge primary"><Sparkles size={12} /> Route Optimized</span>
                                <span className="riq-badge info"><Fuel size={12} /> Smart Stops</span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Journey Loading State */}
                {journeyLoading && (
                    <div className="flex flex-col items-center justify-center min-h-[400px] gap-4">
                        <div className="riq-journey-icon-lg">
                            <Car size={32} />
                        </div>
                        <div className="flex items-center gap-2">
                            <Loader2 className="animate-spin" size={20} style={{ color: '#6366f1' }} />
                            <p className="text-lg font-semibold" style={{ color: 'var(--riq-text)' }}>Planning your car journey...</p>
                        </div>
                    </div>
                )}

                {/* Journey Error State */}
                {journeyError && !journeyLoading && (
                    <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 text-center mb-6 transition-colors">
                        <div className="text-red-600 dark:text-red-400 text-4xl mb-2">⚠️</div>
                        <h2 className="text-lg font-semibold text-red-900 dark:text-red-200 mb-2 transition-colors">Error Planning Route</h2>
                        <p className="text-red-700 dark:text-red-300 transition-colors">{journeyError}</p>
                        <button
                            onClick={() => navigate('/plan')}
                            className="mt-4 px-4 py-2 bg-green-600 dark:bg-green-500 text-white rounded hover:bg-green-700 dark:hover:bg-green-600 transition-colors"
                        >
                            Back to Planning
                        </button>
                    </div>
                )}

                {/* Main Content */}
                {journeyData && (
                    <>
                        {/* Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg p-4 transition-colors">
                                <div className="text-sm text-gray-600 dark:text-slate-400 mb-1 transition-colors">Total Distance</div>
                                <div className="text-2xl font-bold text-gray-900 dark:text-slate-100 transition-colors">
                                    {journeyData.total_distance_km.toFixed(2)} km
                                </div>
                            </div>
                            <div className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg p-4 transition-colors">
                                <div className="text-sm text-gray-600 dark:text-slate-400 mb-1 transition-colors">Estimated Duration</div>
                                <div className="text-2xl font-bold text-gray-900 dark:text-slate-100 transition-colors">
                                    {Math.floor(journeyData.total_duration_min / 60)}h {journeyData.total_duration_min % 60}m
                                </div>
                            </div>
                            <div className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg p-4 transition-colors">
                                <div className="text-sm text-gray-600 dark:text-slate-400 mb-1 transition-colors">Stops Needed</div>
                                <div className="text-2xl font-bold text-green-600 dark:text-green-400 transition-colors">
                                    {selectedVehicle ? stopPlan.count : '—'}
                                </div>
                                <div className="text-xs text-gray-500 dark:text-slate-500 mt-1 transition-colors">
                                    {selectedVehicle ? (
                                        <>
                                            <div className="font-semibold text-gray-700 dark:text-slate-300 mb-1 transition-colors">Smart Planning:</div>
                                            <div>🔋 Fuel/Charge & ☕ Comfort: {stopPlan.count} stops needed</div>
                                            {eldersCount > 0 || childrenCount > 0 ? (
                                                <div className="mt-1 text-purple-600 dark:text-purple-400">👥 {eldersCount + childrenCount} passengers (every {stopInterval} km)</div>
                                            ) : (
                                                <div className="mt-1 text-gray-600 dark:text-slate-400">Solo trip (every {stopInterval} km)</div>
                                            )}
                                        </>
                                    ) : 'Select vehicle'}
                                </div>
                            </div>
                        </div>

                        {/* Route Details & Map */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                            {/* Left: Journey Stages */}
                            <div className="space-y-3">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-slate-100 transition-colors">Route Details</h2>
                                {journeyData.segments && journeyData.segments.map((segment, index) => (
                                    <JourneyStageCard
                                        key={index}
                                        icon="🚗"
                                        title="Road Journey"
                                        from={source}
                                        to={destination}
                                        distance={segment.distance_km}
                                        duration={segment.duration_min}
                                        type="road"
                                    />
                                ))}
                            </div>

                            {/* Right: Map */}
                            <div>
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-slate-100 mb-4 transition-colors">Route Map</h2>
                                <div className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg overflow-hidden transition-colors">
                                    <MapView
                                        center={journeyData.source_coords}
                                        zoom={8}
                                        routeSegments={journeyData.segments || []}
                                        markers={[
                                            {
                                                ...journeyData.source_coords,
                                                type: 'source',
                                                content: source,
                                            },
                                            {
                                                ...journeyData.destination_coords,
                                                type: 'destination',
                                                content: destination,
                                            },
                                        ]}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Vehicle Configuration Section */}
                        <div className="bg-white dark:bg-slate-800 rounded-lg shadow border border-gray-200 dark:border-slate-700 p-6 mb-6 transition-colors">
                            <h2 className="text-2xl font-semibold text-gray-900 dark:text-slate-100 mb-4 transition-colors">Select Vehicle & Plan Stops</h2>

                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                {/* Left/Main - Vehicle Selection & Cost Comparison */}
                                <div className="lg:col-span-2 space-y-6">
                                    {/* Merged: Vehicle Selection + Fuel Comparison */}
                                    <div className="bg-white dark:bg-slate-800 p-6 rounded-lg shadow border border-gray-200 dark:border-slate-700 transition-colors">
                                        <FuelComparison
                                            vehicles={vehicles}
                                            distance_km={distance_km}
                                            fuelPrices={{ petrol: 108, diesel: 96, cng: 52, ev: 8 }}
                                            selectedVehicleId={selectedVehicle?.id}
                                            onSelect={(v) => setSelectedVehicle(v)}
                                            customEfficiency={useCustomMileage && customMileage ? customMileage : null}
                                        />

                                        {/* Custom Mileage Override (Cost Simulation Only) */}
                                        {selectedVehicle && selectedVehicle.fuel_type !== 'ev' && (
                                            <div className="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                                                <label className="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                                                    <input
                                                        type="checkbox"
                                                        checked={useCustomMileage}
                                                        onChange={(e) => {
                                                            setUseCustomMileage(e.target.checked);
                                                            if (!e.target.checked) setCustomMileage(null);
                                                        }}
                                                        className="w-5 h-5 text-blue-600 dark:text-blue-400 rounded focus:ring-2 focus:ring-blue-500"
                                                    />
                                                    <span className="font-semibold text-gray-800 dark:text-slate-200">Simulate Cost with Custom Mileage</span>
                                                </label>

                                                {useCustomMileage && (
                                                    <div className="mt-4 p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700 transition-colors">
                                                        <label className="block mb-3">
                                                            <span className="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2 block transition-colors">
                                                                Custom Mileage (km/{selectedVehicle.fuel_type === 'cng' ? 'kg' : 'L'})
                                                            </span>
                                                            <input
                                                                type="number"
                                                                min="0.1"
                                                                step="0.1"
                                                                value={customMileage || ''}
                                                                onChange={(e) => {
                                                                    const val = e.target.value ? parseFloat(e.target.value) : null;
                                                                    if (val === null || val > 0) setCustomMileage(val);
                                                                }}
                                                                placeholder="Enter value (e.g., 15, 20.5)"
                                                                className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                                                            />
                                                            <p className="text-xs text-gray-500 dark:text-slate-500 mt-2 transition-colors">
                                                                Actual: {selectedVehicle.fuel_type === 'ev' ? 'N/A (EV)' : (getEffectiveEfficiency(selectedVehicle) ? `${getEffectiveEfficiency(selectedVehicle)} km/${selectedVehicle.fuel_type === 'cng' ? 'kg' : 'L'}` : 'N/A')}
                                                            </p>
                                                        </label>
                                                        <p className="text-xs text-blue-600 dark:text-blue-400 mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800 transition-colors">
                                                            ℹ️ This affects cost calculation only. Stops, range, and POI logic remain unchanged. Resets when you change vehicles.
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>


                                    {/* Passenger Info - REDESIGNED */}
                                    <div className="bg-gradient-to-br from-purple-50 dark:from-purple-900/20 to-indigo-50 dark:to-indigo-900/20 p-6 rounded-xl shadow-md border-2 border-purple-200 dark:border-purple-700 transition-colors">
                                        <div className="flex items-center gap-2 mb-4">
                                            <span className="text-2xl">👥</span>
                                            <h2 className="text-xl font-bold text-purple-900 dark:text-purple-100 transition-colors">Passenger Information</h2>
                                        </div>

                                        <div className="grid grid-cols-2 gap-6">
                                            <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-purple-100 dark:border-purple-800 transition-colors">
                                                <label className="block text-sm font-semibold text-purple-900 dark:text-purple-200 mb-3 flex items-center gap-2 transition-colors">
                                                    <span className="text-xl">👴</span> Elders in Car
                                                </label>
                                                <div className="flex items-center justify-center gap-3">
                                                    <button
                                                        onClick={() => setEldersCount(Math.max(0, eldersCount - 1))}
                                                        className="w-10 h-10 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full hover:bg-red-200 dark:hover:bg-red-900/50 font-bold text-xl transition-all hover:scale-110"
                                                    >
                                                        −
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max="5"
                                                        value={eldersCount}
                                                        onChange={(e) => setEldersCount(Math.max(0, parseInt(e.target.value) || 0))}
                                                        className="w-20 px-3 py-3 border-2 border-purple-300 dark:border-purple-600 rounded-lg text-center text-2xl font-bold text-purple-900 dark:text-purple-100 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-purple-500 transition-colors"
                                                    />
                                                    <button
                                                        onClick={() => setEldersCount(Math.min(5, eldersCount + 1))}
                                                        className="w-10 h-10 bg-green-500 dark:bg-green-700 text-white rounded-full hover:bg-green-600 dark:hover:bg-green-600 font-bold text-xl transition-all hover:scale-110"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>

                                            <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-purple-100 dark:border-purple-800 transition-colors">
                                                <label className="block text-sm font-semibold text-purple-900 dark:text-purple-200 mb-3 flex items-center gap-2 transition-colors">
                                                    <span className="text-xl">👧</span> Children in Car
                                                </label>
                                                <div className="flex items-center justify-center gap-3">
                                                    <button
                                                        onClick={() => setChildrenCount(Math.max(0, childrenCount - 1))}
                                                        className="w-10 h-10 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full hover:bg-red-200 dark:hover:bg-red-900/50 font-bold text-xl transition-all hover:scale-110"
                                                    >
                                                        −
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max="5"
                                                        value={childrenCount}
                                                        onChange={(e) => setChildrenCount(Math.max(0, parseInt(e.target.value) || 0))}
                                                        className="w-20 px-3 py-3 border-2 border-purple-300 dark:border-purple-600 rounded-lg text-center text-2xl font-bold text-purple-900 dark:text-purple-100 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-purple-500 transition-colors"
                                                    />
                                                    <button
                                                        onClick={() => setChildrenCount(Math.min(5, childrenCount + 1))}
                                                        className="w-10 h-10 bg-green-500 dark:bg-green-700 text-white rounded-full hover:bg-green-600 dark:hover:bg-green-600 font-bold text-xl transition-all hover:scale-110"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Stop Interval Info - REDESIGNED */}
                                        <div className="mt-5 p-4 bg-white dark:bg-slate-800 rounded-lg shadow-sm border-2 border-indigo-300 dark:border-indigo-600 transition-colors">
                                            <div className="flex items-center gap-2 mb-3">
                                                <span className="text-xl">📍</span>
                                                <div className="text-base font-bold text-indigo-900 dark:text-indigo-100 transition-colors">Smart Stop Planning</div>
                                            </div>
                                            <div className="space-y-2">
                                                {eldersCount > 0 || childrenCount > 0 ? (
                                                    <>
                                                        <div className="flex items-center gap-2 text-sm text-indigo-800 dark:text-indigo-200 transition-colors">
                                                            <span className="text-green-600 dark:text-green-400 font-bold">✓</span>
                                                            <span>Family trip mode activated</span>
                                                        </div>
                                                        <div className="flex items-center gap-2 text-sm text-indigo-800 dark:text-indigo-200 transition-colors">
                                                            <span className="text-green-600 dark:text-green-400 font-bold">✓</span>
                                                            <span>Passengers: <strong>{eldersCount}</strong> elder(s) + <strong>{childrenCount}</strong> child(ren)</span>
                                                        </div>
                                                        <div className="p-3 bg-gradient-to-r from-green-100 dark:from-green-900/30 to-emerald-100 dark:to-emerald-900/30 rounded-lg mt-2">
                                                            <div className="text-base font-bold text-green-900 dark:text-green-100 transition-colors">
                                                                🛑 Comfort stops every <span className="text-xl text-green-700 dark:text-green-300">{stopInterval} km</span>
                                                            </div>
                                                            <div className="text-xs text-green-700 dark:text-green-300 mt-1 transition-colors">More frequent breaks for comfort</div>
                                                        </div>
                                                    </>
                                                ) : (
                                                    <>
                                                        <div className="flex items-center gap-2 text-sm text-indigo-800 dark:text-indigo-200 transition-colors">
                                                            <span className="text-blue-600 dark:text-blue-400 font-bold">ℹ</span>
                                                            <span>Solo/Adult trip mode</span>
                                                        </div>
                                                        <div className="p-3 bg-gradient-to-r from-blue-100 dark:from-blue-900/30 to-indigo-100 dark:to-indigo-900/30 rounded-lg mt-2">
                                                            <div className="text-base font-bold text-blue-900 dark:text-blue-100 transition-colors">
                                                                🛑 Standard stops every <span className="text-xl text-blue-700 dark:text-blue-300">200 km</span>
                                                            </div>
                                                            <div className="text-xs text-blue-700 dark:text-blue-300 mt-1 transition-colors">Regular intervals for adults</div>
                                                        </div>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Stations & suggestions */}
                                    <div className="bg-white dark:bg-slate-800 p-6 rounded-lg shadow border border-gray-200 dark:border-slate-700 transition-colors">
                                        <div className="flex items-center justify-between flex-wrap gap-3">
                                            <h2 className="text-lg font-semibold text-gray-900 dark:text-slate-100 transition-colors">Stations</h2>

                                            {/* Category Tabs */}
                                            <div className="flex bg-gray-100 dark:bg-slate-700 p-1 rounded-lg transition-colors">
                                                <button onClick={() => setActiveTab('auto')} className={`px-3 py-1 text-sm rounded-md transition ${activeTab === 'auto' ? 'bg-white dark:bg-slate-800 shadow text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'}`}>
                                                    {stationCategory === 'charger' ? '⚡ EV' : '⛽ Fuel'}
                                                </button>
                                                <button onClick={() => setActiveTab('food')} className={`px-3 py-1 text-sm rounded-md transition ${activeTab === 'food' ? 'bg-white dark:bg-slate-800 shadow text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'}`}>
                                                    🍴 Food
                                                </button>
                                                <button onClick={() => setActiveTab('hospital')} className={`px-3 py-1 text-sm rounded-md transition ${activeTab === 'hospital' ? 'bg-white dark:bg-slate-800 shadow text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200'}`}>
                                                    🏥 Health
                                                </button>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <label className="text-xs text-gray-600 dark:text-slate-400 transition-colors">Radius</label>
                                                <input type="number" min="1" max="100" value={searchRadiusKm} onChange={e => setSearchRadiusKm(Math.max(1, Number(e.target.value || 1)))} className="w-16 px-2 py-1 border border-gray-300 dark:border-slate-600 rounded text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100 transition-colors" />
                                            </div>
                                        </div>

                                        <div className="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            {trip?.distance_km >= 30 ? (
                                                <>
                                                    <div>
                                                        <div className="text-sm text-gray-600 dark:text-slate-400 mb-3 transition-colors">
                                                            Range: <span className="font-medium">{effectiveRangeKm ?? '—'}</span> km • Stops needed: <span className="font-medium">{stopPlan.count}</span>
                                                        </div>

                                                        <StationsList
                                            stations={filteredStations}
                                                            onFocus={s => focusOnPoi(s.lat, s.lng)}
                                                            onAddStop={handleAddStop}
                                                        />

                                                        <div className="mt-4 flex gap-2">
                                                            <button onClick={autoAssignStops} className="px-3 py-2 bg-green-600 text-white rounded">Auto-assign stops</button>
                                                            <button onClick={() => { setPinnedStops([]); if (typeof setCurrentTrip === 'function') setCurrentTrip(prev => ({ ...(prev || {}), stops: [] })); }} className="px-3 py-2 bg-gray-100 rounded">Clear pinned</button>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <h3 className="text-sm font-semibold mb-2">Suggested anchors</h3>
                                                        {suggestions.length === 0 ? <p className="text-gray-500 text-sm">No suggestions yet — try Find Stations.</p> : (
                                                            <div className="space-y-2">
                                                                {suggestions.map((s, i) => (
                                                                    <div key={i} className="p-2 border rounded flex items-center justify-between">
                                                                        <div>
                                                                            <div className="font-medium">{s.station?.name ?? '—'}</div>
                                                                            <div className="text-xs text-gray-500">{s.station?.category} • {s.kmFromStart} km</div>
                                                                        </div>
                                                                        <div className="flex flex-col gap-1">
                                                                            <button onClick={() => focusOnPoi(s.station.lat, s.station.lng)} className="px-2 py-1 bg-blue-600 text-white rounded text-xs">Focus</button>
                                                                            <button onClick={() => handleAddStop(s.station)} className="px-2 py-1 bg-green-600 text-white rounded text-xs">Add stop</button>
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                </>
                                            ) : (
                                                <div className="col-span-2 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                                    <p className="text-sm text-blue-700">💡 <strong>Short route:</strong> This journey is under 30 km, so no refueling stops are needed.</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Save button */}
                                    <div className="flex gap-3">
                                        <button onClick={handleDirectSave} disabled={!selectedVehicle} className={`py-3 px-6 rounded font-semibold text-white ${selectedVehicle ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-400 cursor-not-allowed'}`}>
                                            {selectedVehicle ? '💾 Save Entire Trip' : 'Select a Vehicle First'}
                                        </button>
                                        <button onClick={() => navigate(-1)} className="py-3 px-6 rounded border">Cancel</button>
                                    </div>
                                </div>

                                {/* Right column: pinned stops only */}
                                <div className="space-y-6">
                                    <div className="bg-white p-4 rounded-lg shadow border">
                                        <h3 className="text-sm font-semibold mb-2">Pinned Stops ({pinnedStops.length})</h3>
                                        {pinnedStops.length === 0 ? <p className="text-gray-500 text-sm">No stops pinned yet.</p> : (
                                            pinnedStops.map((s, i) => (
                                                <div key={`${s.id}-${i}`} className="flex items-center justify-between gap-3 p-2 border-b">
                                                    <div>
                                                        <div className="font-medium">{s.name}</div>
                                                        <div className="text-xs text-gray-500">{s.category}</div>
                                                    </div>
                                                    <div>
                                                        <button onClick={() => focusOnPoi(s.lat, s.lng)} className="px-2 py-1 bg-gray-100 rounded text-xs">Focus</button>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Save Trip Button */}
                            {journeyData && selectedVehicle && trip?.distance_km > 0 && (
                                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mt-6 transition-colors">
                                    <h3 className="font-semibold text-gray-900 dark:text-slate-100 mb-2">💾 Save This Trip</h3>
                                    <p className="text-sm text-gray-700 dark:text-slate-300 mb-4">
                                        Save this car journey to your trip history with selected vehicle and stops.
                                    </p>
                                    <button
                                        onClick={handleSaveTrip}
                                        disabled={saving}
                                        className="w-full px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition disabled:bg-gray-400 dark:disabled:bg-slate-700 font-semibold"
                                    >
                                        {saving ? 'Saving...' : '✓ Save Trip'}
                                    </button>
                                </div>
                            )}
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
