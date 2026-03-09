import React from 'react';
import { useNavigate } from 'react-router-dom';
import { ExternalLink, ArrowRight, AlertTriangle } from 'lucide-react';

const modeGradients = {
  train: 'from-blue-500/12 to-indigo-500/8',
  flight: 'from-violet-500/12 to-purple-500/8',
  bus: 'from-amber-500/12 to-orange-500/8',
  car: 'from-emerald-500/12 to-teal-500/8',
  ev: 'from-sky-500/12 to-cyan-500/8',
};

const modeBorderColors = {
  train: 'hover:border-blue-400 dark:hover:border-blue-500',
  flight: 'hover:border-violet-400 dark:hover:border-violet-500',
  bus: 'hover:border-amber-400 dark:hover:border-amber-500',
  car: 'hover:border-emerald-400 dark:hover:border-emerald-500',
  ev: 'hover:border-sky-400 dark:hover:border-sky-500',
};

const badgeColors = {
  Cheapest: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  Fastest: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
  Budget: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
  Flexible: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
  Eco: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
};

export default function TripOptionsPanel({
  source,
  destination,
  distance_km,
  costs = {},
  durations = {}
}) {
  const navigate = useNavigate();

  if (!source || !destination) return null;

  const formatTime = (minutes) => {
    if (!minutes) return 'N/A';
    const hours = Math.floor(minutes / 60);
    const mins = Math.round(minutes % 60);
    if (hours === 0) return `${mins}m`;
    if (mins === 0) return `${hours}h`;
    return `${hours}h ${mins}m`;
  };

  const formatCost = (cost) => {
    if (!cost) return 'N/A';
    return `₹${Math.round(cost).toLocaleString('en-IN')}`;
  };

  const transportOptions = [
    {
      key: 'train', icon: '🚂', name: 'Train',
      description: 'Most economical, longer journey',
      cost: costs.train, duration: durations.train, isExternal: true,
      action: () => window.open('https://www.irctc.co.in/nget/booking/train-search', '_blank'),
      badge: 'Cheapest'
    },
    {
      key: 'flight', icon: '✈️', name: 'Flight',
      description: 'Fastest option',
      cost: costs.flight, duration: durations.flight, isExternal: true,
      action: () => window.open('https://www.skyscanner.co.in/?utm_source=routeiq', '_blank'),
      badge: 'Fastest'
    },
    {
      key: 'bus', icon: '🚌', name: 'Bus',
      description: 'Affordable, comfortable',
      cost: costs.bus, duration: durations.bus, isExternal: true,
      action: () => window.open('https://www.redbus.in/', '_blank'),
      badge: 'Budget'
    },
    {
      key: 'car', icon: '🚗', name: 'Car',
      description: 'Direct, flexible schedule',
      cost: costs.car, duration: durations.car, isExternal: false,
      action: () => navigate(`/journey/car?source=${encodeURIComponent(source)}&destination=${encodeURIComponent(destination)}`),
      badge: 'Flexible'
    },
    {
      key: 'ev', icon: '⚡', name: 'EV',
      description: 'Eco-friendly, low cost',
      cost: costs.ev, duration: durations.ev, isExternal: false,
      action: () => navigate(`/journey/ev?source=${encodeURIComponent(source)}&destination=${encodeURIComponent(destination)}`),
      badge: 'Eco'
    }
  ];

  return (
    <div className="riq-panel p-6 sm:p-8 mb-6 riq-fade-up">
      <h2 className="text-2xl font-extrabold mb-2" style={{ color: 'var(--riq-text)' }}>
        Travel Options
      </h2>
      <p className="mb-6" style={{ color: 'var(--riq-text-muted)' }}>
        Choose your preferred mode to continue. External bookings open in a new tab.
      </p>

      {/* Transport Options Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {transportOptions.map((option, index) => (
          <div
            key={option.key}
            onClick={option.action}
            className={`
              group relative overflow-hidden rounded-2xl border-2 border-slate-200/70 dark:border-slate-700/60
              bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm p-5 cursor-pointer
              ${modeBorderColors[option.key]} hover:shadow-xl
              transition-all duration-300 hover:-translate-y-1.5
              riq-fade-up
            `}
            style={{ animationDelay: `${index * 80}ms` }}
          >
            {/* Top gradient accent */}
            <div className={`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${modeGradients[option.key]} opacity-0 group-hover:opacity-100 transition-opacity duration-300`} />

            {/* Badge */}
            {option.badge && (
              <div className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold mb-3 ${badgeColors[option.badge]}`}>
                {option.badge}
              </div>
            )}

            {/* Icon */}
            <div className={`w-14 h-14 rounded-2xl bg-gradient-to-br ${modeGradients[option.key]} flex items-center justify-center text-3xl mb-3 border border-white/20 dark:border-slate-700/30 shadow-sm group-hover:scale-105 transition-transform duration-300`}>
              {option.icon}
            </div>

            {/* Name & Description */}
            <h3 className="font-bold text-slate-800 dark:text-slate-100 mb-1">{option.name}</h3>
            <p className="text-xs mb-4 line-clamp-2" style={{ color: 'var(--riq-text-muted)' }}>
              {option.description}
            </p>

            {/* Cost & Duration */}
            <div className="space-y-2 mb-4 border-t border-slate-100 dark:border-slate-700/60 pt-3">
              <div>
                <p className="text-[10px] uppercase tracking-wider font-semibold" style={{ color: 'var(--riq-text-faint)' }}>Cost</p>
                <p className="text-lg font-extrabold riq-gradient-text">{formatCost(option.cost)}</p>
              </div>
              <div>
                <p className="text-[10px] uppercase tracking-wider font-semibold" style={{ color: 'var(--riq-text-faint)' }}>Duration</p>
                <p className="text-sm font-bold text-slate-800 dark:text-slate-200">{formatTime(option.duration)}</p>
              </div>
            </div>

            {/* Action Button */}
            <button className="w-full py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:brightness-110 text-white font-bold rounded-xl text-sm transition-all duration-200 inline-flex items-center justify-center gap-2">
              {option.isExternal ? (
                <>Book Now <ExternalLink size={13} /></>
              ) : (
                <>Plan Journey <ArrowRight size={13} /></>
              )}
            </button>

            {option.isExternal && (
              <p className="text-[10px] mt-2 text-center font-medium" style={{ color: 'var(--riq-text-faint)' }}>
                Opens external site
              </p>
            )}
          </div>
        ))}
      </div>

      {/* Disclaimer */}
      <div className="mt-6 p-4 rounded-xl border border-amber-200/60 dark:border-amber-700/40 bg-amber-50/60 dark:bg-amber-900/10 flex items-start gap-3">
        <AlertTriangle size={18} className="text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
        <p className="text-sm text-amber-800 dark:text-amber-200">
          <strong>Note:</strong> Train, Flight, and Bus open external booking sites.
          Always verify current prices and schedules before booking.
          Car and EV options stay within RouteIQ for detailed planning.
        </p>
      </div>
    </div>
  );
}
