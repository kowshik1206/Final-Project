import React from 'react';
import PropTypes from 'prop-types';

/**
 * ModeSelector — Premium travel mode selection component
 */

const modeGradients = {
    car: 'from-emerald-500/15 to-teal-500/10',
    train: 'from-blue-500/15 to-indigo-500/10',
    flight: 'from-violet-500/15 to-purple-500/10',
};

const modeAccents = {
    car: { border: 'border-emerald-400/60 dark:border-emerald-500/50', ring: 'ring-emerald-300/40 dark:ring-emerald-500/30', text: 'text-emerald-700 dark:text-emerald-300', desc: 'text-emerald-600 dark:text-emerald-400' },
    train: { border: 'border-blue-400/60 dark:border-blue-500/50', ring: 'ring-blue-300/40 dark:ring-blue-500/30', text: 'text-blue-700 dark:text-blue-300', desc: 'text-blue-600 dark:text-blue-400' },
    flight: { border: 'border-violet-400/60 dark:border-violet-500/50', ring: 'ring-violet-300/40 dark:ring-violet-500/30', text: 'text-violet-700 dark:text-violet-300', desc: 'text-violet-600 dark:text-violet-400' },
};

export default function ModeSelector({ selectedMode, onModeChange, className = '' }) {
    const modes = [
        { id: 'car', label: 'Car', icon: '🚗', description: 'Road routing only' },
        { id: 'train', label: 'Train', icon: '🚆', description: 'Rail + road routing' },
        { id: 'flight', label: 'Flight', icon: '✈️', description: 'Air + road routing' },
    ];

    return (
        <div className={`mode-selector ${className}`}>
            <div className="flex gap-3">
                {modes.map((mode) => {
                    const isSelected = selectedMode === mode.id;
                    const accent = modeAccents[mode.id];
                    const gradient = modeGradients[mode.id];

                    return (
                        <button
                            key={mode.id}
                            type="button"
                            onClick={() => onModeChange(mode.id)}
                            className={`
                flex-1 px-4 py-3.5 rounded-2xl border-2 transition-all duration-300
                backdrop-blur-sm cursor-pointer
                ${isSelected
                                    ? `${accent.border} bg-gradient-to-br ${gradient} shadow-md ring-2 ${accent.ring}`
                                    : 'border-slate-200/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-sm'
                                }
              `}
                            aria-pressed={isSelected}
                            aria-label={`Select ${mode.label} mode`}
                        >
                            <div className="flex flex-col items-center gap-1.5">
                                <div className={`w-10 h-10 rounded-xl bg-gradient-to-br ${gradient} flex items-center justify-center text-2xl border border-white/20 dark:border-slate-700/30 ${isSelected ? 'shadow-sm scale-110' : ''} transition-transform duration-300`}>
                                    {mode.icon}
                                </div>
                                <span className={`text-sm font-bold ${isSelected ? accent.text : 'text-slate-700 dark:text-slate-300'}`}>
                                    {mode.label}
                                </span>
                                <span className={`text-xs font-medium ${isSelected ? accent.desc : 'text-slate-500 dark:text-slate-400'}`}>
                                    {mode.description}
                                </span>
                            </div>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

ModeSelector.propTypes = {
    selectedMode: PropTypes.oneOf(['car', 'train', 'flight']).isRequired,
    onModeChange: PropTypes.func.isRequired,
    className: PropTypes.string,
};
