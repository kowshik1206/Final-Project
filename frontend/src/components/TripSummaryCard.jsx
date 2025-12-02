import React from 'react';
import { formatCurrency, formatKm, formatMinutes } from '../utils/formatters';

export default function TripSummaryCard({
  source,
  destination,
  distance,
  duration,
  cost,
  mode,
  date,
  onView,
}) {
  const modeEmojis = {
    car: '🚗',
    ev: '🔋',
    train: '🚂',
    flight: '✈️',
  };

  return (
    <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-4 hover:shadow-md transition">
      <div className="flex items-start justify-between mb-3">
        <div className="flex-1">
          <h3 className="text-lg font-semibold text-slate-800 mb-1">
            {source} → {destination}
          </h3>
          <p className="text-xs text-slate-500">
            {date ? new Date(date).toLocaleDateString() : 'Recent trip'}
          </p>
        </div>
        <span className="text-3xl">{modeEmojis[mode] || '🚗'}</span>
      </div>

      <div className="grid grid-cols-3 gap-3 mb-4 pb-4 border-b border-slate-100">
        <div>
          <p className="text-xs text-slate-500 uppercase tracking-wide">Distance</p>
          <p className="text-sm font-semibold text-slate-800">{formatKm(distance)}</p>
        </div>
        <div>
          <p className="text-xs text-slate-500 uppercase tracking-wide">Duration</p>
          <p className="text-sm font-semibold text-slate-800">{formatMinutes(duration)}</p>
        </div>
        <div>
          <p className="text-xs text-slate-500 uppercase tracking-wide">Cost</p>
          <p className="text-sm font-semibold text-blue-600">{formatCurrency(cost)}</p>
        </div>
      </div>

      <button
        onClick={onView}
        className="w-full py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
      >
        View Details
      </button>
    </div>
  );
}
