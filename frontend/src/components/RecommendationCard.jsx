import React from 'react';
import { formatCurrency, formatMinutes } from '../utils/formatters';
import { Award, MapPin, Clock, IndianRupee, AlertCircle } from 'lucide-react';

const modeIcons = { car: '🚗', ev: '🔋', train: '🚂', flight: '✈️' };

const modeGradients = {
  car: 'from-emerald-500/12 to-teal-500/8',
  ev: 'from-sky-500/12 to-cyan-500/8',
  train: 'from-blue-500/12 to-indigo-500/8',
  flight: 'from-violet-500/12 to-purple-500/8',
};

export default function RecommendationCard({ mode, reason, cost, distance, duration, source, destination }) {
  const gradient = modeGradients[mode] || modeGradients.car;

  const trainTradeoffNote = mode === 'train' ? (
    <div className="mt-4 p-3.5 bg-amber-50/70 dark:bg-amber-900/15 border border-amber-200/50 dark:border-amber-700/40 rounded-xl text-sm flex items-start gap-2.5">
      <AlertCircle size={16} className="text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" />
      <p className="text-amber-800 dark:text-amber-200">
        <strong>💰 Budget-Friendly Choice:</strong> Trains are most economical but take longer travel time.
        {duration && duration > 600 && <span> This journey will take ~{Math.round(duration / 60)}+ hours.</span>}
      </p>
    </div>
  ) : null;

  const personalizedReason = (source && destination)
    ? `Recommended for your trip from ${source} to ${destination}: ${reason}`
    : reason;

  return (
    <div className="group relative overflow-hidden rounded-2xl border-2 border-teal-300/50 dark:border-teal-600/50 bg-gradient-to-br from-teal-50/60 via-white/70 to-sky-50/60 dark:from-teal-900/20 dark:via-slate-800/80 dark:to-sky-900/20 p-6 shadow-sm hover:shadow-xl transition-all duration-300 backdrop-blur-sm">
      {/* Top gradient accent */}
      <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-teal-500 to-sky-500 opacity-80" />

      <div className="flex items-start gap-4">
        {/* Mode Icon */}
        <div className={`w-16 h-16 rounded-2xl bg-gradient-to-br ${gradient} flex items-center justify-center text-4xl border border-white/30 dark:border-slate-700/30 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform duration-300`}>
          {modeIcons[mode] || '🚗'}
        </div>

        <div className="flex-1 min-w-0">
          {/* Header */}
          <div className="flex items-center gap-2.5 mb-2 flex-wrap">
            <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-teal-600 dark:bg-teal-500 text-white text-xs font-bold rounded-full shadow-sm">
              <Award size={12} />
              Recommended
            </span>
            <h3 className="text-xl font-extrabold text-slate-800 dark:text-slate-100 capitalize">{mode}</h3>
          </div>

          <p className="text-sm text-slate-700 dark:text-slate-300 mb-4 leading-relaxed">{personalizedReason}</p>

          {/* Stats Grid */}
          <div className="grid grid-cols-3 gap-4">
            <div className="flex flex-col items-center p-2.5 rounded-xl bg-white/60 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/40">
              <IndianRupee size={14} className="text-teal-500 dark:text-teal-400 mb-1" />
              <p className="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">Cost</p>
              <p className="text-base font-extrabold text-teal-700 dark:text-teal-300">{formatCurrency(cost)}</p>
            </div>
            {distance && (
              <div className="flex flex-col items-center p-2.5 rounded-xl bg-white/60 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/40">
                <MapPin size={14} className="text-slate-400 dark:text-slate-500 mb-1" />
                <p className="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">Distance</p>
                <p className="text-base font-extrabold text-slate-800 dark:text-slate-200">{distance.toFixed(1)} km</p>
              </div>
            )}
            {duration && (
              <div className="flex flex-col items-center p-2.5 rounded-xl bg-white/60 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/40">
                <Clock size={14} className="text-slate-400 dark:text-slate-500 mb-1" />
                <p className="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">Duration</p>
                <p className="text-base font-extrabold text-slate-800 dark:text-slate-200">{formatMinutes(duration)}</p>
              </div>
            )}
          </div>

          {trainTradeoffNote}
        </div>
      </div>
    </div>
  );
}
