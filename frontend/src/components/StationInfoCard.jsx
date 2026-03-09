import React from 'react';
import PropTypes from 'prop-types';

/**
 * PHASE 5: StationInfoCard - Train Domain Model
 */

const importanceBadges = {
  major: { bg: 'bg-amber-100/80 dark:bg-amber-900/30', text: 'text-amber-800 dark:text-amber-300', label: '⭐ Major Terminal' },
  junction: { bg: 'bg-blue-100/80 dark:bg-blue-900/30', text: 'text-blue-800 dark:text-blue-300', label: '🔀 Junction' },
  secondary: { bg: 'bg-slate-100/80 dark:bg-slate-700/40', text: 'text-slate-700 dark:text-slate-300', label: '🚂 Station' },
};

export default function StationInfoCard({ name, code, city, importance, distance_km }) {
  const badgeStyle = importanceBadges[importance] || importanceBadges.secondary;

  return (
    <div className="relative overflow-hidden rounded-2xl border border-blue-300/40 dark:border-blue-600/40 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-sm hover:shadow-xl transition-all duration-300 p-6">
      {/* Left accent bar */}
      <div className="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-400 to-indigo-500 rounded-l-2xl" />

      {/* Header */}
      <div className="flex justify-between items-start mb-4 pl-2">
        <div>
          <h3 className="text-xl font-bold" style={{ color: 'var(--riq-text)' }}>{name}</h3>
          <p className="text-sm mt-1" style={{ color: 'var(--riq-text-muted)' }}>
            {code && <span className="font-mono bg-slate-100 dark:bg-slate-700/60 px-2 py-0.5 rounded-md text-xs font-bold" style={{ color: 'var(--riq-text)' }}>{code}</span>}
          </p>
        </div>
        <div className={`px-3 py-1 rounded-full text-xs font-bold ${badgeStyle.bg} ${badgeStyle.text}`}>
          {badgeStyle.label}
        </div>
      </div>

      {/* Details Grid */}
      <div className="grid grid-cols-2 gap-4 mb-4 pl-2">
        <div className="p-3 rounded-xl bg-slate-50/60 dark:bg-slate-700/30 border border-slate-100/60 dark:border-slate-700/40">
          <div className="text-[10px] uppercase tracking-wider font-bold mb-1" style={{ color: 'var(--riq-text-faint)' }}>City</div>
          <div className="font-semibold text-sm" style={{ color: 'var(--riq-text)' }}>{city}</div>
        </div>
        <div className="p-3 rounded-xl bg-slate-50/60 dark:bg-slate-700/30 border border-slate-100/60 dark:border-slate-700/40">
          <div className="text-[10px] uppercase tracking-wider font-bold mb-1" style={{ color: 'var(--riq-text-faint)' }}>Segment Distance</div>
          <div className="font-semibold text-sm" style={{ color: 'var(--riq-text)' }}>{distance_km.toFixed(1)} km</div>
        </div>
      </div>

      {/* Description */}
      <div className="pt-4 border-t border-slate-200/60 dark:border-slate-700/50 pl-2">
        <p className="text-sm" style={{ color: 'var(--riq-text-muted)' }}>
          {importance === 'major' && '🏛️ Major terminal with multiple platforms and comprehensive facilities.'}
          {importance === 'junction' && '🔄 Junction station connecting multiple rail lines.'}
          {importance === 'secondary' && '🚂 Local railway station.'}
        </p>
      </div>
    </div>
  );
}

StationInfoCard.propTypes = {
  name: PropTypes.string.isRequired,
  code: PropTypes.string.isRequired,
  city: PropTypes.string.isRequired,
  importance: PropTypes.oneOf(['major', 'junction', 'secondary']),
  distance_km: PropTypes.number.isRequired
};

StationInfoCard.defaultProps = {
  importance: 'secondary'
};
