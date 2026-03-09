import React from 'react';
import PropTypes from 'prop-types';

/**
 * PHASE 5: AirportInfoCard - Flight Domain Model
 */
export default function AirportInfoCard({ name, iata_code, city, distance_km }) {
  return (
    <div className="relative overflow-hidden rounded-2xl border border-violet-300/40 dark:border-violet-600/40 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-sm hover:shadow-xl transition-all duration-300 p-6">
      {/* Left accent bar */}
      <div className="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-violet-400 to-purple-500 rounded-l-2xl" />

      {/* Header */}
      <div className="flex justify-between items-start mb-4 pl-2">
        <div>
          <h3 className="text-xl font-bold" style={{ color: 'var(--riq-text)' }}>✈️ {name}</h3>
          <p className="text-sm mt-1" style={{ color: 'var(--riq-text-muted)' }}>
            {iata_code && <span className="font-mono bg-violet-100/80 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 px-2 py-0.5 rounded-md text-xs font-bold">{iata_code}</span>}
          </p>
        </div>
        <div className="px-3 py-1 rounded-full text-xs font-bold bg-violet-100/80 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
          🌍 International
        </div>
      </div>

      {/* Details Grid */}
      <div className="grid grid-cols-2 gap-4 mb-4 pl-2">
        <div className="p-3 rounded-xl bg-slate-50/60 dark:bg-slate-700/30 border border-slate-100/60 dark:border-slate-700/40">
          <div className="text-[10px] uppercase tracking-wider font-bold mb-1" style={{ color: 'var(--riq-text-faint)' }}>City</div>
          <div className="font-semibold text-sm" style={{ color: 'var(--riq-text)' }}>{city}</div>
        </div>
        <div className="p-3 rounded-xl bg-slate-50/60 dark:bg-slate-700/30 border border-slate-100/60 dark:border-slate-700/40">
          <div className="text-[10px] uppercase tracking-wider font-bold mb-1" style={{ color: 'var(--riq-text-faint)' }}>Flight Distance</div>
          <div className="font-semibold text-sm" style={{ color: 'var(--riq-text)' }}>{distance_km.toFixed(1)} km</div>
        </div>
      </div>

      {/* Description */}
      <div className="pt-4 border-t border-slate-200/60 dark:border-slate-700/50 pl-2">
        <p className="text-sm" style={{ color: 'var(--riq-text-muted)' }}>
          🛫 Major aviation hub with international connectivity. Check baggage allowances and advance check-in requirements.
        </p>
      </div>
    </div>
  );
}

AirportInfoCard.propTypes = {
  name: PropTypes.string.isRequired,
  iata_code: PropTypes.string.isRequired,
  city: PropTypes.string.isRequired,
  distance_km: PropTypes.number.isRequired
};
