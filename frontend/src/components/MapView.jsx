import React, { useMemo, useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import {
  MapContainer,
  TileLayer,
  Marker,
  Popup,
  Polyline,
  useMap,
} from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// ============================================================
// PHASE 4: MAPVIEW - PURE SEGMENT RENDERER
// 
// CRITICAL RULE: Accepts ONLY routeSegments and markers.
// Does NOT build routes, does NOT infer styles, does NOT handle modes.
// Segment type determines line style automatically (pure mapping).
// ============================================================

// Icon and color helpers (PURE MAPPING, NO LOGIC)
const getIconEmoji = (type) => {
  const emojis = {
    source: '📍',
    destination: '🎯',
    station: '🚆',
    airport: '✈️',
  };
  return emojis[type] || '📍';
};

const createIcon = (type) => {
  const colors = {
    source: '#4F46E5',
    destination: '#DC2626',
    station: '#2563eb',
    airport: '#7c3aed',
  };

  const color = colors[type] || '#4F46E5';
  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div style="
        width: 34px;
        height: 34px;
        background-color: ${color};
        border: 2px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.18);
        color: white;
        font-size: 14px;
        line-height: 1;
      ">
        ${getIconEmoji(type)}
      </div>
    `,
    iconSize: [34, 34],
    iconAnchor: [17, 17],
    popupAnchor: [0, -18],
  });
};

// Line style per segment type (PURE MAPPING, NO LOGIC)
const SEGMENT_LINE_STYLES = {
  road: {
    color: '#16a34a',
    weight: 5,
    opacity: 0.85,
    dashArray: null,
  },
  rail: {
    color: '#2563eb',
    weight: 4,
    opacity: 0.85,
    dashArray: '8, 8',
  },
  flight: {
    color: '#7c3aed',
    weight: 3,
    opacity: 0.85,
    dashArray: '12, 12',
  },
};

// Safe coordinate parsing
const toLatLngPair = (p) => {
  if (!p) return null;
  if (Array.isArray(p)) return [Number(p[0]), Number(p[1])];
  if (typeof p === 'object' && 'lat' in p && 'lng' in p) {
    return [Number(p.lat), Number(p.lng)];
  }
  return null;
};

// Fit bounds helper
function FitBounds({ positions, padding = [40, 40] }) {
  const map = useMap();

  useEffect(() => {
    if (!map || !positions || positions.length === 0) return;
    try {
      const latlngs = positions.map((p) => L.latLng(p[0], p[1]));
      const bounds = L.latLngBounds(latlngs);
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding });
      }
    } catch (err) {
      // Silent fail
    }
  }, [map, positions, padding]);

  return null;
}

// ============================================================
// MAPVIEW COMPONENT
// ============================================================
export default function MapView({
  center = [28.6139, 77.209],
  zoom = 10,
  routeSegments = [], // array of segments {type, polyline, ...}
  polyline = [],      // Alternative: simple array of coordinates
  markers = [],      // array of markers {lat, lng, type, content, ...}
  children,           // Custom Leaflet elements
  fitBoundsPadding = [40, 40],
}) {
  const mapRef = useRef(null);

  // Default center
  const defaultCenter = useMemo(() => {
    if (Array.isArray(center) && center.length >= 2) return [Number(center[0]), Number(center[1])];
    if (center && typeof center === 'object') {
      return [Number(center.lat || 28.6139), Number(center.lng || 77.209)];
    }
    return [28.6139, 77.209];
  }, [center]);

  // Process segments: handle both routeSegments and a single polyline
  const processedSegments = useMemo(() => {
    const segments = [];

    // 1. Add routeSegments if provided
    if (Array.isArray(routeSegments) && routeSegments.length > 0) {
      routeSegments.forEach((segment) => {
        const coords = (segment.polyline || [])
          .map((point) => toLatLngPair(point))
          .filter(Boolean);

        if (coords.length > 0) {
          segments.push({
            ...segment,
            coords,
            lineStyle: SEGMENT_LINE_STYLES[segment.type] || SEGMENT_LINE_STYLES.road,
          });
        }
      });
    }

    // 2. Add single polyline if provided (legacy support)
    if (Array.isArray(polyline) && polyline.length > 0) {
      const coords = polyline
        .map((point) => toLatLngPair(point))
        .filter(Boolean);

      if (coords.length > 0) {
        segments.push({
          type: 'road',
          coords,
          lineStyle: SEGMENT_LINE_STYLES.road,
        });
      }
    }

    return segments;
  }, [routeSegments, polyline]);

  // Collect all coordinates for bounds
  const boundsPositions = useMemo(() => {
    let allCoords = [];

    processedSegments.forEach((seg) => {
      allCoords = allCoords.concat(seg.coords);
    });

    if (markers && markers.length > 0) {
      markers.forEach((m) => {
        const pair = toLatLngPair(m);
        if (pair) allCoords.push(pair);
      });
    }

    return allCoords;
  }, [processedSegments, markers]);

  return (
    <MapContainer
      center={defaultCenter}
      zoom={zoom}
      className="w-full h-96 rounded-lg"
      scrollWheelZoom={true}
      style={{ minHeight: '320px' }}
      ref={mapRef}
    >
      <TileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      />

      {/* Auto-fit bounds */}
      {boundsPositions.length > 0 && <FitBounds positions={boundsPositions} padding={fitBoundsPadding} />}

      {/* Render all segments (from routeSegments + polyline) */}
      {processedSegments.map((segment, index) => (
        <Polyline
          key={`segment-${index}`}
          positions={segment.coords}
          pathOptions={{
            color: segment.lineStyle.color,
            weight: segment.lineStyle.weight,
            opacity: segment.lineStyle.opacity,
            dashArray: segment.lineStyle.dashArray,
          }}
        />
      ))}

      {/* Render markers */}
      {markers.map((marker, idx) => {
        const coords = toLatLngPair(marker);
        if (!coords) return null;

        return (
          <Marker
            key={`marker-${idx}`}
            position={coords}
            icon={createIcon(marker.type || 'source')}
          >
            {(marker.content || marker.label) && (
              <Popup>{marker.content || marker.label}</Popup>
            )}
          </Marker>
        );
      })}

      {/* Children for extensibility */}
      {children}
    </MapContainer>
  );
}

MapView.propTypes = {
  center: PropTypes.oneOfType([
    PropTypes.array,
    PropTypes.shape({ lat: PropTypes.number, lng: PropTypes.number }),
  ]),
  zoom: PropTypes.number,
  routeSegments: PropTypes.arrayOf(
    PropTypes.shape({
      type: PropTypes.oneOf(['road', 'rail', 'flight']),
      polyline: PropTypes.array,
      distance_km: PropTypes.number,
      duration_min: PropTypes.number,
    })
  ),
  polyline: PropTypes.arrayOf(PropTypes.oneOfType([PropTypes.object, PropTypes.array])),
  markers: PropTypes.arrayOf(
    PropTypes.oneOfType([
      PropTypes.array,
      PropTypes.shape({
        lat: PropTypes.number,
        lng: PropTypes.number,
        type: PropTypes.string,
        content: PropTypes.string,
        label: PropTypes.string,
      })
    ])
  ),
  children: PropTypes.node,
  fitBoundsPadding: PropTypes.oneOfType([PropTypes.array, PropTypes.number]),
};

