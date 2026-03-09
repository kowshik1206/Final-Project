import React from 'react';
import PropTypes from 'prop-types';
import { MapPin, Clock, ArrowRight } from 'lucide-react';

/**
 * PHASE 4: JourneyStageCard — Pure Renderer of Segment Data
 */
const segmentMeta = {
    road: {
        icon: '🚗',
        gradient: 'from-emerald-500 to-teal-500',
        bgGradient: 'from-emerald-50/80 to-teal-50/40 dark:from-emerald-900/15 dark:to-teal-900/10',
        borderColor: 'border-emerald-400/40 dark:border-emerald-600/40',
        accentBar: 'bg-gradient-to-b from-emerald-400 to-teal-500',
        iconBg: 'bg-emerald-100/80 dark:bg-emerald-900/30',
    },
    rail: {
        icon: '🚆',
        gradient: 'from-blue-500 to-indigo-500',
        bgGradient: 'from-blue-50/80 to-indigo-50/40 dark:from-blue-900/15 dark:to-indigo-900/10',
        borderColor: 'border-blue-400/40 dark:border-blue-600/40',
        accentBar: 'bg-gradient-to-b from-blue-400 to-indigo-500',
        iconBg: 'bg-blue-100/80 dark:bg-blue-900/30',
    },
    flight: {
        icon: '✈️',
        gradient: 'from-violet-500 to-purple-500',
        bgGradient: 'from-violet-50/80 to-purple-50/40 dark:from-violet-900/15 dark:to-purple-900/10',
        borderColor: 'border-violet-400/40 dark:border-violet-600/40',
        accentBar: 'bg-gradient-to-b from-violet-400 to-purple-500',
        iconBg: 'bg-violet-100/80 dark:bg-violet-900/30',
    },
};

export default function JourneyStageCard({ segment, index = 0 }) {
    if (!segment) return null;
    const meta = segmentMeta[segment.type] || segmentMeta.road;

    return (
        <div
            className={`
        group relative overflow-hidden rounded-2xl border
        ${meta.borderColor}
        bg-gradient-to-br ${meta.bgGradient}
        p-5 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5
        backdrop-blur-sm
      `}
        >
            {/* Left accent bar */}
            <div className={`absolute left-0 top-0 bottom-0 w-1 ${meta.accentBar} rounded-l-2xl`} />

            <div className="flex items-start gap-4 pl-2">
                {/* Icon */}
                <div className={`w-11 h-11 rounded-xl ${meta.iconBg} flex items-center justify-center text-xl border border-white/30 dark:border-slate-700/30 shadow-sm flex-shrink-0 group-hover:scale-105 transition-transform duration-300`}>
                    {meta.icon}
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                    {/* Stage Label */}
                    <h3 className="font-bold text-slate-800 dark:text-slate-100 mb-2 text-sm">
                        <span className="text-slate-400 dark:text-slate-500 font-semibold">Stage {index + 1}:</span>{' '}
                        {segment.label || 'Journey'}
                    </h3>

                    {/* Route */}
                    <div className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 mb-3 flex-wrap">
                        <span className="font-semibold">{segment.from_name || segment.from}</span>
                        <ArrowRight size={14} className="text-teal-500 dark:text-teal-400 flex-shrink-0" />
                        <span className="font-semibold">{segment.to_name || segment.to}</span>
                    </div>

                    {/* Stats */}
                    <div className="flex items-center gap-4 text-xs">
                        <div className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/60 dark:bg-slate-800/50 border border-slate-100/80 dark:border-slate-700/40">
                            <MapPin size={12} className="text-slate-400" />
                            <span className="font-bold text-slate-700 dark:text-slate-300">
                                {Number(segment.distance_km).toFixed(1)} km
                            </span>
                        </div>
                        <div className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/60 dark:bg-slate-800/50 border border-slate-100/80 dark:border-slate-700/40">
                            <Clock size={12} className="text-slate-400" />
                            <span className="font-bold text-slate-700 dark:text-slate-300">
                                {segment.duration_min >= 60
                                    ? `${Math.floor(segment.duration_min / 60)}h ${Math.round(segment.duration_min % 60)}m`
                                    : `${Math.round(segment.duration_min)}m`}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

JourneyStageCard.propTypes = {
    segment: PropTypes.shape({
        type: PropTypes.oneOf(['road', 'rail', 'flight']).isRequired,
        from: PropTypes.string.isRequired,
        to: PropTypes.string.isRequired,
        from_name: PropTypes.string,
        to_name: PropTypes.string,
        label: PropTypes.string,
        distance_km: PropTypes.number.isRequired,
        duration_min: PropTypes.number.isRequired,
    }).isRequired,
    index: PropTypes.number,
};
