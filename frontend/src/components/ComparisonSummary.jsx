import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Lightbulb, CheckCircle, ArrowUpRight, AlertCircle } from 'lucide-react';

/**
 * ComparisonSummary — Premium navigation launcher for mode-specific journey pages
 */
export default function ComparisonSummary({
  source = null,
  destination = null,
  recommendation = null,
  distance_km = null,
  car = null,
  ev = null,
  train = null,
  bus = null,
  flight = null
}) {
  const navigate = useNavigate();

  const handleModeClick = (modeKey) => {
    const routes = { car: '/journey/car', ev: '/journey/ev', bus: '/journey/bus', train: '/journey/train', flight: '/journey/flight' };
    const targetRoute = routes[modeKey];
    if (targetRoute && source && destination) {
      const params = new URLSearchParams({
        source, destination,
        ...(distance_km && { distance: distance_km.toString() })
      });
      navigate(`${targetRoute}?${params.toString()}`, {
        state: { source, destination, distance_km },
        replace: false
      });
    }
  };

  const formatCost = (cost) => {
    if (cost === null || cost === undefined) return 'N/A';
    return `₹${Math.round(cost).toLocaleString('en-IN')}`;
  };

  const formatTime = (minutes) => {
    if (!minutes) return 'N/A';
    const hours = Math.floor(minutes / 60);
    const mins = Math.round(minutes % 60);
    if (hours === 0) return `${mins}m`;
    if (mins === 0) return `${hours}h`;
    return `${hours}h ${mins}m`;
  };

  const isUnavailable = (mode) => {
    if (mode === null) return true;
    if (mode.available === false) return true;
    if (mode.feasible === false) return true;
    return false;
  };

  const getTrainLabel = () => {
    if (!distance_km) return null;
    if (distance_km > 500) return 'Most reliable for long distances';
    return 'Comfortable & Efficient';
  };

  const getCarDetails = () => 'Flexible schedule & door-to-door';
  const getBusDetails = () => 'Budget-friendly option';
  const getFlightDetails = () => 'Fastest travel time';
  const getEVDetails = () => 'Eco-friendly & low running cost';

  const modeGradients = {
    car: 'from-emerald-500/12 to-teal-500/8',
    ev: 'from-sky-500/12 to-cyan-500/8',
    train: 'from-blue-500/12 to-indigo-500/8',
    bus: 'from-amber-500/12 to-orange-500/8',
    flight: 'from-violet-500/12 to-purple-500/8',
  };

  const modes = [
    { key: 'car', label: 'Car', icon: '🚗', data: car, cost: car?.cost, primary: car?.duration, primaryLabel: 'Duration', secondary: car?.stops, secondaryLabel: 'Stops' },
    { key: 'ev', label: 'EV', icon: '⚡', data: ev, cost: ev?.cost, primary: ev?.chargingStops, primaryLabel: 'Charging Stops', secondary: null },
    { key: 'train', label: 'Train', icon: '🚆', data: train, cost: train?.cost, primary: train?.duration, primaryLabel: 'Duration', secondary: null },
    { key: 'bus', label: 'Bus', icon: '🚌', data: bus, cost: bus?.cost, primary: bus?.duration, primaryLabel: 'Duration', secondary: bus?.stops, secondaryLabel: 'Stops' },
    { key: 'flight', label: 'Flight', icon: '✈️', data: flight, cost: flight?.cost, primary: flight?.duration, primaryLabel: 'Duration', secondary: null },
  ];

  const recommendedMode = recommendation?.recommended_mode;

  const sortedModes = [...modes].sort((a, b) => {
    const costA = a.cost ?? Infinity;
    const costB = b.cost ?? Infinity;
    return costA - costB;
  });

  return (
    <div className="riq-panel p-6 sm:p-8 riq-fade-up">
      <h3 className="text-xl font-extrabold mb-5" style={{ color: 'var(--riq-text)' }}>
        Comparison Summary{' '}
        {source && destination && (
          <span className="font-semibold" style={{ color: 'var(--riq-text-muted)' }}>
            for {source} → {destination}
          </span>
        )}
      </h3>

      {/* Table */}
      <div className="overflow-x-auto rounded-xl border border-slate-200/70 dark:border-slate-700/60">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-slate-50/80 dark:bg-slate-800/60">
              <th className="text-left py-3.5 px-4 font-bold text-slate-700 dark:text-slate-200 text-xs uppercase tracking-wider">Mode</th>
              <th className="text-right py-3.5 px-4 font-bold text-slate-700 dark:text-slate-200 text-xs uppercase tracking-wider">Cost</th>
              <th className="text-right py-3.5 px-4 font-bold text-slate-700 dark:text-slate-200 text-xs uppercase tracking-wider">Time / Stops</th>
              <th className="text-left py-3.5 px-4 font-bold text-slate-700 dark:text-slate-200 text-xs uppercase tracking-wider">Details</th>
            </tr>
          </thead>
          <tbody>
            {sortedModes.map((mode, idx) => {
              const unavailable = isUnavailable(mode.data);
              const isRecommended = mode.key === recommendedMode;

              return (
                <tr
                  key={mode.key}
                  onClick={() => !unavailable && handleModeClick(mode.key)}
                  className={`
                    border-b border-slate-100 dark:border-slate-700/50 transition-all duration-200
                    ${isRecommended
                      ? 'bg-teal-50/60 dark:bg-teal-900/20 ring-1 ring-inset ring-teal-300/50 dark:ring-teal-600/40'
                      : unavailable
                        ? 'bg-slate-50/40 dark:bg-slate-900/30 opacity-50 cursor-not-allowed'
                        : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40 cursor-pointer'
                    }
                  `}
                >
                  {/* Mode Label */}
                  <td className="py-3.5 px-4">
                    <div className="flex items-center gap-2.5">
                      <div className={`w-8 h-8 rounded-lg bg-gradient-to-br ${modeGradients[mode.key]} flex items-center justify-center text-base border border-white/20 dark:border-slate-700/30`}>
                        {mode.icon}
                      </div>
                      <span className={`font-bold ${isRecommended ? 'text-teal-700 dark:text-teal-300' : 'text-slate-800 dark:text-slate-100'}`}>
                        {mode.label}
                      </span>
                      {isRecommended && (
                        <span className="inline-flex items-center gap-1 ml-1 px-2 py-0.5 bg-teal-600 dark:bg-teal-500 text-white text-[10px] rounded-full font-bold">
                          <CheckCircle size={10} />
                          Recommended
                        </span>
                      )}
                    </div>
                  </td>

                  {/* Cost */}
                  <td className="py-3.5 px-4 text-right">
                    {unavailable ? (
                      <span className="text-slate-400 italic text-xs">Not Available</span>
                    ) : (
                      <span className={`font-extrabold ${isRecommended ? 'riq-gradient-text' : 'text-slate-800 dark:text-slate-100'}`}>
                        {formatCost(mode.cost)}
                      </span>
                    )}
                  </td>

                  {/* Primary Metric */}
                  <td className="py-3.5 px-4 text-right">
                    {unavailable ? (
                      <span className="text-slate-400">—</span>
                    ) : mode.primary !== null && mode.primary !== undefined ? (
                      <div className="flex flex-col items-end">
                        <span className={`font-bold ${isRecommended ? 'text-teal-700 dark:text-teal-300' : 'text-slate-800 dark:text-slate-100'}`}>
                          {mode.key === 'ev' && typeof mode.primary === 'number'
                            ? mode.primary
                            : formatTime(mode.primary)}
                        </span>
                        <span className="text-[10px] font-medium" style={{ color: 'var(--riq-text-faint)' }}>{mode.primaryLabel}</span>
                      </div>
                    ) : (
                      <span className="text-slate-400">—</span>
                    )}
                  </td>

                  {/* Details */}
                  <td className="py-3.5 px-4 text-left">
                    {unavailable ? (
                      <div className="flex items-center gap-1.5 text-xs">
                        <AlertCircle size={12} className="text-slate-400" />
                        <span className="text-slate-400">Route not feasible for this mode</span>
                      </div>
                    ) : (
                      <div className="text-xs space-y-1" style={{ color: 'var(--riq-text-muted)' }}>
                        {mode.secondary !== null && mode.secondary !== undefined && (
                          <div><span className="font-semibold">{mode.secondaryLabel}:</span> {Math.round(mode.secondary)}</div>
                        )}
                        {mode.key === 'car' && <div>{getCarDetails()}</div>}
                        {mode.key === 'bus' && <div>{getBusDetails()}</div>}
                        {mode.key === 'flight' && <div className="text-sky-600 dark:text-sky-300 font-semibold">⚡ {getFlightDetails()}</div>}
                        {mode.key === 'ev' && <div className="text-teal-600 dark:text-teal-300">{getEVDetails()}</div>}
                        {mode.key === 'train' && getTrainLabel() && (
                          <div className="text-sky-600 dark:text-sky-300 font-semibold">💡 {getTrainLabel()}</div>
                        )}
                        {mode.key === 'ev' && mode.data?.feasible === false && (
                          <div className="text-red-500 font-semibold">⚠️ Not feasible for this route</div>
                        )}
                        {isRecommended && recommendation?.reason && (
                          <div className="text-teal-700 dark:text-teal-300 font-semibold mt-1">✓ {recommendation.reason}</div>
                        )}
                      </div>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {/* Footer */}
      <div className="mt-5 p-4 bg-sky-50/60 dark:bg-sky-900/15 border border-sky-200/50 dark:border-sky-700/40 rounded-xl flex items-start gap-2.5">
        <Lightbulb size={16} className="text-sky-500 dark:text-sky-400 flex-shrink-0 mt-0.5" />
        <p className="text-xs text-sky-800 dark:text-sky-200">
          <strong>Tip:</strong> All costs shown are estimates. Final choice depends on your preferences,
          comfort needs, and actual route conditions. Click any row to explore that mode.
        </p>
      </div>
    </div>
  );
}
