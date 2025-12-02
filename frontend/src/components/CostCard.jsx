import React from 'react';
import { formatCurrency, formatMinutes } from '../utils/formatters';

const modeIcons = {
  car: '🚗',
  ev: '🔋',
  train: '🚂',
  flight: '✈️',
};

export default function CostCard({
  mode,
  cost,
  duration,
  selected = false,
  onSelect,
}) {
  return (
    <div
      onClick={onSelect}
      className={`p-6 rounded-lg border-2 transition cursor-pointer ${
        selected
          ? 'border-blue-600 bg-blue-50'
          : 'border-slate-200 bg-white hover:border-blue-300'
      } shadow-sm hover:shadow-md`}
    >
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center space-x-3">
          <span className="text-4xl">{modeIcons[mode] || '🚗'}</span>
          <div>
            <h3 className="text-lg font-semibold text-slate-800 capitalize">
              {mode}
            </h3>
            {duration && (
              <p className="text-sm text-slate-500">{formatMinutes(duration)}</p>
            )}
          </div>
        </div>
        {selected && (
          <div className="text-lg">
            <input type="radio" checked={true} readOnly />
          </div>
        )}
      </div>

      <div className="text-3xl font-bold text-blue-600 mb-4">
        {formatCurrency(cost)}
      </div>

      <button
        className={`w-full py-2 rounded-lg transition font-medium ${
          selected
            ? 'bg-blue-600 text-white'
            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
        }`}
      >
        {selected ? 'Selected' : 'Select'}
      </button>
    </div>
  );
}

