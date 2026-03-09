import React from "react";
import { CheckCircle, Award, PiggyBank } from "lucide-react";
import { getEffectiveEfficiency } from "../utils/vehicleRange";

export default function FuelComparison({
  vehicles = [],
  distance_km = 0,
  fuelPrices = {},
  onSelect = () => { },
  selectedVehicleId = null,
  customEfficiency = null
} = {}) {
  if (!vehicles || vehicles.length === 0) {
    return (
      <div className="riq-panel p-8 text-center">
        <p className="text-sm" style={{ color: 'var(--riq-text-muted)' }}>No vehicles available for comparison.</p>
      </div>
    );
  }

  // Calculate costs
  const results = vehicles.map((v) => {
    const price = fuelPrices[v.fuel_type] ?? 0;
    let consumption = null;
    let cost = null;

    if (v.fuel_type === "ev") {
      const efficiency = v.ev_range;
      if (efficiency) {
        consumption = distance_km / efficiency;
        cost = consumption * price;
      }
    } else {
      let efficiency = null;
      if (selectedVehicleId === v.id && customEfficiency) {
        efficiency = customEfficiency;
      } else {
        efficiency = getEffectiveEfficiency(v);
      }
      if (efficiency) {
        consumption = distance_km / efficiency;
        cost = consumption * price;
      }
    }

    return { ...v, consumption, cost };
  });

  const bestMileage = [...results].sort((a, b) => a.consumption - b.consumption)[0];
  const cheapest = [...results].sort((a, b) => a.cost - b.cost)[0];

  const fuelTypes = ['petrol', 'diesel', 'cng', 'ev'];
  const bestPerFuelType = {};
  fuelTypes.forEach(fuel => {
    const matching = results.filter(v => (v.fuel_type || '').toLowerCase() === fuel);
    if (matching.length > 0) {
      bestPerFuelType[fuel] = matching.sort((a, b) => a.consumption - b.consumption)[0];
    }
  });

  const fuelGradients = {
    petrol: 'from-emerald-500/12 to-green-500/8',
    diesel: 'from-blue-500/12 to-indigo-500/8',
    cng: 'from-amber-500/12 to-yellow-500/8',
  };

  return (
    <div className="w-full">
      <h2 className="text-2xl font-extrabold mb-2" style={{ color: 'var(--riq-text)' }}>
        Fuel Comparison
      </h2>
      <p className="text-sm mb-5" style={{ color: 'var(--riq-text-muted)' }}>
        {results.length} vehicle option{results.length !== 1 ? "s" : ""} for {distance_km} km
      </p>

      {/* Best Mileage Per Fuel Type Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        {['petrol', 'diesel', 'cng'].filter(fuel => bestPerFuelType[fuel]).map(fuel => {
          const best = bestPerFuelType[fuel];
          const fuelLabel = fuel.charAt(0).toUpperCase() + fuel.slice(1);
          const gradient = fuelGradients[fuel] || fuelGradients.petrol;
          return (
            <div key={fuel} className={`relative overflow-hidden rounded-2xl border border-emerald-300/50 dark:border-emerald-600/40 bg-gradient-to-br from-emerald-50/70 to-green-50/50 dark:from-emerald-900/20 dark:to-green-900/10 p-5 shadow-sm`}>
              <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-green-500 opacity-70" />
              <div className="flex items-center gap-2 mb-2">
                <Award size={16} className="text-emerald-600 dark:text-emerald-400" />
                <h4 className="font-bold text-sm text-emerald-800 dark:text-emerald-300">Best {fuelLabel}</h4>
              </div>
              <p className="text-sm font-bold text-emerald-700 dark:text-emerald-200 mb-1">{best.name}</p>
              <p className="text-xl font-extrabold riq-gradient-text">
                {typeof best.cost === 'number' ? `₹${best.cost.toFixed(0)}` : '—'}
              </p>
              <p className="text-xs mt-1" style={{ color: 'var(--riq-text-faint)' }}>
                {typeof best.consumption === 'number' ? `${best.consumption.toFixed(1)} ${best.fuel_type === "cng" ? "kg" : "L"}` : 'N/A'}
              </p>
            </div>
          );
        })}
      </div>

      {/* Cheapest Overall Card */}
      <div className="relative overflow-hidden rounded-2xl border-2 border-amber-300/50 dark:border-amber-600/40 bg-gradient-to-br from-amber-50/70 to-yellow-50/50 dark:from-amber-900/15 dark:to-yellow-900/10 p-6 shadow-sm mb-6">
        <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 to-yellow-500 opacity-80" />
        <div className="flex items-center gap-2 mb-2">
          <PiggyBank size={20} className="text-amber-600 dark:text-amber-400" />
          <h4 className="font-bold text-base text-amber-800 dark:text-amber-200">Cheapest Overall Option</h4>
        </div>
        <p className="text-lg font-bold text-amber-700 dark:text-amber-200 mb-1">{cheapest.name}</p>
        <p className="text-2xl font-extrabold riq-gradient-text">
          {typeof cheapest.cost === 'number' ? `₹${cheapest.cost.toFixed(0)}` : '—'}
        </p>
        <p className="text-xs mt-1" style={{ color: 'var(--riq-text-faint)' }}>
          {typeof cheapest.consumption === 'number' ? `${cheapest.consumption.toFixed(2)} ${cheapest.fuel_type === "ev" ? "charges" : cheapest.fuel_type === "cng" ? "kg" : "L"} • ${cheapest.fuel_type.toUpperCase()}` : 'N/A'}
        </p>
      </div>

      {/* All Vehicle Cards */}
      <h3 className="text-lg font-bold mb-3" style={{ color: 'var(--riq-text)' }}>All Options</h3>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        {results.map((v) => {
          const isSelected = selectedVehicleId === v.id;
          const isBestMileage = v.id === bestMileage.id;
          const isCheapest = v.id === cheapest.id;

          return (
            <div
              key={v.id}
              className={`
                flex flex-col h-full p-5 rounded-2xl border transition-all duration-300
                ${isSelected
                  ? 'ring-2 ring-teal-500/60 border-teal-300/50 dark:border-teal-600/50 bg-teal-50/40 dark:bg-teal-900/15'
                  : 'border-slate-200/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-800/80 hover:shadow-lg hover:-translate-y-0.5'
                }
                backdrop-blur-sm shadow-sm
              `}
            >
              {/* Header + Badges */}
              <div className="flex flex-col gap-2 mb-3">
                <h3 className="font-bold text-base text-slate-800 dark:text-slate-100 whitespace-nowrap overflow-hidden text-ellipsis">
                  {v.name}
                </h3>
                <div className="flex flex-wrap gap-1.5">
                  {isBestMileage && (
                    <span className="inline-flex items-center px-2 py-0.5 text-[10px] rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-bold">
                      <Award size={10} className="mr-1" /> Best MPG
                    </span>
                  )}
                  {isCheapest && (
                    <span className="inline-flex items-center px-2 py-0.5 text-[10px] rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 font-bold">
                      <PiggyBank size={10} className="mr-1" /> Cheapest
                    </span>
                  )}
                </div>
              </div>

              {/* Content */}
              <div className="mt-1 text-sm space-y-2 flex-grow" style={{ color: 'var(--riq-text-muted)' }}>
                <p className="truncate">
                  <span className="font-semibold" style={{ color: 'var(--riq-text)' }}>Fuel:</span>{' '}
                  <span className="uppercase text-xs font-bold">{v.fuel_type}</span>
                </p>
                <p className="truncate">
                  <span className="font-semibold" style={{ color: 'var(--riq-text)' }}>Efficiency:</span>{' '}
                  {v.fuel_type === "ev"
                    ? (v.ev_range ? `${v.ev_range} km range` : "N/A")
                    : (() => {
                      const displayEfficiency = (selectedVehicleId === v.id && customEfficiency) ? customEfficiency : getEffectiveEfficiency(v);
                      return displayEfficiency ? `${displayEfficiency} km/${v.fuel_type === "cng" ? "kg" : "L"}${(selectedVehicleId === v.id && customEfficiency) ? " (custom)" : ""}` : "N/A";
                    })()
                  }
                </p>
                <p className="truncate">
                  <span className="font-semibold" style={{ color: 'var(--riq-text)' }}>Used:</span>{' '}
                  {typeof v.consumption === 'number' ? `${v.consumption.toFixed(2)} ${v.fuel_type === "ev" ? "charge" : v.fuel_type === "cng" ? "kg" : "L"}` : 'N/A'}
                </p>
                <p className="text-lg font-extrabold riq-gradient-text whitespace-nowrap">
                  {typeof v.cost === 'number' ? `₹${v.cost.toFixed(0)}` : '—'}
                </p>
              </div>

              {/* Select Button */}
              <button
                className={`
                  mt-4 w-full py-2.5 rounded-xl font-bold text-sm transition-all duration-200
                  ${isSelected
                    ? 'bg-gradient-to-r from-teal-600 to-sky-600 text-white shadow-md'
                    : 'bg-slate-100 dark:bg-slate-700/80 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'
                  }
                `}
                onClick={() => onSelect?.(v)}
              >
                {isSelected ? (
                  <span className="inline-flex items-center gap-1.5">
                    <CheckCircle size={14} /> Selected
                  </span>
                ) : 'Select'}
              </button>
            </div>
          );
        })}
      </div>
    </div>
  );
}
