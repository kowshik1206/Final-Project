import React from 'react';
import { formatCurrency, formatKm, formatMinutes } from '../utils/formatters';
import { MapPin, Clock, IndianRupee, Car, Waypoints, ArrowRight } from 'lucide-react';

const modeConfig = {
  car: { emoji: '🚗', gradient: 'from-emerald-500/12 to-teal-500/8', accent: 'text-emerald-600 dark:text-emerald-400' },
  ev: { emoji: '🔋', gradient: 'from-sky-500/12 to-cyan-500/8', accent: 'text-sky-600 dark:text-sky-400' },
  train: { emoji: '🚂', gradient: 'from-blue-500/12 to-indigo-500/8', accent: 'text-blue-600 dark:text-blue-400' },
  flight: { emoji: '✈️', gradient: 'from-violet-500/12 to-purple-500/8', accent: 'text-violet-600 dark:text-violet-400' },
  bus: { emoji: '🚌', gradient: 'from-amber-500/12 to-orange-500/8', accent: 'text-amber-600 dark:text-amber-400' },
};

export default function TripSummaryCard({
  source,
  destination,
  distance,
  duration,
  cost,
  mode,
  date,
  onView,
  vehicleName,
  stopsCount,
}) {
  const config = modeConfig[mode] || modeConfig.car;
  const modeLabel = (mode || 'car').toUpperCase();

  return (
    <article className="group relative overflow-hidden rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
      {/* Top Gradient Accent */}
      <div className={`h-1 bg-gradient-to-r ${config.gradient}`} />

      <div className="p-5">
        {/* Header */}
        <div className="flex items-start justify-between gap-3 mb-4">
          <div className="flex-1">
            <span className="riq-mode-pill mb-2 inline-flex">
              {config.emoji} {modeLabel}
            </span>
            <h3 className="text-lg font-bold text-slate-800 dark:text-slate-100 mt-2 leading-tight">
              {source}
              <span className="inline-flex mx-2 text-teal-500 dark:text-teal-400">
                <ArrowRight size={16} />
              </span>
              {destination}
            </h3>
            <p className="text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
              {date ? new Date(date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Recent trip'}
            </p>
          </div>
          <div className={`w-12 h-12 rounded-xl bg-gradient-to-br ${config.gradient} flex items-center justify-center text-2xl border border-white/20 dark:border-slate-700/30 shadow-sm flex-shrink-0`}>
            {config.emoji}
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-3 gap-3 mb-4 py-3 border-y border-slate-100 dark:border-slate-700/60">
          <div className="flex flex-col items-center text-center">
            <MapPin size={14} className="text-slate-400 dark:text-slate-500 mb-1" />
            <p className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Distance</p>
            <p className="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">{formatKm(distance)}</p>
          </div>
          <div className="flex flex-col items-center text-center">
            <Clock size={14} className="text-slate-400 dark:text-slate-500 mb-1" />
            <p className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Duration</p>
            <p className="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">{formatMinutes(duration)}</p>
          </div>
          <div className="flex flex-col items-center text-center">
            <IndianRupee size={14} className="text-teal-500 dark:text-teal-400 mb-1" />
            <p className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-medium">Cost</p>
            <p className="text-sm font-bold text-teal-700 dark:text-teal-300 mt-0.5">{formatCurrency(cost)}</p>
          </div>
        </div>

        {/* Vehicle & Stops */}
        <div className="grid grid-cols-2 gap-3 mb-5">
          <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-700/40">
            <Car size={14} className="text-slate-400 dark:text-slate-500 flex-shrink-0" />
            <div>
              <p className="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">Vehicle</p>
              <p className="text-xs font-bold text-slate-800 dark:text-slate-200">{vehicleName || '—'}</p>
            </div>
          </div>
          <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-700/40">
            <Waypoints size={14} className="text-slate-400 dark:text-slate-500 flex-shrink-0" />
            <div>
              <p className="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">Stops</p>
              <p className="text-xs font-bold text-slate-800 dark:text-slate-200">{stopsCount > 0 ? `${stopsCount} stops` : 'None'}</p>
            </div>
          </div>
        </div>

        {/* View Button */}
        <button
          onClick={onView}
          className="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-xl hover:brightness-110 hover:shadow-lg transition-all duration-200 text-sm font-bold inline-flex items-center justify-center gap-2"
        >
          View Details
          <ArrowRight size={14} />
        </button>
      </div>
    </article>
  );
}
