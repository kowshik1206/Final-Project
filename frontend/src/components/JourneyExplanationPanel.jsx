import React from 'react';
import { formatCurrency, formatMinutes, formatKm } from '../utils/formatters';

/**
 * PHASE 5: Journey Explanation Panel
 * Displays detailed information about why each mode was chosen
 * and what assumptions were made (especially critical for Train/Flight)
 */

const ModeExplanations = {
  car: {
    icon: '🚗',
    title: 'Car',
    whyChosen: 'Most suitable for medium distances with direct point-to-point travel.',
    confidence: 'high',
    confidenceLabel: '🟢 High Confidence',
    confidenceDesc: 'Based on real-time routing data (OSRM)',
    assumptions: [
      'Direct highway/road route',
      'Includes fuel/charging stops',
      'Based on average fuel prices',
      'Includes toll charges (if applicable)'
    ]
  },
  ev: {
    icon: '🔋',
    title: 'Electric Vehicle',
    whyChosen: 'Eco-friendly option with lower running costs for medium distances.',
    confidence: 'high',
    confidenceLabel: '🟢 High Confidence',
    confidenceDesc: 'Based on real-time routing data (OSRM)',
    assumptions: [
      'Electric charging available at major stops',
      'Based on average electricity rates',
      'Charging time: ~30 min per stop',
      'Battery range: 300-400 km per charge'
    ]
  },
  bus: {
    icon: '🚌',
    title: 'Bus',
    whyChosen: 'Most economical option for group travel and long distances.',
    confidence: 'medium',
    confidenceLabel: '🟡 Estimated',
    confidenceDesc: 'Based on distance estimation (real booking data varies)',
    assumptions: [
      'Fixed route bus service',
      'Limited flexibility on timing',
      'Includes terminal/station charges',
      'Travel time includes stops and delays'
    ]
  },
  train: {
    icon: '🚂',
    title: 'Train',
    whyChosen: 'Most economical and fastest option for very long distances.',
    confidence: 'low',
    confidenceLabel: '🟠 Conceptual',
    confidenceDesc: '⚠️ Demo-grade estimation. Real quotes vary significantly.',
    assumptions: [
      'Straight-line distance used (actual route may differ)',
      'Express train assumed (local trains slower)',
      'Standard pricing tier (premium seats cost more)',
      'No delays or cancellations assumed',
      '🔴 IMPORTANT: Always verify with actual booking sites'
    ]
  },
  flight: {
    icon: '✈️',
    title: 'Flight',
    whyChosen: 'Fastest option for very long distances (>800 km).',
    confidence: 'low',
    confidenceLabel: '🟠 Conceptual',
    confidenceDesc: '⚠️ Demo-grade estimation. Real fares vary by date/airline.',
    assumptions: [
      'Assumes availability of connecting flights',
      'Airport transfers (taxi/cab) not included',
      'Luggage limits not considered',
      'Pricing changes dramatically by season',
      '🔴 IMPORTANT: Always verify with flight booking sites'
    ]
  }
};

export default function JourneyExplanationPanel({ mode, distance, duration, cost, source, destination }) {
  if (!mode) return null;

  const modeInfo = ModeExplanations[mode.toLowerCase()];
  if (!modeInfo) return null;

  return (
    <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
      {/* Header */}
      <div className="flex items-start gap-4 mb-6">
        <div className="text-4xl">{modeInfo.icon}</div>
        <div className="flex-1">
          <h2 className="text-2xl font-bold text-slate-900">{modeInfo.title}</h2>
          <p className="text-slate-600 mt-1">{modeInfo.whyChosen}</p>
        </div>
      </div>

      {/* Summary Row */}
      <div className="grid grid-cols-3 gap-4 mb-6 p-4 bg-slate-50 rounded-lg">
        <div>
          <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Distance</p>
          <p className="text-lg font-semibold text-slate-900">{formatKm(distance)}</p>
        </div>
        <div>
          <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Duration</p>
          <p className="text-lg font-semibold text-slate-900">{formatMinutes(duration)}</p>
        </div>
        <div>
          <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Estimated Cost</p>
          <p className="text-lg font-semibold text-blue-600">{formatCurrency(cost)}</p>
        </div>
      </div>

      {/* Confidence Indicator */}
      <div className={`mb-6 p-4 rounded-lg border-2 ${modeInfo.confidence === 'high'
        ? 'bg-green-50 border-green-300'
        : modeInfo.confidence === 'medium'
          ? 'bg-yellow-50 border-yellow-300'
          : 'bg-orange-50 border-orange-300'
        }`}>
        <div className="flex items-start gap-3">
          <div className="text-2xl pt-1">{modeInfo.confidenceLabel.split(' ')[0]}</div>
          <div className="flex-1">
            <h3 className="font-semibold text-slate-900 mb-1">{modeInfo.confidenceLabel}</h3>
            <p className="text-sm text-slate-700">{modeInfo.confidenceDesc}</p>
            {modeInfo.confidence !== 'high' && (
              <p className="text-xs text-slate-600 mt-2 italic">
                💡 We recommend verifying these details before finalizing your booking.
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Assumptions */}
      <div>
        <h3 className="font-semibold text-slate-900 mb-3">
          How We Calculated Your Trip to {destination || 'Your Destination'}
        </h3>
        <ul className="space-y-2">
          {modeInfo.assumptions.map((assumption, idx) => (
            <li key={idx} className="flex items-start gap-3 text-sm text-slate-700">
              <span className="text-slate-400 flex-shrink-0 mt-0.5">•</span>
              <span>{assumption}</span>
            </li>
          ))}
        </ul>
      </div>

      {/* Call-to-action for demo modes */}
      {['train', 'flight'].includes(mode.toLowerCase()) && (
        <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <p className="text-sm text-blue-900">
            <strong>📌 Next Step:</strong> Use this estimate to compare options. Always get real quotes from
            <span className="font-semibold">
              {mode.toLowerCase() === 'train' ? ' Indian Railways' : ' airline websites'}
            </span>
            {' '}before booking.
          </p>
        </div>
      )}
    </div>
  );
}

export { ModeExplanations };
