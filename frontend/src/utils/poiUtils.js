// src/utils/poiUtils.js
import * as turf from '@turf/turf';

/**
 * routeCoords: [[lon, lat], [lon, lat], ...] or [[lat, lon], ...] depending on your format.
 * Expected: GeoJSON order [lon, lat]
 */
export function routeToBBox(routeCoords, bufferMeters = 5000) {
  if (!routeCoords || routeCoords.length === 0) return null;
  // Make LineString
  const line = turf.lineString(routeCoords);
  const buffered = turf.buffer(line, bufferMeters, { units: 'meters' });
  const bbox = turf.bbox(buffered); // [minX, minY, maxX, maxY] => [west, south, east, north]
  // Convert to Overpass bbox order [south, west, north, east]
  const [west, south, east, north] = bbox;
  return [south, west, north, east];
}

/**
 * Distance from POI to route (meters)
 * point: [lon, lat]
 * routeLine: GeoJSON LineString (turf-friendly)
 */
export function distanceFromPointToRouteMeters(point, routeLine) {
  const pt = turf.point(point);
  const dist = turf.pointToLineDistance(pt, routeLine, { units: 'meters' });
  return dist;
}
