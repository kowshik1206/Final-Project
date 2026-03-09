// POI scoring logic for prioritizing stops based on vehicle, distance, quality, and user preferences

function normalizeRating(r) {
  if (r == null) return 0.5;
  return Math.max(0, Math.min(1, Number(r) / 5));
}

function distanceScoreMeters(d, max = 30000) {
  if (d == null) return 0;
  const v = 1 - Math.min(d, max) / max;
  return Math.max(0, Math.min(1, v));
}

export function scorePoi(poi, { vehicle, userPrefs = {}, maxDistance = 30000 } = {}) {
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
  const qScore = normalizeRating(poi.rating);
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
