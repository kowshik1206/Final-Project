// src/components/StationsList.jsx
import React from 'react';

export default function StationsList({
  stations = [],
  onFocus = () => {},
  onAddStop = () => {},
  loading = false
}) {
  if (loading) {
    return (
      <div className="p-4 text-center text-slate-600">
        <div className="inline-block animate-spin">⌛</div> Loading stations…
      </div>
    );
  }

  if (!stations.length) {
    return (
      <div className="p-4 text-center text-slate-600">
        No stations found nearby. Try increasing the search radius.
      </div>
    );
  }

  return (
    <div className="space-y-2">
      {stations.map((s, idx) => (
        <div
          key={s.id}
          className="flex items-start gap-3 p-3 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-100 transition"
        >
          <div className="w-8 h-8 flex items-center justify-center rounded-full bg-blue-600 text-white font-bold text-sm flex-shrink-0">
            {idx + 1}
          </div>
          <div className="flex-1 min-w-0">
            <div className="font-semibold text-slate-900 truncate">{s.name}</div>
            <div className="text-xs text-slate-500 mt-1">
              {s.category} • {s.distance_to_route_m ? `${(s.distance_to_route_m / 1000).toFixed(1)} km` : '—'} • Score {Math.round((s.score ?? 0) * 100)}%
            </div>
          </div>
          <div className="flex flex-col items-end gap-1 flex-shrink-0">
            <button
              onClick={() => onAddStop(s)}
              className="px-3 py-1 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700 transition whitespace-nowrap"
              title="Add this stop to itinerary"
            >
              + Add
            </button>
            <button
              onClick={() => onFocus(s)}
              className="px-3 py-1 bg-gray-200 text-gray-700 rounded text-xs font-medium hover:bg-gray-300 transition whitespace-nowrap"
              title="Center map on this station"
            >
              Focus
            </button>
          </div>
        </div>
      ))}
    </div>
  );
}
