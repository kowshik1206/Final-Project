import React from 'react';
import PropTypes from 'prop-types';

/**
 * PHASE 5: BusStandInfoCard - Bus Terminal Information
 */

const typeBadges = {
  interstate: { bg: 'bg-red-100/80 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-300', label: '🚌 Interstate Terminal' },
  local: { bg: 'bg-blue-100/80 dark:bg-blue-900/30', text: 'text-blue-700 dark:text-blue-300', label: '🚍 Local Bus Stand' },
  private: { bg: 'bg-violet-100/80 dark:bg-violet-900/30', text: 'text-violet-700 dark:text-violet-300', label: '🚐 Private Terminal' },
};

const facilityIcons = {
  toilet: '🚻', food: '🍽️', parking: '🅿️', water: '💧', waiting: '🪑', wifi: '📶'
};

export default function BusStandInfoCard({ name, city, type, facilities = [] }) {
  const badgeStyle = typeBadges[type] || typeBadges.interstate;

  return (
    <div className="relative overflow-hidden rounded-2xl border border-amber-300/40 dark:border-amber-600/40 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-sm hover:shadow-xl transition-all duration-300 p-6">
      {/* Left accent bar */}
      <div className="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-orange-500 rounded-l-2xl" />

      {/* Header */}
      <div className="flex justify-between items-start mb-4 pl-2">
        <div>
          <h3 className="text-xl font-bold" style={{ color: 'var(--riq-text)' }}>{name}</h3>
          <p className="text-sm mt-1" style={{ color: 'var(--riq-text-muted)' }}>{city}</p>
        </div>
        <div className={`px-3 py-1 rounded-full text-xs font-bold ${badgeStyle.bg} ${badgeStyle.text}`}>
          {badgeStyle.label}
        </div>
      </div>

      {/* Facilities */}
      {facilities.length > 0 && (
        <div className="pt-4 border-t border-slate-200/60 dark:border-slate-700/50 pl-2">
          <div className="text-[10px] uppercase tracking-wider font-bold mb-2.5" style={{ color: 'var(--riq-text-faint)' }}>Available Facilities</div>
          <div className="flex flex-wrap gap-2">
            {facilities.map((facility, index) => (
              <span
                key={index}
                className="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100/80 dark:bg-slate-700/40 border border-slate-200/60 dark:border-slate-600/40 rounded-full text-xs font-medium"
                style={{ color: 'var(--riq-text)' }}
              >
                <span>{facilityIcons[facility] || '•'}</span>
                <span className="capitalize">{facility}</span>
              </span>
            ))}
          </div>
        </div>
      )}

      {/* Description */}
      <div className="pt-4 border-t border-slate-200/60 dark:border-slate-700/50 mt-4 pl-2">
        <p className="text-sm" style={{ color: 'var(--riq-text-muted)' }}>
          {type === 'interstate' && '🚌 Major interstate bus terminal with long-distance services.'}
          {type === 'local' && '🚍 Local bus stand for city and regional services.'}
          {type === 'private' && '🚐 Private bus terminal operated by travel companies.'}
        </p>
      </div>
    </div>
  );
}

BusStandInfoCard.propTypes = {
  name: PropTypes.string.isRequired,
  city: PropTypes.string.isRequired,
  type: PropTypes.oneOf(['interstate', 'local', 'private']),
  facilities: PropTypes.arrayOf(PropTypes.string)
};

BusStandInfoCard.defaultProps = {
  type: 'interstate',
  facilities: []
};
