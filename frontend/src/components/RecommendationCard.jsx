import React from 'react';
import { formatCurrency, formatMinutes } from '../utils/formatters';

const modeIcons = {
  car: '🚗',
  ev: '🔋',
  train: '🚂',
  flight: '✈️',
};

export default function RecommendationCard({ mode, reason, cost, distance, duration }) {
  return (
    <div className="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg p-6 shadow-sm">
      <div className="flex items-start space-x-4">
        <div className="text-5xl">{modeIcons[mode] || '🚗'}</div>
        <div className="flex-1">
          <div className="flex items-center space-x-2 mb-2">
            <span className="px-3 py-1 bg-blue-600 text-white text-xs font-semibold rounded-full">
              Recommended
            </span>
            <h3 className="text-xl font-bold text-slate-800 capitalize">{mode}</h3>
          </div>

          <p className="text-slate-700 mb-4">{reason}</p>

          <div className="grid grid-cols-3 gap-4">
            <div>
              <p className="text-xs text-slate-500 uppercase tracking-wide">Cost</p>
              <p className="text-lg font-bold text-blue-600">{formatCurrency(cost)}</p>
            </div>
            {distance && (
              <div>
                <p className="text-xs text-slate-500 uppercase tracking-wide">Distance</p>
                <p className="text-lg font-bold text-slate-800">{distance.toFixed(1)} km</p>
              </div>
            )}
            {duration && (
              <div>
                <p className="text-xs text-slate-500 uppercase tracking-wide">Duration</p>
                <p className="text-lg font-bold text-slate-800">{formatMinutes(duration)}</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

