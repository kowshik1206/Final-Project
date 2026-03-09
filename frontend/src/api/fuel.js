import axiosClient from './axiosClient';

export const fuelCalc = (payload) => axiosClient.post('/fuel-calc', payload);

/**
 * Normalize backend vehicle response to frontend expected shape.
 * Backend: { id, name, fuel, efficiency_km_per_unit, unit }
 * Frontend: { id, name, fuel_type, mileage, ev_range, tank_size }
 */
function normalizeVehicle(raw) {
  if (!raw) return null;
  
  const v = {
    id: raw.id ?? raw.vehicle_id ?? null,
    name: raw.name ?? raw.title ?? 'Vehicle',
    fuel_type: raw.fuel_type ?? raw.fuel ?? null,
    mileage: raw.mileage ?? raw.efficiency_km_per_unit ?? null,
    tank_size: raw.tank_size ?? raw.tank_capacity ?? null,
    ev_range: raw.ev_range ?? raw.ev_range_km ?? null
  };

  // Coerce numbers
  if (v.mileage !== null) v.mileage = Number(v.mileage);
  if (v.tank_size !== null) v.tank_size = Number(v.tank_size);
  if (v.ev_range !== null) v.ev_range = Number(v.ev_range);

  // Add reasonable default tank sizes if missing
  if (!v.tank_size || v.tank_size === 0) {
    const fuel = (v.fuel_type || '').toLowerCase();
    if (fuel === 'ev') {
      // No tank for EV, use ev_range instead
    } else if (fuel === 'cng') {
      v.tank_size = 40; // CNG vehicles typically 35-45 kg
    } else {
      v.tank_size = 50; // Petrol/Diesel vehicles typically 40-60 L
    }
  }

  return v;
}

const fuelAPI = {
  // Calculate fuel consumption and costs for a trip
  calculateFuel: (data) => 
    axiosClient.post('/fuel-calc', data),
  
  // Get all vehicle profiles with normalization
  getProfiles: async (profileId = null) => {
    const url = profileId ? `/vehicle-profiles?id=${profileId}` : '/vehicle-profiles';
    const res = await axiosClient.get(url, { timeout: 60000 }); // 60 second timeout
    
    // Backend may return { ok: true, data: [...] } or { ok: true, vehicles: [...] }
    const payload = res.data ?? {};
    const list = (payload.vehicles ?? payload.data ?? []) || [];
    const normalized = Array.isArray(list) ? list.map(normalizeVehicle).filter(Boolean) : [];
    
    return { ok: true, vehicles: normalized, raw: payload };
  },
  
  // Get a specific vehicle profile
  getProfile: (profileId) => 
    axiosClient.get(`/vehicle-profiles?id=${profileId}`),
  
  // Create a new vehicle profile (admin)
  createProfile: (profileData) => 
    axiosClient.post('/vehicle-profiles', profileData),
  
  // Update a vehicle profile (admin)
  updateProfile: (profileId, profileData) => 
    axiosClient.put(`/vehicle-profiles?id=${profileId}`, profileData),
  
  // Delete a vehicle profile (admin)
  deleteProfile: (profileId) => 
    axiosClient.delete(`/vehicle-profiles?id=${profileId}`)
};

export default fuelAPI;
