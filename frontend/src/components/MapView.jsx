import React, { useMemo } from 'react';
import {
  MapContainer,
  TileLayer,
  Marker,
  Popup,
  Polyline,
  CircleMarker,
} from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Custom icons
const createIcon = (type) => {
  const colors = {
    source: '#4F46E5',
    destination: '#DC2626',
    'poi-temple': '#F59E0B',
    'poi-fuel': '#6B7280',
    'poi-charger': '#10B981',
    'poi-toll': '#8B5CF6',
    'poi-restaurant': '#EC4899',
    'poi-hospital': '#EF4444',
  };

  const color = colors[type] || '#4F46E5';

  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div style="
        width: 32px;
        height: 32px;
        background-color: ${color};
        border: 3px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        color: white;
        font-size: 16px;
        font-weight: bold;
      ">
        ${getIconEmoji(type)}
      </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 16],
    popupAnchor: [0, -16],
  });
};

const getIconEmoji = (type) => {
  const emojis = {
    source: '📍',
    destination: '🎯',
    'poi-temple': '🛕',
    'poi-fuel': '⛽',
    'poi-charger': '🔌',
    'poi-toll': '🅿️',
    'poi-restaurant': '🍽️',
    'poi-hospital': '🏥',
  };
  return emojis[type] || '📍';
};

export default function MapView({
  center = [28.6139, 77.209],
  zoom = 12,
  markers = [],
  polyline = [],
}) {
  const defaultCenter = Array.isArray(center)
    ? center
    : [center.lat || 28.6139, center.lng || 77.209];

  const polylineCoords = useMemo(() => {
    if (Array.isArray(polyline) && polyline.length > 0) {
      return polyline.map((point) => [point.lat || point[0], point.lng || point[1]]);
    }
    return [];
  }, [polyline]);

  return (
    <MapContainer center={defaultCenter} zoom={zoom} className="w-full h-96 rounded-lg">
      <TileLayer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      />

      {/* Polyline */}
      {polylineCoords.length > 0 && (
        <Polyline positions={polylineCoords} color="#4F46E5" weight={4} opacity={0.8} />
      )}

      {/* Markers */}
      {markers.map((marker, idx) => (
        <Marker
          key={idx}
          position={[marker.lat || marker[0], marker.lng || marker[1]]}
          icon={createIcon(marker.type || 'source')}
        >
          <Popup>{marker.label || `Point ${idx + 1}`}</Popup>
        </Marker>
      ))}
    </MapContainer>
  );
}

