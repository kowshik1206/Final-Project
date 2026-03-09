import axiosClient from './axiosClient';

/**
 * POI API: Route-Authoritative Backend
 * 
 * This API enforces the POI backend contract:
 * - Input: POST with polyline (route-authoritative geometry)
 * - Vehicle context for feasibility checks
 * - Deterministic filtering + explicit failure modes
 * - Response includes feasibility analysis + ranked POIs
 */

const poiAPI = {
  async getPoisForRoute({ 
    polyline,           // [{lat, lng}, ...] - route geometry
    vehicle = null,     // {fuel_type, effective_range_km} - vehicle context
    categories = [],    // ["charger", "fuel", ...] - explicit intent
    radius_km = 5       // bounded search radius
  }) {
    try {
      // Validate input contract
      if (!Array.isArray(polyline) || polyline.length < 2) {
        console.error('❌ Invalid polyline:', polyline);
        return {
          ok: false,
          error: 'Invalid polyline',
          pois: [],
          feasibility: null
        };
      }

      if (!Array.isArray(categories) || categories.length === 0) {
        console.error('❌ No categories specified');
        return {
          ok: false,
          error: 'No categories specified',
          pois: [],
          feasibility: null
        };
      }

      console.group('📍 POI Backend Request');
      console.log('🛣️ Polyline points:', polyline.length);
      console.log('🚗 Vehicle context:', vehicle);
      console.log('📂 Categories:', categories);
      console.log('📏 Search radius:', radius_km, 'km');

      // Build request payload according to input contract
      const payload = {
        polyline,         // Pass as-is, backend will validate
        vehicle,          // Include vehicle for feasibility checks
        categories,       // Explicit categories only
        radius_km: Math.min(Math.max(radius_km, 1), 20) // Enforce bounds
      };

      // POST to route-authoritative endpoint
      const res = await axiosClient.post('/pois-for-route', payload);
      const data = res.data ?? {};

      console.log('✅ Backend response:', {
        ok: data.ok,
        totalPois: data.summary?.total_pois,
        returnedPois: data.pois?.length,
        feasibility: data.feasibility
      });
      console.groupEnd();

      // Response validation
      if (!data.ok) {
        return {
          ok: false,
          error: data.error,
          reason: data.reason,
          suggestion: data.suggestion,
          pois: [],
          feasibility: data.feasibility || null
        };
      }

      return {
        ok: true,
        pois: (data.pois || []).map(p => ({
          id: p.id,
          name: p.name,
          category: p.category,
          lat: p.lat,
          lng: p.lng,
          distance_to_route_m: p.distance_to_route_m,
          score: p.score
        })),
        summary: data.summary || {},
        feasibility: data.feasibility || {},
        debug: {
          polylineLength: polyline.length,
          categoriesRequested: categories,
          radiusKm: radius_km
        }
      };

    } catch (err) {
      console.error('🔴 POI API Error:', err);
      return {
        ok: false,
        error: err.response?.data?.error || err.message,
        pois: [],
        feasibility: null
      };
    }
  }
};

export default poiAPI;

