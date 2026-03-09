import React from 'react';
import PropTypes from 'prop-types';

const DEFAULT_POI_TYPES = [
  { key: 'temple', label: 'Temple', emoji: '🛕' },
  { key: 'fuel', label: 'Fuel', emoji: '⛽' },
  { key: 'charger', label: 'Charger', emoji: '🔌' },
  { key: 'toll', label: 'Toll', emoji: '🅿️' },
  { key: 'restaurant', label: 'Restaurant', emoji: '🍽️' },
  { key: 'hospital', label: 'Hospital', emoji: '🏥' },
  { key: 'cng', label: 'CNG', emoji: '⛽' },
];

export default function PoiLegend({
  types = DEFAULT_POI_TYPES,
  onToggle = null,
  toggleState = null,
}) {
  const mergedTypes = types && types.length ? types : DEFAULT_POI_TYPES;

  // Use toggleState from parent if provided, else default all to true
  const active = toggleState || {};

  function toggle(key) {
    const newState = !active[key];
    if (typeof onToggle === 'function') onToggle(key, newState);
  }

  return (
    <div className="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
      <h3 className="text-sm font-semibold text-slate-800 mb-3">Points of Interest</h3>
      <div className="grid grid-cols-2 gap-3">
        {mergedTypes.map((poi) => (
          <button
            key={poi.key}
            type="button"
            onClick={() => toggle(poi.key)}
            className={`flex items-center space-x-2 text-sm p-2 rounded transition ${active[poi.key] ? 'bg-blue-50 border border-blue-200' : 'bg-slate-50 border border-slate-200'} hover:bg-slate-100`}
            aria-pressed={!!active[poi.key]}
          >
            <span className="text-lg" aria-hidden>{poi.emoji}</span>
            <span className={`text-sm font-medium ${active[poi.key] ? 'text-blue-700' : 'text-slate-600'}`}>{poi.label}</span>
            <span className="ml-auto text-xs font-semibold">
              {active[poi.key] ? '✓' : '✕'}
            </span>
          </button>
        ))}
      </div>
    </div>
  );
}

PoiLegend.propTypes = {
  types: PropTypes.arrayOf(PropTypes.shape({
    key: PropTypes.string.isRequired,
    label: PropTypes.string.isRequired,
    emoji: PropTypes.string,
  })),
  onToggle: PropTypes.func, // (key, isActive) => void
  toggleState: PropTypes.objectOf(PropTypes.bool),
};
