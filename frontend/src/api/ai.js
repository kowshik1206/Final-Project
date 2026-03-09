// src/api/ai.js
import axios from './axios'; // your existing axios instance

export default {
  /**
   * Call backend AI ranking for POIs.
   * params:
   *   candidates: Array of POI objects (id,name,category,lat,lng,rating,distance_to_route_m)
   *   route: { distance_km, duration_min }
   *   vehicle: selectedVehicle object (fuel_type, etc)
   *   prefs: user prefs (comfortMode, eldersCount, templePriority)
   */
  async scorePois({ candidates = [], route = {}, vehicle = {}, prefs = {} } = {}) {
    const payload = { candidates, route, vehicle, prefs };
    const res = await axios.post('/api/ai/score-pois.php', payload);
    return res.data;
  }
};
