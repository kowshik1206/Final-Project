// src/utils/geometry.js
// Simple point <-> polyline distance without external deps.
// Input:
//   point: { lat: number, lng: number }
//   polyline: [{lat,lng}, ...]
// Returns: distance in meters from point to nearest segment/vertex.

const R = 6371000; // earth radius meters

function toRad(deg) {
  return (deg * Math.PI) / 180;
}

function haversineDistance(a, b) {
  // a, b: { lat, lng }
  const dLat = toRad(b.lat - a.lat);
  const dLon = toRad(b.lng - a.lng);
  const lat1 = toRad(a.lat);
  const lat2 = toRad(b.lat);

  const sinDLat = Math.sin(dLat / 2);
  const sinDLon = Math.sin(dLon / 2);

  const aa = sinDLat * sinDLat + sinDLon * sinDLon * Math.cos(lat1) * Math.cos(lat2);
  const c = 2 * Math.atan2(Math.sqrt(aa), Math.sqrt(1 - aa));
  return R * c;
}

// project point p onto segment ab (on sphere we approximate with ECEF projection via lat/lng -> 2D by mercator-like small area).
// For reasonable route distances this planar approximation is fine for nearest-segment checks.
function pointToSegmentDistanceMeters(p, a, b) {
  // Convert to simple Cartesian in meters using equirectangular approx relative to p (small area)
  const latRef = toRad(p.lat);
  const x = (toRad(p.lng) - toRad(a.lng)) * Math.cos(latRef) * R;
  const y = (toRad(p.lat) - toRad(a.lat)) * R;
  const ax = 0;
  const ay = 0;
  const bx = (toRad(b.lng) - toRad(a.lng)) * Math.cos(latRef) * R;
  const by = (toRad(b.lat) - toRad(a.lat)) * R;

  const px = x - ax;
  const py = y - ay;
  const vx = bx - ax;
  const vy = by - ay;
  const vLen2 = vx * vx + vy * vy;

  if (vLen2 === 0) {
    // a and b are the same point
    return Math.sqrt(px * px + py * py);
  }

  // projection scalar t
  const t = Math.max(0, Math.min(1, (px * vx + py * vy) / vLen2));
  const projx = ax + t * vx;
  const projy = ay + t * vy;
  const dx = px - (projx - ax);
  const dy = py - (projy - ay);
  const dist = Math.sqrt(dx * dx + dy * dy);
  return Math.abs(dist);
}

export function pointToPolylineDistanceMeters(point, polyline = []) {
  if (!polyline || polyline.length === 0) {
    return Infinity;
  }
  // if polyline points are objects {lat,lng} or arrays [lat,lng] handle both
  const norm = polyline.map((p) => {
    if (Array.isArray(p)) return { lat: Number(p[0]), lng: Number(p[1]) };
    return { lat: Number(p.lat), lng: Number(p.lng) };
  });

  // compute min distance over segments
  let min = Infinity;
  for (let i = 0; i < norm.length - 1; i++) {
    const a = norm[i];
    const b = norm[i + 1];
    const d = pointToSegmentDistanceMeters(point, a, b);
    if (d < min) min = d;
  }

  // also check vertices
  for (let i = 0; i < norm.length; i++) {
    const d = haversineDistance(point, norm[i]);
    if (d < min) min = d;
  }

  return min === Infinity ? null : min;
}
