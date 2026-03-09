import React from 'react';
import { formatCurrency, formatMinutes } from '../utils/formatters';
import { Check, Shield, AlertTriangle, Info } from 'lucide-react';

const modeIcons = { car: '🚗', ev: '🔋', bus: '🚌', train: '🚂', flight: '✈️' };

const modeGradients = {
  car: 'from-emerald-500/10 to-teal-500/8',
  ev: 'from-sky-500/10 to-cyan-500/8',
  bus: 'from-amber-500/10 to-orange-500/8',
  train: 'from-blue-500/10 to-indigo-500/8',
  flight: 'from-violet-500/10 to-purple-500/8',
};

const modeAccents = {
  car: { ring: 'ring-emerald-400/40', border: 'border-emerald-400/50', bg: 'bg-emerald-500/5' },
  ev: { ring: 'ring-sky-400/40', border: 'border-sky-400/50', bg: 'bg-sky-500/5' },
  bus: { ring: 'ring-amber-400/40', border: 'border-amber-400/50', bg: 'bg-amber-500/5' },
  train: { ring: 'ring-blue-400/40', border: 'border-blue-400/50', bg: 'bg-blue-500/5' },
  flight: { ring: 'ring-violet-400/40', border: 'border-violet-400/50', bg: 'bg-violet-500/5' },
};

const confidenceLevels = {
  car: { icon: Shield, label: 'High Confidence', color: 'text-emerald-600 dark:text-emerald-400', tooltip: 'Based on real-time routing data' },
  ev: { icon: Shield, label: 'High Confidence', color: 'text-emerald-600 dark:text-emerald-400', tooltip: 'Based on real-time routing data' },
  bus: { icon: Info, label: 'Estimated', color: 'text-amber-600 dark:text-amber-400', tooltip: 'Actual prices vary by date & operator' },
  train: { icon: AlertTriangle, label: 'Conceptual', color: 'text-orange-600 dark:text-orange-400', tooltip: 'Demo-grade estimate. Verify before booking.' },
  flight: { icon: AlertTriangle, label: 'Conceptual', color: 'text-orange-600 dark:text-orange-400', tooltip: 'Demo-grade estimate. Verify before booking.' },
};

function getPriceRange(baseCost, confidenceLabel) {
  let rangePercent;
  if (confidenceLabel === 'High Confidence') rangePercent = 0.05;
  else if (confidenceLabel === 'Estimated') rangePercent = 0.15;
  else if (confidenceLabel === 'Conceptual') rangePercent = 0.30;
  else rangePercent = 0.15;

  const min = baseCost * (1 - rangePercent);
  const max = baseCost * (1 + rangePercent);

  const roundToClean = (value) => {
    if (value >= 1000) return Math.round(value / 100) * 100;
    if (value >= 100) return Math.round(value / 50) * 50;
    return Math.round(value / 10) * 10;
  };

  return { min: roundToClean(min), max: roundToClean(max) };
}

export default function CostCard({
  mode,
  cost,
  duration,
  selected = false,
  onSelect,
  highlight = null,
  showExplanation = true,
}) {
  const confidence = confidenceLevels[mode] || { icon: Info, label: 'Unknown', color: 'text-slate-500' };
  const ConfidenceIcon = confidence.icon;
  const priceRange = getPriceRange(cost, confidence.label);
  const accent = modeAccents[mode] || modeAccents.car;
  const gradient = modeGradients[mode] || modeGradients.car;

  const highlightBadges = {
    cheapest: { icon: '🏷️', text: 'Cheapest', bg: 'bg-emerald-100 dark:bg-emerald-900/30', textColor: 'text-emerald-700 dark:text-emerald-300', explanation: 'Recommended because it has the lowest estimated cost for this route.' },
    fastest: { icon: '⚡', text: 'Fastest', bg: 'bg-sky-100 dark:bg-sky-900/30', textColor: 'text-sky-700 dark:text-sky-300', explanation: 'Recommended because it is the fastest available option.' },
    eco: { icon: '🌱', text: 'Eco-Friendly', bg: 'bg-teal-100 dark:bg-teal-900/30', textColor: 'text-teal-700 dark:text-teal-300', explanation: 'Recommended as the most eco-friendly choice for this journey.' },
  };

  const highlightBadge = highlight ? highlightBadges[highlight] : null;

  return (
    <div
      onClick={onSelect}
      className={`
        group relative overflow-hidden rounded-2xl border-2 p-6 cursor-pointer
        transition-all duration-300 ease-out
        ${selected
          ? `${accent.border} ${accent.bg} shadow-lg ring-2 ${accent.ring}`
          : 'border-slate-200/70 dark:border-slate-700/70 hover:border-teal-300 dark:hover:border-teal-600 shadow-sm hover:shadow-xl'
        }
        bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm
        hover:-translate-y-1
      `}
    >
      {/* Top gradient accent bar */}
      <div className={`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${gradient} ${selected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'} transition-opacity duration-300`} />

      {/* Highlight Badge */}
      {highlightBadge && (
        <div className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold mb-4 ${highlightBadge.bg} ${highlightBadge.textColor}`}>
          <span>{highlightBadge.icon}</span>
          <span>{highlightBadge.text}</span>
        </div>
      )}

      {/* Mode Header */}
      <div className="flex items-start justify-between mb-5">
        <div className="flex items-center gap-3">
          <div className={`w-12 h-12 rounded-xl bg-gradient-to-br ${gradient} flex items-center justify-center text-2xl border border-white/20 dark:border-slate-700/30 shadow-sm`}>
            {modeIcons[mode] || '🚗'}
          </div>
          <div>
            <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100 capitalize">{mode}</h3>
            {duration && (
              <p className="text-sm text-slate-500 dark:text-slate-400">{formatMinutes(duration)}</p>
            )}
          </div>
        </div>

        {/* Confidence indicator */}
        <div className="flex items-center gap-1 group/conf relative">
          <ConfidenceIcon size={14} className={confidence.color} />
          <span className={`text-xs font-semibold ${confidence.color}`}>{confidence.label}</span>
          <span className="hidden group-hover/conf:block absolute right-0 top-6 bg-slate-900 dark:bg-slate-700 text-white px-2.5 py-1.5 rounded-lg text-xs whitespace-nowrap z-20 shadow-lg">
            {confidence.tooltip}
          </span>
        </div>
      </div>

      {/* Price Range */}
      <div className="mb-5">
        <div className="text-2xl sm:text-3xl font-extrabold riq-gradient-text">
          ₹{priceRange.min.toLocaleString('en-IN')} – ₹{priceRange.max.toLocaleString('en-IN')}
        </div>
        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Estimated price range</p>
      </div>

      {/* Explanation */}
      {selected && highlightBadge && showExplanation && (
        <p className="text-sm text-slate-600 dark:text-slate-400 mb-4 italic border-l-2 border-teal-400 pl-3">
          {highlightBadge.explanation}
        </p>
      )}

      {/* Select Button */}
      <button
        className={`
          w-full py-2.5 rounded-xl font-bold text-sm transition-all duration-200
          ${selected
            ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md hover:shadow-lg'
            : 'bg-slate-100 dark:bg-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'
          }
        `}
      >
        {selected ? (
          <span className="inline-flex items-center gap-2">
            <Check size={16} /> Selected
          </span>
        ) : 'Select'}
      </button>
    </div>
  );
}
