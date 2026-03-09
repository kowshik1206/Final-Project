// src/utils/vehicleRange.js
// Compute effective range and stops needed for vehicle refueling/charging planning

/**
 * Get the authoritative efficiency value for a vehicle.
 * Priority: Custom mileage (trip-specific) > DB efficiency_km_per_unit (baseline)
 * 
 * @param {Object} vehicle - Vehicle object
 * @returns {number|null} Effective efficiency in km/unit (km/L, km/kg, etc) or null if not available
 */
export function getEffectiveEfficiency(vehicle) {
  if (!vehicle) return null;

  // Priority 1: Custom mileage (trip-specific override)
  const customMileage = Number(vehicle.mileage ?? 0);
  if (customMileage > 0) {
    return customMileage;
  }

  // Priority 2: DB baseline efficiency
  const dbEfficiency = Number(vehicle.efficiency_km_per_unit ?? 0);
  if (dbEfficiency > 0) {
    return dbEfficiency;
  }

  // No valid efficiency found
  return null;
}

export function computeEffectiveRangeKm(vehicle, safetyFactor = 0.8) {
  if (!vehicle || !vehicle.fuel_type) return null;
  const t = vehicle.fuel_type.toLowerCase();
  
  if (t === 'ev') {
    const r = Number(vehicle.ev_range ?? vehicle.range ?? 0);
    return r > 0 ? Math.round(r * safetyFactor) : null;
  }
  
  // petrol/diesel/cng - use authoritative efficiency
  const efficiency = getEffectiveEfficiency(vehicle);
  const tank = Number(vehicle.tank_size ?? 0);
  const range = (efficiency > 0 && tank > 0) ? efficiency * tank : null;
  return range ? Math.round(range * safetyFactor) : null;
}

// Removed: computeStopsNeeded is now in utils/stops.js
