/**
 * Map vehicle type to POI categories that make sense for that vehicle
 * @param {object} vehicle - vehicle object with fuel property ('petrol' | 'diesel' | 'cng' | 'ev')
 * @returns {array} array of POI category strings
 */
export const vehicleToPoiCategories = (vehicle) => {
  const fuel = vehicle?.fuel ?? vehicle?.fuel_type ?? null;

  // Base categories shown for all trips
  const base = ['toll', 'restaurant', 'hospital'];

  if (!fuel) return base.concat(['fuel', 'charger']); // default: show both

  if (fuel === 'ev') {
    // EV: chargers + tolls + amenities
    return ['charger', 'toll', 'restaurant', 'hospital'];
  }

  // For combustion (petrol/diesel), show fuel and tolls
  if (fuel === 'petrol' || fuel === 'diesel') {
    return ['fuel', 'toll', 'restaurant', 'hospital'];
  }

  // CNG: show cng fuel stations if you have category 'cng' else 'fuel'
  if (fuel === 'cng') {
    // prefer 'cng' category if your POI DB has it, otherwise fallback to 'fuel'
    return ['cng', 'fuel', 'toll', 'restaurant', 'hospital'];
  }

  return base.concat(['fuel', 'charger']);
};

/**
 * Get default POI toggle state for a given vehicle
 * All categories in the vehicle's set are ON; others OFF
 * @param {object} vehicle - vehicle object
 * @returns {object} toggle state object { temple: bool, fuel: bool, ... }
 */
export const getDefaultPoiToggles = (vehicle) => {
  const allCategories = ['temple', 'fuel', 'charger', 'toll', 'restaurant', 'hospital', 'cng'];
  const vehicleCategories = vehicleToPoiCategories(vehicle);

  const toggles = {};
  allCategories.forEach(cat => {
    toggles[cat] = vehicleCategories.includes(cat);
  });
  return toggles;
};

/**
 * Calculate recommended stop interval (km between stops) based on comfort mode and elders
 * @param {object} options
 * @param {string} options.baseMode - 'comfort' or 'mileage' (default: 'comfort')
 * @param {number} options.eldersCount - number of elderly passengers (default: 0)
 * @returns {number} recommended stop interval in km
 */
export const calcStopInterval = ({ baseMode = 'comfort', eldersCount = 0 } = {}) => {
  // Base intervals: comfort mode = shorter, less exhausting; mileage mode = longer, more efficient
  const baseInterval = baseMode === 'comfort' ? 250 : 300; // km

  // Elder adjustment: each elder reduces interval by 30 km (more frequent stops)
  let interval = baseInterval - (eldersCount * 30);

  // Safety clamp: minimum 100 km between stops
  if (interval < 100) interval = 100;

  return Math.round(interval);
};

// Removed: calcStopsNeeded is now in utils/stops.js as computeStops()
// Use computeStops({ distanceKm, effectiveRangeKm, eldersCount, childrenCount }) instead
