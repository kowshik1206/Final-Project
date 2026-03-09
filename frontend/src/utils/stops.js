/**
 * CANONICAL STOPS CALCULATION
 * Single source of truth for all stop calculations.
 * No other stop functions should exist in the codebase.
 */

/**
 * Determine stop interval based on passenger demographics
 * @param {number} eldersCount - number of elderly passengers
 * @param {number} childrenCount - number of children
 * @returns {number} stop interval in km
 */
export function getStopIntervalKm(eldersCount = 0, childrenCount = 0) {
  const hasElders = eldersCount > 0;
  const hasChildren = childrenCount > 0;

  // Family trip with elders or children: more frequent stops (120km)
  if (hasElders || hasChildren) {
    return 120;
  }

  // Solo/adult trip: less frequent stops (200km)
  return 200;
}

/**
 * Compute the number of stops needed for a trip.
 * Uses deterministic rule: stopsNeeded = max(fuelStops, comfortStops)
 * 
 * fuelStops = ceil(distance / effectiveRange) - 1
 * comfortStops = ceil(distance / stopInterval) - 1
 * 
 * @param {number} distanceKm - total trip distance in km
 * @param {number} effectiveRangeKm - vehicle's effective range in km
 * @param {number} eldersCount - number of elderly passengers (default 0)
 * @param {number} childrenCount - number of children (default 0)
 * @returns {object} { count: number }
 */
export function computeStops(distanceKm, effectiveRangeKm, eldersCount = 0, childrenCount = 0) {
  // Validate inputs
  if (!distanceKm || distanceKm <= 0 || !effectiveRangeKm || effectiveRangeKm <= 0) {
    return { count: 0 };
  }

  // Compute fuel stops (hard constraint)
  const fuelStops = Math.max(0, Math.ceil(distanceKm / effectiveRangeKm) - 1);

  // Compute comfort stops (soft constraint)
  const comfortInterval = getStopIntervalKm(eldersCount, childrenCount);
  const comfortStops = Math.max(0, Math.ceil(distanceKm / comfortInterval) - 1);

  // Take maximum of both constraints
  const count = Math.max(fuelStops, comfortStops);

  console.log('[computeStops] distance:', distanceKm, 'range:', effectiveRangeKm, 'fuelStops:', fuelStops, 'comfortStops:', comfortStops, 'final:', count);

  return { count };
}
