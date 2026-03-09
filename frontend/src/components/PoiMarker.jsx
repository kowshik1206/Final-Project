import React from 'react';
import PropTypes from 'prop-types';

export default function PoiMarker({
  name,
  category,
  rating,
  latitude,
  longitude,
  onClick,
  children, // optional extra content
}) {
  // Defensive fallbacks
  const displayName = name || 'Unknown';
  const displayCategory = category || 'POI';
  const safeLat = typeof latitude === 'number' ? latitude : null;
  const safeLng = typeof longitude === 'number' ? longitude : null;
  const safeRating = typeof rating === 'number' ? rating.toFixed(1) : '—';

  return (
    <div
      onClick={(e) => {
        e.stopPropagation();
        if (typeof onClick === 'function') onClick();
      }}
      className="bg-white shadow-md rounded-lg p-3 cursor-pointer hover:shadow-lg transition max-w-xs"
      role="button"
      tabIndex={0}
      onKeyDown={(e) => { if (e.key === 'Enter') onClick && onClick(); }}
    >
      <h4 className="font-semibold text-gray-800 mb-1 truncate">{displayName}</h4>
      <p className="text-xs text-gray-500 mb-2 capitalize">{displayCategory}</p>

      <div className="flex justify-between items-center mb-3">
        <span className="text-sm text-yellow-500">⭐ {safeRating}</span>
        <span className="text-xs text-gray-400">
          {safeLat !== null && safeLng !== null ? `${safeLat.toFixed(4)}, ${safeLng.toFixed(4)}` : 'Coordinates N/A'}
        </span>
      </div>

      {children}

      <button
        type="button"
        className="w-full text-sm bg-blue-600 text-white py-1 rounded hover:bg-blue-700 transition mt-2"
        onClick={(e) => {
          e.stopPropagation();
          if (typeof onClick === 'function') onClick();
        }}
      >
        View Details
      </button>
    </div>
  );
}

PoiMarker.propTypes = {
  name: PropTypes.string,
  category: PropTypes.string,
  rating: PropTypes.number,
  latitude: PropTypes.number,
  longitude: PropTypes.number,
  onClick: PropTypes.func,
  children: PropTypes.node,
};

PoiMarker.defaultProps = {
  name: 'Unknown',
  category: 'POI',
  rating: null,
  latitude: null,
  longitude: null,
  onClick: null,
  children: null,
};
