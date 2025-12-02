import React from 'react';

const poiTypes = [
  { type: 'poi-temple', label: 'Temple', emoji: '🛕' },
  { type: 'poi-fuel', label: 'Fuel', emoji: '⛽' },
  { type: 'poi-charger', label: 'Charger', emoji: '🔌' },
  { type: 'poi-toll', label: 'Toll', emoji: '🅿️' },
  { type: 'poi-restaurant', label: 'Restaurant', emoji: '🍽️' },
  { type: 'poi-hospital', label: 'Hospital', emoji: '🏥' },
];

export default function PoiLegend() {
  return (
    <div className="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
      <h3 className="text-sm font-semibold text-slate-800 mb-3">Points of Interest</h3>
      <div className="grid grid-cols-2 gap-3">
        {poiTypes.map((poi) => (
          <div key={poi.type} className="flex items-center space-x-2 text-sm">
            <span className="text-lg">{poi.emoji}</span>
            <span className="text-slate-600">{poi.label}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
