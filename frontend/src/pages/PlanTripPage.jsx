import React, { useState, useContext, useMemo, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { Marker, Popup } from 'react-leaflet';
import { MapPin, Navigation, Sparkles, Compass, Zap, TrendingUp } from 'lucide-react';
import L from 'leaflet';
import { pointToPolylineDistanceMeters } from '../utils/geometry';
import { getEffectiveEfficiency } from '../utils/vehicleRange';

import MapView from '../components/MapView';
import PoiLegend from '../components/PoiLegend';
import PoiMarker from '../components/PoiMarker';
import CostCard from '../components/CostCard';
import RecommendationCard from '../components/RecommendationCard';
import ComparisonSummary from '../components/ComparisonSummary';
import ModeSelector from '../components/ModeSelector';
import ErrorHandler from '../components/ErrorHandler';
import JourneyExplanationPanel from '../components/JourneyExplanationPanel';

import AITravelInsight from '../components/AITravelInsight';

import { TripContext, JourneyStates } from '../context/TripContext';
import routeAPI from '../api/route';
import costAPI from '../api/cost';
import poiAPI from '../api/poi';
import tripsAPI from '../api/trips';
import recommendationAPI from '../api/recommendation';
import busAPI from '../api/bus';
import trainAPI from '../api/train';
import flightAPI from '../api/flight';
import { formatKm, formatMinutes } from '../utils/formatters';
import { vehicleToPoiCategories, getDefaultPoiToggles, calcStopInterval } from '../utils/poiFilters';
import { scorePoi } from '../utils/poiScoring';

// City suggestions for autocomplete
const CITY_SUGGESTIONS = [
  { name: 'Delhi', aliases: ['new delhi', 'delhi'] },
  { name: 'Mumbai', aliases: ['bombay', 'mumbai'] },
  { name: 'Bangalore', aliases: ['bengaluru', 'bangalore'] },
  { name: 'Hyderabad', aliases: ['hyderabad', 'secunderabad'] },
  { name: 'Chennai', aliases: ['madras', 'chennai'] },
  { name: 'Kolkata', aliases: ['calcutta', 'kolkata'] },
  { name: 'Pune', aliases: ['pune', 'puna'] },
  { name: 'Jaipur', aliases: ['jaipur'] },
  { name: 'Ahmedabad', aliases: ['ahmedabad'] },
  { name: 'Surat', aliases: ['surat'] },
  { name: 'Vijayawada', aliases: ['vijayawada'] },
  { name: 'Visakhapatnam', aliases: ['vizag', 'visakhapatnam'] },
  { name: 'Indore', aliases: ['indore'] },
  { name: 'Lucknow', aliases: ['lucknow'] },
  { name: 'Kanpur', aliases: ['kanpur'] },
  { name: 'Chandigarh', aliases: ['chandigarh'] },
  { name: 'Bhopal', aliases: ['bhopal'] },
  { name: 'Nagpur', aliases: ['nagpur'] },
  { name: 'Coimbatore', aliases: ['coimbatore'] },
  { name: 'Goa', aliases: ['goa', 'panaji'] },
  { name: 'Kochi', aliases: ['kochi', 'cochin'] },
  { name: 'Thiruvananthapuram', aliases: ['thiruvananthapuram', 'trivandrum'] },
  { name: 'Srinagar', aliases: ['srinagar'] },
  { name: 'Agra', aliases: ['agra'] },
  { name: 'Varanasi', aliases: ['varanasi'] },
  { name: 'Allahabad', aliases: ['allahabad'] },
  { name: 'Patna', aliases: ['patna'] },
  { name: 'Ranchi', aliases: ['ranchi'] },
  { name: 'Guwahati', aliases: ['guwahati'] },
  { name: 'Shillong', aliases: ['shillong'] },
  { name: 'Kottayam', aliases: ['kottayam'] },
  { name: 'Thrissur', aliases: ['thrissur'] },
  { name: 'Kozhikode', aliases: ['kozhikode', 'calicut'] },
  { name: 'Kannur', aliases: ['kannur'] },
  { name: 'Kasaragod', aliases: ['kasaragod'] },
  { name: 'Vadodara', aliases: ['vadodara'] }
];

// Helper function to get city suggestions
const getCitySuggestions = (input) => {
  if (!input || input.trim().length === 0) return [];
  const lowerInput = input.toLowerCase().trim();

  const suggestions = CITY_SUGGESTIONS.filter(city =>
    city.name.toLowerCase().includes(lowerInput) ||
    city.aliases.some(alias => alias.includes(lowerInput))
  );

  // Sort alphabetically by city name
  suggestions.sort((a, b) => a.name.localeCompare(b.name));

  return suggestions.slice(0, 8); // limit to 8 suggestions
};



export default function PlanTripPage() {
  const { currentTrip, setCurrentTrip, addStop, removeStop, lastSearch, setLastSearch, journeyState, setJourneyState, journeyError, setJourneyError } = useContext(TripContext);
  const navigate = useNavigate();
  const location = useLocation();

  // form - initialize from lastSearch context
  const [source, setSource] = useState(lastSearch?.source || '');
  const [destination, setDestination] = useState(lastSearch?.destination || '');
  const [passengers, setPassengers] = useState(1);
  const [travelMode, setTravelMode] = useState('car'); // Mode selector state

  // Autocomplete state
  const [sourceSuggestions, setSourceSuggestions] = useState([]);
  const [destinationSuggestions, setDestinationSuggestions] = useState([]);
  const [showSourceSuggestions, setShowSourceSuggestions] = useState(false);
  const [showDestinationSuggestions, setShowDestinationSuggestions] = useState(false);

  // Refs to track mouse over suggestions
  const sourceInputRef = React.useRef(null);
  const destInputRef = React.useRef(null);

  // UI state
  const [loading, setLoading] = useState(false);
  const [savingTrip, setSavingTrip] = useState(false);
  const [error, setError] = useState('');

  // route & map state
  const [routeData, setRouteData] = useState(null); // full route object returned by backend
  const [markers, setMarkers] = useState([]); // generic markers (source/destination + POI markers)
  const [polylineData, setPolylineData] = useState([]); // array of {lat,lng}

  // costs & recommendation
  const [costs, setCosts] = useState(null);
  const [recommendation, setRecommendation] = useState(null);
  const [modeRecommendation, setModeRecommendation] = useState(null); // New: Engine recommendation
  const [vehicleAnalysis, setVehicleAnalysis] = useState(null); // New: Vehicle feasibility
  const [selectedMode, setSelectedMode] = useState(null);
  const [selectedVehicle, setSelectedVehicle] = useState(null);
  const [busTrip, setBusTrip] = useState(null); // Bus mode calculation (STEP 1D)
  const [trainTrip, setTrainTrip] = useState(null); // Train mode calculation (v2.2)
  const [flightTrip, setFlightTrip] = useState(null); // Flight mode calculation

  // POIs and legend filters
  const [pois, setPois] = useState([]); // raw POIs from API
  const [poiSummary, setPoiSummary] = useState(null); // POI summary from backend
  const [poiFeasibility, setPoiFeasibility] = useState(null); // POI feasibility status
  const [poiToggleState, setPoiToggleState] = useState({
    temple: true,
    fuel: true,
    charger: true,
    toll: true,
    restaurant: true,
    hospital: true,
    cng: true,
  });
  const [activePoiMap, setActivePoiMap] = useState(null); // { fuel: true, charger: true, ... }

  // POI scoring and top POIs
  const [poisScored, setPoisScored] = useState([]); // pois with score
  const [topPois, setTopPois] = useState([]); // top N highlighted
  const [poiPrioritySettings, setPoiPrioritySettings] = useState({
    templePriority: 'normal', // 'high'|'normal'|'low'
    eldersCount: 0,
    comfortMode: 'comfort' // or 'mileage'
  });

  // FEATURE: AI Insights Toggle (Level 3+)
  const [aiInsightsEnabled, setAiInsightsEnabled] = useState(false);
  const [aiInsightsAnimating, setAiInsightsAnimating] = useState(false);

  // Handle AI Insights toggle with animation
  const handleAiInsightsToggle = () => {
    setAiInsightsAnimating(true);
    setAiInsightsEnabled(!aiInsightsEnabled);
    setTimeout(() => setAiInsightsAnimating(false), 600);
  };

  const buildMarkers = (route) => {
    const list = [];
    if (route?.source_coords) {
      list.push({
        id: 'src',
        lat: Number(route.source_coords.lat),
        lng: Number(route.source_coords.lng),
        label: source || 'Source',
        type: 'source'
      });
    }
    if (route?.destination_coords) {
      list.push({
        id: 'dst',
        lat: Number(route.destination_coords.lat),
        lng: Number(route.destination_coords.lng),
        label: destination || 'Destination',
        type: 'destination'
      });
    }
    return list;
  };

  const safeNumber = (v, fallback = 0) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : fallback;
  };

  // filter POIs according to active legend map
  const activePoiTypes = useMemo(() => {
    return poiToggleState;
  }, [poiToggleState]);

  const poisFiltered = useMemo(() => {
    if (!pois || pois.length === 0) return [];
    return pois.filter(p => {
      const t = (p.type || p.category || '').toString().toLowerCase();
      return !!activePoiTypes[t];
    });
  }, [pois, activePoiTypes]);

  // ---------- Event handlers ----------
  const handlePoiToggle = (key, isActive) => {
    setPoiToggleState(prev => ({ ...prev, [key]: isActive }));
  };

  const handleAddStop = (poi) => {
    const stop = {
      id: poi.id,
      name: poi.name,
      lat: poi.lat,
      lng: poi.lng,
      category: poi.category,
      inserted_at: new Date().toISOString()
    };
    addStop(stop);
  };

  // Sync markers when POI toggle state changes
  useEffect(() => {
    if (!routeData || !pois.length) return;

    // Filter POIs based on toggle state
    const visiblePois = pois.filter(poi => {
      const category = (poi.category || poi.type || '').toString().toLowerCase();
      return poiToggleState[category] !== false;
    });

    // Rebuild POI markers
    const baseMarkers = buildMarkers(routeData);
    const poiMarkers = visiblePois.map(poi => ({
      id: poi.id,
      lat: poi.lat,
      lng: poi.lng,
      label: poi.name,
      type: `poi-${poi.category || poi.type}`
    }));

    setMarkers([...baseMarkers, ...poiMarkers]);
  }, [poiToggleState, pois, routeData]);

  // Handle vehicle selection from CarConfigurator via location.state
  useEffect(() => {
    if (location.state?.selectedVehicle) {
      setSelectedVehicle(location.state.selectedVehicle);
      // Update POI toggles for this vehicle immediately
      setPoiToggleState(getDefaultPoiToggles(location.state.selectedVehicle));
    }
  }, [location.state]);

  // Watch TripContext.selectedVehicle and sync local state + POI toggles
  useEffect(() => {
    if (currentTrip?.selectedVehicle) {
      setSelectedVehicle(currentTrip.selectedVehicle);
      setPoiToggleState(currentTrip.poiToggleState || getDefaultPoiToggles(currentTrip.selectedVehicle));
    }
  }, [currentTrip?.selectedVehicle]);

  // Save source/destination to context whenever they change
  useEffect(() => {
    setLastSearch({ source, destination });
  }, [source, destination, setLastSearch]);

  // Dynamic re-scoring when priority settings, vehicle, or POIs change
  useEffect(() => {
    if (!pois || !pois.length) return;

    const userPrefs = {
      eldersCount: poiPrioritySettings.eldersCount,
      templePriority: poiPrioritySettings.templePriority,
      comfortMode: poiPrioritySettings.comfortMode
    };

    const scored = pois.map(p => {
      const copy = { ...p };
      copy.score = scorePoi(copy, {
        vehicle: selectedVehicle || recommendation,
        userPrefs,
        maxDistance: 30000
      });
      return copy;
    }).sort((a, b) => (b.score || 0) - (a.score || 0));

    setPoisScored(scored);
    setTopPois(scored.slice(0, 6));
  }, [poiPrioritySettings, selectedVehicle, pois, recommendation]);

  const handlePlanRoute = async (e) => {
    e.preventDefault();
    setError('');
    setJourneyError(null);
    // PHASE 5: Transition to PLANNING state
    setJourneyState(JourneyStates.PLANNING);

    if (!source || !destination) {
      const err = 'Please enter both source and destination';
      setError(err);
      setJourneyError({ code: 'INVALID_INPUT', message: err });
      setJourneyState(JourneyStates.ERROR);
      return;
    }

    // PHASE 5: Check for same source/destination
    if (source.toLowerCase().trim() === destination.toLowerCase().trim()) {
      const err = 'Source and destination are the same. You\'re already there!';
      setError(err);
      setJourneyError({ code: 'SAME_SOURCE_DEST', message: err });
      setJourneyState(JourneyStates.ERROR);
      return;
    }

    try {
      console.group('🛣️ PLAN ROUTE DEBUG');
      console.log('📍 Source:', source);
      console.log('📍 Destination:', destination);
      console.log('🚗 Vehicle:', selectedVehicle);

      // ask backend to plan route
      const routePayload = { source, destination };
      if (selectedVehicle) {
        routePayload.vehicle = selectedVehicle; // Include vehicle for feasibility check
      }

      const routeRes = await routeAPI.planRoute(routePayload);
      const route = routeRes.data;

      console.log('📡 Backend response received:', route);

      if (!route?.ok) {
        const errorMsg = route?.message || 'Routing failed';
        console.error('❌ Backend returned error:', errorMsg);

        // Preserve backend error details instead of throwing plain Error
        const backendError = new Error(errorMsg);
        backendError.isBackendError = true;
        backendError.backendData = route;
        backendError.httpCode = route?.http_code;
        backendError.debugError = route?.debug_error;
        throw backendError;
      }

      // normalize numbers and polyline
      route.distance_km = safeNumber(route.distance_km, 0);
      route.duration_min = safeNumber(route.duration_min, 0);
      const poly = Array.isArray(route.polyline) ? route.polyline.map(p => ({ lat: Number(p.lat), lng: Number(p.lng) })) : [];

      console.log('✅ Route normalized:', {
        distance_km: route.distance_km,
        duration_min: route.duration_min,
        polyline_points: poly.length
      });

      // ========== NEW: Extract vehicle analysis if available ==========
      if (route.vehicle_analysis) {
        setVehicleAnalysis(route.vehicle_analysis);
        console.log('🚗 Vehicle analysis:', route.vehicle_analysis);
      }

      console.groupEnd();

      setRouteData(route);
      setPolylineData(poly);

      // build markers (source/destination)
      const baseMarkers = buildMarkers(route);
      setMarkers(baseMarkers);

      // get costs (backend)
      try {
        const costRes = await costAPI.calculateCost({
          distance_km: route.distance_km,
          passengers
        });
        if (costRes.data?.ok !== false) {
          let costsData = costRes.data;
          if (costRes.data.recommendation) {
            setRecommendation(costRes.data.recommendation);
            setSelectedMode(costRes.data.recommendation.mode);
          }

          // ========== STEP 1D: Get bus calculation ==========
          let busData = null;
          try {
            const busRes = await busAPI.calculateBusTrip(route.distance_km);
            if (busRes?.ok) {
              setBusTrip(busRes);
              busData = busRes;
              // Add bus cost to costs object
              costsData.bus = busRes.cost;
              console.log('✅ Bus calculation:', busRes);
            }
          } catch (busErr) {
            console.warn('⚠️ Bus calculation failed', busErr);
          }

          // ========== v2.2: Get train calculation ==========
          let trainData = null;
          try {
            const trainRes = await trainAPI.calculateTrainTrip(route.distance_km);
            if (trainRes?.ok) {
              setTrainTrip(trainRes);
              trainData = trainRes;
              costsData.train = trainRes.cost; // Add to costs object
              console.log('✅ Train calculation:', trainRes);
            }
          } catch (trainErr) {
            console.warn('⚠️ Train calculation failed', trainErr);
          }

          // ========== Get flight calculation ==========
          let flightData = null;
          try {
            const flightRes = await flightAPI.calculateFlightTrip(route.distance_km);
            if (flightRes?.ok) {
              setFlightTrip(flightRes);
              flightData = flightRes;
              costsData.flight = flightRes.cost; // Add to costs object
              console.log('✅ Flight calculation:', flightRes);
            }
          } catch (flightErr) {
            console.warn('⚠️ Flight calculation failed', flightErr);
          }



          // Now set costs with bus data included
          setCosts(costsData);

          // ========== NEW: Get mode recommendation from engine ==========
          try {
            const recRes = await recommendationAPI.recommendMode({
              distance_km: route.distance_km,
              car_cost: costRes.data?.car || null,
              car_duration_min: route.duration_min || null,
              ev_cost: costRes.data?.ev || null,
              ev_charging_stops: null,  // Will be calculated if needed
              bus_cost: busData?.cost || null,
              bus_duration_min: busData?.total_duration_min || null,
              bus_stops: busData?.stops || null,
              train_cost: trainData?.cost || null,
              train_duration_min: trainData?.total_duration_min || null,
              flight_cost: flightData?.cost || null,
              flight_duration_min: flightData?.total_duration_min || null,
              passengers: passengers || 1,
              elders: 0  // Can be extended from trip data if available
            });
            if (recRes?.ok) {
              setModeRecommendation(recRes);
              console.log('✅ Mode recommendation:', recRes);
            }
          } catch (recErr) {
            console.warn('⚠️ Mode recommendation fetch failed', recErr);
          }
        }
      } catch (err) {
        console.warn('Cost fetch failed', err);
      }

      // get POIs (best-effort)
      {
        try {
          // compute categories based on selected vehicle if any; else default show all
          const vehicleForPoi = selectedVehicle || recommendation;
          const categoriesToRequest = vehicleForPoi
            ? vehicleToPoiCategories(vehicleForPoi)
            : ['temple', 'fuel', 'charger', 'toll', 'restaurant', 'hospital'];

          // Set toggle state to match vehicle categories (but still allow user to override)
          if (vehicleForPoi) {
            setPoiToggleState(getDefaultPoiToggles(vehicleForPoi));
          }

          // DEBUG: Log route and category info
          console.group('🔍 POI FETCH DEBUG');
          console.log('poly (first 3 coords):', poly && poly.slice(0, 3));
          console.log('activeCategories:', categoriesToRequest);
          console.log('polyline length:', poly ? poly.length : 0);

          const poisRes = await poiAPI.getPoisForRoute({
            polyline: poly,
            vehicle: vehicleForPoi,
            categories: categoriesToRequest,
            radius_km: 3
          });

          console.log('poisRes:', poisRes);

          // Store POI summary and feasibility
          if (poisRes.summary) {
            setPoiSummary(poisRes.summary);
          }
          if (poisRes.feasibility) {
            setPoiFeasibility(poisRes.feasibility);
          }

          // Check feasibility status - 422 means route is infeasible
          if (!poisRes.ok && poisRes.feasibility?.status === 'FAIL') {
            console.warn('⚠️ POI feasibility check failed:', poisRes.feasibility);
            // Set error state to show user-friendly message
            setError(`Route not feasible for ${vehicleForPoi?.fuel_type || 'selected vehicle'}: ${poisRes.reason || poisRes.error || 'Insufficient infrastructure'}`);
            setPois([]);
            setPoisScored([]);
            setTopPois([]);
            setTopPois([]);
            setJourneyState(JourneyStates.ERROR); // CRITICAL: Stop loading state
            console.groupEnd();
            return; // Don't process POIs if route is infeasible
          }

          console.log('backend POI count:', poisRes.pois?.length ?? 0);
          console.log('feasibility:', poisRes.feasibility);

          const backendPois = poisRes.pois;
          if (Array.isArray(backendPois) && backendPois.length > 0) {
            console.log('✅ Backend POIs count:', backendPois.length);
            // normalize keys expected by components
            const normalized = backendPois.map((p, idx) => ({
              id: p.id ?? `poi-${idx}`,
              name: p.name ?? p.title ?? 'POI',
              type: (p.type || p.category || '').toString().toLowerCase(),
              category: p.category || p.type || 'poi',
              rating: typeof p.rating === 'number' ? p.rating : (p.rating ? Number(p.rating) : null),
              lat: Number(p.lat ?? p.latitude ?? p.lat),
              lng: Number(p.lng ?? p.longitude ?? p.lng),
              distance_m: p.distance_m ?? null,
              raw: p
            }));
            console.log('✅ Normalized POIs count:', normalized.length);

            // compute distance_to_route_m if not provided, score POIs and set top list
            const scored = normalized.map(p => {
              const copy = { ...p };
              if (typeof copy.distance_m === 'number' && !copy.distance_to_route_m) {
                copy.distance_to_route_m = Number(copy.distance_m);
              }
              // if API didn't provide distance_to_route_m, compute from polyline
              if (typeof copy.distance_to_route_m === 'undefined' || copy.distance_to_route_m === null) {
                if (poly && poly.length) {
                  copy.distance_to_route_m = pointToPolylineDistanceMeters({ lat: copy.lat, lng: copy.lng }, poly);
                } else {
                  copy.distance_to_route_m = null;
                }
              }
              // score using current selectedVehicle and user prefs
              const userPrefs = {
                eldersCount: poiPrioritySettings.eldersCount || 0,
                templePriority: poiPrioritySettings.templePriority,
                comfortMode: poiPrioritySettings.comfortMode
              };
              copy.score = scorePoi(copy, { vehicle: selectedVehicle || recommendation, userPrefs, maxDistance: 30000 });
              return copy;
            });

            console.log('✅ Scored POIs count:', scored.length, 'Sample:', scored[0]);
            console.groupEnd();

            // sort descending by score
            scored.sort((a, b) => (b.score || 0) - (a.score || 0));

            setPoisScored(scored);
            setTopPois(scored.slice(0, 6)); // top 6 highlighted
            setPois(scored); // keep existing filtered list logic working
          } else {
            console.log('⚠️ No backend POIs returned.');
            console.groupEnd();
            setPois([]);
            setPoisScored([]);
            setTopPois([]);
          }
        } catch (poiErr) {
          console.warn('❌ POI fetch failed', poiErr);
          console.groupEnd();

          // Phase 4: Handle structured error codes
          const errorCode = poiErr.response?.data?.error_code;
          const errorMsg = poiErr.response?.data?.message;

          if (poiErr.response?.status === 422 || errorCode === 'NO_INFRASTRUCTURE') {
            const errorData = poiErr.response.data;
            setError(`Route not feasible: ${errorData.message || errorData.reason || 'Insufficient infrastructure for selected vehicle'}`);
            setPoiFeasibility(errorData.feasibility);
          } else if (errorCode === 'POI_ZERO_RESULTS') {
            // No POIs found but route is OK
            console.log('ℹ️ No points of interest found along route, but route is valid');
            setPois([]);
            setPoisScored([]);
            setTopPois([]);
          } else if (poiErr.response?.status === 400 || errorCode === 'INVALID_INPUT') {
            setError(`Invalid request: ${errorMsg || 'Please check your route'}`);
          } else if (poiErr.response?.status === 500 || errorCode === 'DB_ERROR') {
            setError('Server error: Please try again later');
          } else {
            setError(`Route feasibility check failed: ${errorMsg || 'Please try a different route'}`);
          }
          setPois([]);
          setPoisScored([]);
          setTopPois([]);
        }
      }

      // set current trip in context (optional)
      setCurrentTrip({
        source,
        destination,
        distance_km: route.distance_km,
        duration_min: route.duration_min,
        passengers,
        polyline: poly
      });

      // CRITICAL: Successfully planned
      setJourneyState(JourneyStates.PLANNED);
      setLoading(false);
    } catch (err) {
      console.groupEnd && console.groupEnd();

      // Comprehensive error logging
      console.group('❌ PLAN ROUTE ERROR');

      if (err.response) {
        // Axios HTTP error - server responded with error status
        console.error('🔴 Axios HTTP Error');
        console.error('Status:', err.response.status);
        console.error('Data:', err.response.data);
        console.error('Headers:', err.response.headers);
      } else if (err.request) {
        // Axios network error - request sent but no response
        console.error('🔴 Network Error - Request sent, no response received');
        console.error('Request:', err.request);
      } else if (err.isBackendError) {
        // Custom backend error with preserved details
        console.error('🔴 Backend Error');
        console.error('Message:', err.message);
        console.error('Backend Data:', err.backendData);
        console.error('HTTP Code:', err.httpCode);
        console.error('Debug Error:', err.debugError);
      } else {
        // Plain JavaScript error
        console.error('🔴 JavaScript Error');
        console.error('Message:', err.message);
      }

      console.error('Full error object:', err);
      console.groupEnd();

      // Build user-friendly error message (Phase 4: handle error_code)
      let errorMessage = 'Failed to plan route';
      let errorDetails = '';

      const errorCode = err.response?.data?.error_code;

      if (errorCode) {
        // Map Phase 4 error codes to user-friendly messages
        const errorMap = {
          'INVALID_INPUT': 'Invalid locations or parameters. Please check your input.',
          'OSRM_DOWN': 'Route service is temporarily unavailable. Please try again.',
          'DB_ERROR': 'Server database error. Please try again later.',
          'RATE_LIMITED': 'Too many requests. Please wait a moment and try again.',
          'INTEGRITY_FAIL': 'Data validation failed. Please re-enter your trip.',
        };
        errorMessage = errorMap[errorCode] || err.response.data.message || 'Route planning failed';
      } else if (err.response?.data?.message) {
        errorMessage = err.response.data.message;
        errorDetails = err.response.status ? ` (HTTP ${err.response.status})` : '';
      } else if (err.isBackendError) {
        errorMessage = err.message;
        if (err.httpCode) {
          errorDetails += ` (HTTP ${err.httpCode})`;
        }
        if (err.debugError) {
          errorDetails += ` - ${err.debugError}`;
        }
      } else if (err.message) {
        errorMessage = err.message;
      }

      setError(`Routing error: ${errorMessage}${errorDetails}`);
      setJourneyError({ code: errorCode || 'ROUTE_PLANNING_FAILED', message: errorMessage });
      // PHASE 5: Transition to ERROR state
      setJourneyState(JourneyStates.ERROR);
    } finally {
      // PHASE 5: Cleanup only - state transitions handled explicitly above
      setLoading(false);
    }
  };

  // LEVEL 1: Compute highlights (deterministic) - Memoized for shared use
  const highlights = useMemo(() => {
    if (!costs || !routeData) return null;

    const modes = Object.entries(costs)
      .filter(([key]) => ['car', 'ev', 'bus', 'train', 'flight'].includes(key))
      .map(([mode, cost]) => {
        let duration = routeData.duration_min;
        if (mode === 'bus' && busTrip) duration = busTrip.total_duration_min;
        if (mode === 'train' && trainTrip) duration = trainTrip.total_duration_min;
        if (mode === 'flight' && flightTrip) duration = flightTrip.total_duration_min;
        return { mode, cost, duration };
      });

    if (modes.length === 0) return null;

    // Find cheapest (lowest cost)
    const cheapest = modes.reduce((a, b) => a.cost < b.cost ? a : b).mode;

    // Find fastest (lowest duration)
    const fastest = modes.reduce((a, b) => (a.duration || Infinity) < (b.duration || Infinity) ? a : b).mode;

    // Find eco-friendly (EV first, then Train)
    let eco = null;
    if (costs.ev) eco = 'ev';
    else if (costs.train) eco = 'train';

    return { cheapest, fastest, eco, modesData: modes };
  }, [costs, routeData, busTrip, trainTrip, flightTrip]);

  // LEVEL 2: Helper to get formatted cost range for AI
  const getFormattedCostRange = (mode, baseCost) => {
    // Re-implementing simplified logic or importing helper would be better
    // But for now, simple implementation to match Level 2 logic
    let rangePercent = 0.15;
    if (['car', 'ev'].includes(mode)) rangePercent = 0.05;
    if (['train', 'flight'].includes(mode)) rangePercent = 0.30;

    const min = Math.round(baseCost * (1 - rangePercent) / 10) * 10;
    const max = Math.round(baseCost * (1 + rangePercent) / 10) * 10;
    return `₹${min.toLocaleString('en-IN')} – ₹${max.toLocaleString('en-IN')}`;
  };

  const handleSaveTrip = async () => {
    if (!selectedMode || !routeData) {
      setError('Please select a transportation mode');
      setJourneyError({ code: 'NO_MODE_SELECTED', message: 'You must select a transportation mode before saving' });
      setJourneyState(JourneyStates.ERROR);
      return;
    }
    // PHASE 5: Transition to SAVING state
    setSavingTrip(true);
    setJourneyState(JourneyStates.SAVING);
    try {
      await tripsAPI.saveTrip({
        source,
        destination,
        distance_km: routeData.distance_km,
        duration_min: routeData.duration_min,
        selected_mode: selectedMode,
        costs,
        passengers,
        polyline: polylineData,
        selected_vehicle: selectedVehicle,
        stops: markers.filter(m => m.type && m.type.startsWith('poi-'))
          .map(m => ({ id: m.id, name: m.label, lat: m.lat, lng: m.lng }))
      });
      // PHASE 5: Transition to SAVED state
      setJourneyState(JourneyStates.SAVED);
      navigate('/trips');
    } catch (err) {
      // Phase 4: Handle structured error codes from trip save
      const errorCode = err.response?.data?.error_code;
      const errorMsg = err.response?.data?.message;
      let displayError = '';

      if (errorCode === 'INVALID_SIGNATURE') {
        displayError = 'Trip data was tampered with. Please re-plan your trip.';
      } else if (errorCode === 'DUPLICATE_ENTRY') {
        displayError = 'This trip was already saved. Check your saved trips.';
      } else if (errorCode === 'DB_ERROR') {
        displayError = 'Database error while saving. Please try again.';
      } else if (errorCode === 'VALIDATION_FAIL') {
        displayError = `Trip validation failed: ${err.response?.data?.details?.field || 'unknown field'}`;
      } else if (err.response?.status === 400) {
        displayError = `Invalid trip data: ${errorMsg || 'Please check your route and try again.'}`;
      } else if (err.response?.status >= 500) {
        displayError = 'Server error. Please try again later.';
      } else {
        displayError = errorMsg || 'Failed to save trip';
      }

      setError(displayError);
      setJourneyError({ code: errorCode || 'SAVE_FAILED', message: displayError });
      // PHASE 5: Transition to ERROR state
      setJourneyState(JourneyStates.ERROR);
    } finally {
      setSavingTrip(false);
    }
  };



  // ---------- Render ----------
  return (
    <div className="min-h-screen riq-page-shell riq-plan-page transition-colors">
      <ErrorHandler error={journeyError} onDismiss={() => setJourneyError(null)} onRetry={handlePlanRoute} />

      <div style={{ maxWidth: '1280px', margin: '0 auto', padding: '1.5rem' }}>
        {/* ── Enhanced Page Header ── */}
        <div className="riq-hero-strip" style={{ marginBottom: '2rem', padding: '2rem', background: 'linear-gradient(135deg, var(--riq-surface-elevated), var(--riq-surface))', border: '1px solid var(--riq-border)', borderRadius: 'var(--riq-radius-xl)', position: 'relative', overflow: 'hidden' }}>
          <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: '4px', background: 'linear-gradient(90deg, #6366f1, #14b8a6, #8b5cf6)' }}></div>
          <div className="flex items-center gap-4">
            <div style={{ width: '4rem', height: '4rem', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'linear-gradient(135deg, var(--riq-accent), var(--riq-secondary))', borderRadius: 'var(--riq-radius-xl)', boxShadow: '0 8px 20px rgba(99, 102, 241, 0.3)', color: 'white', flexShrink: 0 }}>
              <Compass size={28} />
            </div>
            <div className="flex-1">
              <p className="riq-eyebrow" style={{ marginBottom: '0.25rem' }}>Journey Builder</p>
              <h1 style={{ fontSize: 'clamp(1.75rem, 3.5vw, 2.25rem)', fontWeight: 900, color: 'var(--riq-text)', margin: 0, lineHeight: 1.1 }}>
                Plan Your <span className="riq-gradient-text">Trip</span>
              </h1>
            </div>
            <div className="hidden sm:flex flex-wrap" style={{ gap: '0.5rem' }}>
              <span className="riq-badge primary"><Zap size={12} /> Mode Compare</span>
              <span className="riq-badge info"><MapPin size={12} /> POI Discovery</span>
              <span className="riq-badge success"><TrendingUp size={12} /> Cost Insights</span>
            </div>
          </div>
        </div>

        <div className="grid lg:grid-cols-3 gap-6">
          {/* Left / main column */}
          <div className="lg:col-span-2 space-y-5">

            <form onSubmit={handlePlanRoute} className="riq-panel riq-fade-up p-5 transition-colors">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                <h2 style={{ fontSize: '1.1rem', fontWeight: 700, color: 'var(--riq-text)', margin: 0 }}>Trip Details</h2>

                {/* AI Insights Toggle */}
                <div className={`flex items-center gap-2.5 px-3 py-1.5 rounded-lg border transition-all duration-300 ${aiInsightsAnimating ? 'scale-105 shadow-md' : ''}`}
                  style={{ background: 'var(--riq-surface-elevated)', borderColor: 'var(--riq-border)' }}>
                  <div className="text-right hidden sm:block">
                    <label htmlFor="ai-toggle-main" style={{ display: 'block', fontSize: '0.7rem', fontWeight: 600, color: 'var(--riq-text)', cursor: 'pointer' }}>AI Insights</label>
                    <p style={{ fontSize: '0.6rem', color: 'var(--riq-text-muted)', margin: 0 }}>Explains recommendations</p>
                  </div>
                  <label className="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="ai-toggle-main" className="sr-only peer" checked={aiInsightsEnabled} onChange={handleAiInsightsToggle} />
                    <div className="w-9 h-5 bg-slate-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                  </label>
                </div>
              </div>

              {error && <div style={{ marginBottom: '0.75rem', padding: '0.6rem 0.75rem', background: 'rgba(239,68,68,0.08)', border: '1px solid rgba(239,68,68,0.2)', borderRadius: '0.5rem' }}><p style={{ color: '#dc2626', fontSize: '0.8rem', margin: 0 }}>{error}</p></div>}

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {/* Source */}
                <div style={{ position: 'relative' }}>
                  <input ref={sourceInputRef} value={source}
                    onChange={e => { setSource(e.target.value); setSourceSuggestions(getCitySuggestions(e.target.value)); setShowSourceSuggestions(true); }}
                    onFocus={() => { setShowSourceSuggestions(true); if (source) setSourceSuggestions(getCitySuggestions(source)); }}
                    onBlur={() => { setTimeout(() => setShowSourceSuggestions(false), 150); }}
                    placeholder="Source (e.g. Delhi)" style={{ width: '100%' }} />
                  {showSourceSuggestions && sourceSuggestions.length > 0 && (
                    <div className="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded shadow-lg z-10">
                      {sourceSuggestions.map((city, idx) => (
                        <div key={idx} onMouseDown={(e) => { e.preventDefault(); setSource(city.name); setShowSourceSuggestions(false); }}
                          className="px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 cursor-pointer text-slate-900 dark:text-slate-100 text-sm border-b border-slate-200 dark:border-slate-600 last:border-b-0">📍 {city.name}</div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Destination */}
                <div style={{ position: 'relative' }}>
                  <input ref={destInputRef} value={destination}
                    onChange={e => { setDestination(e.target.value); setDestinationSuggestions(getCitySuggestions(e.target.value)); setShowDestinationSuggestions(true); }}
                    onFocus={() => { setShowDestinationSuggestions(true); if (destination) setDestinationSuggestions(getCitySuggestions(destination)); }}
                    onBlur={() => { setTimeout(() => setShowDestinationSuggestions(false), 150); }}
                    placeholder="Destination (e.g. Mumbai)" style={{ width: '100%' }} />
                  {showDestinationSuggestions && destinationSuggestions.length > 0 && (
                    <div className="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded shadow-lg z-10">
                      {destinationSuggestions.map((city, idx) => (
                        <div key={idx} onMouseDown={(e) => { e.preventDefault(); setDestination(city.name); setShowDestinationSuggestions(false); }}
                          className="px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 cursor-pointer text-slate-900 dark:text-slate-100 text-sm border-b border-slate-200 dark:border-slate-600 last:border-b-0">📍 {city.name}</div>
                      ))}
                    </div>
                  )}
                </div>

                <input type="number" min="1" value={passengers} onChange={e => setPassengers(Math.max(1, parseInt(e.target.value || '1', 10)))} placeholder="Passengers" />
                <button type="submit" className="riq-btn-primary" style={{ width: '100%', justifyContent: 'center', padding: '0.6rem 1rem' }}
                  disabled={journeyState === JourneyStates.PLANNING || journeyState === JourneyStates.SAVING}>
                  {journeyState === JourneyStates.PLANNING ? 'Planning Route...' : 'Plan Route'}
                </button>
              </div>
            </form>

            {/* Map area */}
            <div className="riq-panel riq-map-shell riq-fade-up p-4 transition-colors">
              <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--riq-text)', marginBottom: '0.75rem' }}>Route Map</h3>

              <MapView
                center={routeData?.source_coords ? [routeData.source_coords.lat, routeData.source_coords.lng] : [28.6139, 77.209]}
                zoom={8}
                markers={markers}
                polyline={polylineData}
                onMapReady={(map) => { window.__routeiq_map = map; }}
              >
                {poisFiltered.map((poi, idx) => {
                  const isTop = topPois.some(t => t.id === poi.id);
                  const icon = isTop
                    ? L.divIcon({ html: `<div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-bold text-sm shadow-lg">★</div>`, className: 'top-poi-icon', iconSize: [32, 32], iconAnchor: [16, 16] })
                    : L.divIcon({ html: `<div class="flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-700 rounded-full text-xs shadow">•</div>`, className: 'regular-poi-icon', iconSize: [24, 24], iconAnchor: [12, 12] });
                  return (
                    <Marker key={poi.id} position={[poi.lat, poi.lng]} icon={icon}>
                      <Popup>
                        <PoiMarker name={poi.name} category={poi.category} rating={Number(poi.rating || 0)} latitude={poi.lat} longitude={poi.lng} onClick={() => console.log('open poi', poi.id)} />
                      </Popup>
                    </Marker>
                  );
                })}
              </MapView>

              {/* Route quick stats — only show when a route is loaded */}
              {routeData && (
                <div className="mt-4 riq-quick-stats">
                  <div className="riq-stat-tile">
                    <p style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--riq-text-muted)' }}>Distance</p>
                    <p className="riq-kpi-value">{formatKm(routeData.distance_km)}</p>
                  </div>
                  <div className="riq-stat-tile">
                    <p style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--riq-text-muted)' }}>Duration</p>
                    <p className="riq-kpi-value">{formatMinutes(routeData.duration_min)}</p>
                  </div>
                  <div className="riq-stat-tile">
                    <p style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--riq-text-muted)' }}>POIs</p>
                    <p className="riq-kpi-value">{poisFiltered.length}</p>
                  </div>
                </div>
              )}

              {/* POI Category Counts */}
              {poiSummary && poiSummary.by_category && Object.keys(poiSummary.by_category).length > 0 && (
                <div className="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <h4 className="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">POIs by Category</h4>
                  <div className="grid grid-cols-2 gap-2 text-sm">
                    {Object.entries(poiSummary.by_category).map(([category, count]) => (
                      <div key={category} className="flex justify-between items-center">
                        <span className="text-blue-700 dark:text-blue-300 capitalize">{category}:</span>
                        <span className="font-bold text-blue-900 dark:text-blue-200">{count}</span>
                      </div>
                    ))}
                  </div>
                  <p className="text-xs text-blue-600 dark:text-blue-300 mt-2">
                    Total: {poiSummary.total_pois} POIs found, showing {poiSummary.returned_pois}
                  </p>
                </div>
              )}

              {/* POI Feasibility Warning */}
              {poiFeasibility && poiFeasibility.status === 'FAIL' && (
                <div className="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-800 rounded-lg">
                  <div className="flex items-start gap-3">
                    <div className="text-2xl">❌</div>
                    <div className="flex-1">
                      <h4 className="text-sm font-bold text-red-900 dark:text-red-200 mb-1">Route Not Feasible</h4>
                      <p className="text-sm text-red-700 dark:text-red-300 mb-2">
                        This route lacks sufficient infrastructure for your {poiFeasibility.vehicle || 'selected vehicle'}.
                      </p>
                      <div className="text-xs text-red-600 dark:text-red-400 space-y-1">
                        <p><strong>Max gap between stops:</strong> {poiFeasibility.max_gap_km} km</p>
                        <p><strong>Vehicle range:</strong> {poiFeasibility.effective_range_km} km</p>
                      </div>
                      <p className="text-xs text-red-800 dark:text-red-300 mt-2 font-semibold">
                        💡 Try: Different route, vehicle type, or expand search radius
                      </p>
                    </div>
                  </div>
                </div>
              )}


            </div>

            {/* ========== FINAL COMPARISON SUMMARY ========== */}
            {/* Only show if AI Insights is enabled */}
            {modeRecommendation && modeRecommendation.ok && costs && aiInsightsEnabled && (
              <ComparisonSummary
                source={source}
                destination={destination}
                recommendation={modeRecommendation}
                distance_km={routeData?.distance_km}
                car={{
                  cost: costs?.car,
                  duration: routeData?.duration_min,
                  stops: Math.ceil((routeData?.distance_km || 0) / 300) // Estimated stops
                }}
                ev={{
                  cost: costs?.ev,
                  chargingStops: modeRecommendation.ev_charging_stops,
                  feasible: true
                }}
                train={trainTrip ? {
                  cost: trainTrip?.cost,
                  duration: trainTrip?.total_duration_min,
                  available: true
                } : null}
                bus={busTrip ? {
                  cost: busTrip?.cost,
                  duration: busTrip?.total_duration_min,
                  stops: busTrip?.stops,
                  available: true
                } : null}
                flight={flightTrip ? {
                  cost: flightTrip?.cost,
                  duration: flightTrip?.total_duration_min,
                  available: true
                } : null}
              />
            )}


          </div>

          {/* Sidebar */}
          <div className="space-y-5">
            {/* Selected Vehicle Summary Card */}
            {selectedVehicle && (
              <div style={{ background: 'linear-gradient(135deg, #6366f1, #7c3aed)', borderRadius: '0.75rem', padding: '1.25rem', color: '#fff', border: '1px solid rgba(255,255,255,0.15)' }}>
                <h3 className="text-lg font-semibold mb-4">Selected Vehicle</h3>
                <div className="space-y-3 text-sm">
                  <div className="flex justify-between">
                    <span className="opacity-90">Model:</span>
                    <span className="font-semibold">{selectedVehicle.name || selectedVehicle.model || 'Unknown'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="opacity-90">Fuel Type:</span>
                    <span className="font-semibold capitalize">{selectedVehicle.fuel_type || selectedVehicle.fuel || 'N/A'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="opacity-90">Mileage:</span>
                    <span className="font-semibold">{getEffectiveEfficiency(selectedVehicle) || 'N/A'} km/Liter</span>
                  </div>
                  {selectedVehicle.cost_per_unit && (
                    <div className="flex justify-between">
                      <span className="opacity-90">Cost/Liter:</span>
                      <span className="font-semibold">₹{selectedVehicle.cost_per_unit.toFixed(2)}</span>
                    </div>
                  )}
                  <button
                    onClick={() => navigate('/car-configurator', { state: { trip: routeData, markers, polyline: polylineData } })}
                    style={{ width: '100%', marginTop: '1rem', padding: '0.5rem', background: 'rgba(255,255,255,0.95)', color: '#4f46e5', borderRadius: '0.5rem', fontWeight: 600, fontSize: '0.85rem', border: 'none', cursor: 'pointer' }}
                  >
                    Change Vehicle
                  </button>
                </div>
              </div>
            )}

            {/* Stop Interval Preview Card */}
            {selectedVehicle && routeData && currentTrip?.preferences && (
              <div className="riq-panel p-6">
                <h3 className="text-lg font-semibold text-slate-900 mb-4">Stop Recommendations</h3>
                <div className="space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-slate-600">Mode:</span>
                    <span className="font-semibold text-slate-900 capitalize">{currentTrip.preferences.comfortMode || 'comfort'}</span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-slate-600">Stop Interval:</span>
                    <span className="font-semibold text-slate-900">{currentTrip.preferences.stopInterval || 'N/A'} km</span>
                  </div>
                  {routeData?.distance_km && currentTrip.preferences.stopInterval && (
                    <div className="flex justify-between items-center pt-2 border-t border-slate-200">
                      <span className="text-slate-600">Estimated Stops:</span>
                      <span style={{ fontWeight: 700, fontSize: '1.1rem', color: '#6366f1' }}>
                        {Math.max(0, Math.ceil(routeData.distance_km / currentTrip.preferences.stopInterval) - 1)}
                      </span>
                    </div>
                  )}
                </div>
              </div>
            )}

            {recommendation && routeData && aiInsightsEnabled && (
              <RecommendationCard
                mode={recommendation.mode}
                reason={recommendation.reason}
                cost={costs?.[recommendation.mode]}
                distance={routeData.distance_km}
                duration={
                  recommendation.mode === 'bus' && busTrip ? busTrip.total_duration_min :
                    recommendation.mode === 'train' && trainTrip ? trainTrip.total_duration_min :
                      recommendation.mode === 'flight' && flightTrip ? flightTrip.total_duration_min :
                        routeData.duration_min
                }
                source={source}
                destination={destination}
              />
            )}

            {/* PHASE 5: Journey Explanation Panel - Shows why each mode was chosen */}
            {/* PHASE 5: Journey Explanation Panel - Shows why each mode was chosen */}
            {selectedMode && routeData && costs && aiInsightsEnabled && (
              <JourneyExplanationPanel
                mode={selectedMode}
                distance={routeData.distance_km}
                duration={
                  selectedMode === 'bus' && busTrip ? busTrip.total_duration_min :
                    selectedMode === 'train' && trainTrip ? trainTrip.total_duration_min :
                      selectedMode === 'flight' && flightTrip ? flightTrip.total_duration_min :
                        routeData.duration_min
                }
                cost={costs[selectedMode]}
                source={source}
                destination={destination}
              />
            )}

            {/* ========== NEW: VEHICLE FEASIBILITY ANALYSIS ========== */}
            {vehicleAnalysis && (
              <div className={`border-2 rounded-lg p-6 shadow-sm ${vehicleAnalysis.feasible
                ? 'bg-gradient-to-br from-green-50 to-emerald-100 border-green-300'
                : 'bg-gradient-to-br from-red-50 to-rose-100 border-red-300'
                }`}>
                <div className="flex items-start gap-4">
                  <div className="text-3xl">
                    {vehicleAnalysis.feasible ? '✅' : '❌'}
                  </div>
                  <div className="flex-1">
                    <h3 className="text-sm font-semibold uppercase tracking-wide mb-1"
                      style={{ color: vehicleAnalysis.feasible ? '#047857' : '#dc2626' }}>
                      Route Feasibility
                    </h3>
                    <p className="text-lg font-bold mb-2"
                      style={{ color: vehicleAnalysis.feasible ? '#065f46' : '#7f1d1d' }}>
                      {vehicleAnalysis.feasible ? 'Route is feasible for this vehicle' : 'Route is NOT feasible for this vehicle'}
                    </p>

                    {/* Vehicle specs */}
                    <div className="mt-3 pt-3 border-t" style={{ borderColor: vehicleAnalysis.feasible ? '#d1fae5' : '#fee2e2' }}>
                      <div className="grid grid-cols-2 gap-3 text-sm">
                        <div>
                          <p className="text-xs opacity-75">Vehicle Type</p>
                          <p className="font-semibold capitalize">{vehicleAnalysis.fuel_type}</p>
                        </div>
                        <div>
                          <p className="text-xs opacity-75">Range</p>
                          <p className="font-semibold">{vehicleAnalysis.vehicle_range_km} km</p>
                        </div>
                        <div>
                          <p className="text-xs opacity-75">Safe Range (80%)</p>
                          <p className="font-semibold">{vehicleAnalysis.effective_range_km} km</p>
                        </div>
                        <div>
                          <p className="text-xs opacity-75">Stops Needed</p>
                          <p className="font-semibold">{vehicleAnalysis.stops_required}</p>
                        </div>
                      </div>
                    </div>

                    {/* Warning if applicable */}
                    {vehicleAnalysis.warning && (
                      <div className="mt-3 p-2 bg-yellow-100 rounded border border-yellow-300 text-sm text-yellow-800">
                        ⚠️ {vehicleAnalysis.warning}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            )}

            {/* ========== MODE RECOMMENDATION ENGINE ========== */}
            {modeRecommendation && modeRecommendation.ok && (
              <div style={{ background: 'linear-gradient(135deg, rgba(99,102,241,0.06), rgba(20,184,166,0.06))', border: '1px solid var(--riq-border)', borderRadius: '0.75rem', padding: '1.25rem' }}>
                <div className="flex items-start gap-4">
                  <div className="text-3xl">🎯</div>
                  <div className="flex-1">
                    <h3 style={{ fontSize: '0.7rem', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.05em', color: '#6366f1', marginBottom: '0.25rem' }}>
                      Recommended Travel Mode
                    </h3>
                    <p style={{ fontSize: '1.5rem', fontWeight: 800, color: 'var(--riq-text)', textTransform: 'capitalize', marginBottom: '0.5rem' }}>
                      {modeRecommendation.recommended_mode}
                    </p>
                    <p className="text-sm text-teal-700 mb-3">
                      💡 {modeRecommendation.reason}
                    </p>

                    {/* Score breakdown - show alternatives only if scores are close (diff ≤1) */}
                    {modeRecommendation.scores && Object.keys(modeRecommendation.scores).length > 1 && (() => {
                      const scores = Object.values(modeRecommendation.scores);
                      const maxScore = Math.max(...scores);
                      const minScore = Math.min(...scores);
                      const showAlternatives = (maxScore - minScore) <= 1;

                      return showAlternatives ? (
                        <div className="mt-4 pt-4 border-t border-teal-200">
                          <p className="text-xs font-semibold text-teal-600 mb-2">CLOSE ALTERNATIVES</p>
                          <div className="grid grid-cols-4 gap-2 text-center">
                            {Object.entries(modeRecommendation.scores).map(([mode, score]) => (
                              <div key={mode} className={`rounded p-2 ${mode === modeRecommendation.recommended_mode ? 'bg-teal-200' : 'bg-white'}`}>
                                <p className="text-xs capitalize text-slate-600">{mode}</p>
                                <p className={`text-sm font-bold ${mode === modeRecommendation.recommended_mode
                                  ? 'text-teal-700'
                                  : 'text-slate-700'
                                  }`}>
                                  {score.toFixed(1)}
                                </p>
                              </div>
                            ))}
                          </div>
                        </div>
                      ) : null;
                    })()}
                  </div>
                </div>
              </div>
            )}


            {costs && (
              <div className="riq-panel p-5 transition-colors">
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--riq-text)', marginBottom: '0.75rem' }}>Cost Comparison</h3>

                <div className="space-y-3">
                  {Object.entries(costs)
                    .filter(([key]) => ['car', 'ev', 'bus', 'train', 'flight'].includes(key))
                    .sort(([, costA], [, costB]) => costA - costB)
                    .map(([mode, cost]) => {
                      let duration = routeData?.duration_min;
                      if (mode === 'bus' && busTrip) duration = busTrip.total_duration_min;
                      if (mode === 'train' && trainTrip) duration = trainTrip.total_duration_min;
                      if (mode === 'flight' && flightTrip) duration = flightTrip.total_duration_min;

                      // Determine highlight for this mode
                      let highlight = null;
                      if (highlights) {
                        if (mode === highlights.cheapest) highlight = 'cheapest';
                        else if (mode === highlights.fastest) highlight = 'fastest';
                        else if (mode === highlights.eco) highlight = 'eco';
                      }

                      return (
                        <CostCard
                          key={mode}
                          mode={mode}
                          cost={cost}
                          duration={duration}
                          selected={selectedMode === mode}
                          highlight={highlight}
                          showExplanation={aiInsightsEnabled}
                          onSelect={() => {
                            setSelectedMode(mode);
                            // Navigate to mode-specific page based on mode selection
                            const params = new URLSearchParams({
                              source,
                              destination,
                              distance: (routeData?.distance_km || 0).toString(),
                              cost: cost.toString(),
                              duration: (routeData?.duration_min || 0).toString(),
                            });

                            // Each mode routes to its own page
                            if (mode === 'car') {
                              navigate(`/journey/car?${params.toString()}`, {
                                state: {
                                  trip: routeData,
                                  markers,
                                  polyline: polylineData,
                                }
                              });
                            } else if (mode === 'ev') {
                              navigate(`/journey/ev?${params.toString()}`, {
                                state: {
                                  trip: routeData,
                                  markers,
                                  polyline: polylineData,
                                }
                              });
                            } else if (mode === 'bus') {
                              navigate(`/journey/bus?${params.toString()}`, {
                                state: {
                                  trip: routeData,
                                  markers,
                                  polyline: polylineData,
                                }
                              });
                            } else if (mode === 'train') {
                              navigate(`/journey/train?${params.toString()}`, {
                                state: {
                                  trip: routeData,
                                  markers,
                                  polyline: polylineData,
                                }
                              });
                            } else {
                              // flight or other modes
                              navigate(`/journey/${mode}?${params.toString()}`, {
                                state: {
                                  trip: routeData,
                                  markers,
                                  polyline: polylineData,
                                }
                              });
                            }
                          }}
                        />
                      );
                    })}
                </div>

                {/* LEVEL 2: Global price disclaimer */}
                <div className="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                  <p className="text-sm text-blue-800 dark:text-blue-200">
                    ℹ️ Prices shown are indicative ranges based on route characteristics and confidence level. Final prices may vary.
                  </p>
                </div>

                {/* LEVEL 3+: AI Travel Analysis (Bundled) - Only renders if enabled */}
                {selectedMode && highlights && aiInsightsEnabled && (() => {
                  let highlight = null;
                  if (selectedMode === highlights.cheapest) highlight = 'Cheapest';
                  else if (selectedMode === highlights.fastest) highlight = 'Fastest';
                  else if (selectedMode === highlights.eco) highlight = 'Eco-Friendly';

                  // Find duration
                  const selectedData = highlights.modesData.find(m => m.mode === selectedMode);
                  const duration = selectedData ? selectedData.duration : null;
                  const cost = costs[selectedMode];

                  return (
                    <AITravelInsight
                      source={source}
                      destination={destination}
                      mode={selectedMode}
                      cost_range={getFormattedCostRange(selectedMode, cost)}
                      duration={duration}
                      reason_tag={highlight}
                    />
                  );
                })()}

              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
