import React, { useState, useEffect } from 'react';
import tripsAPI from '../api/trips';
import { formatCurrency, formatKm } from '../utils/formatters';

export default function AnalyticsPage() {
  const [dashboard, setDashboard] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchDashboard();
  }, []);

  const fetchDashboard = async () => {
    try {
      const response = await tripsAPI.getDashboardSummary();
      setDashboard(response.data);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to fetch analytics');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-slate-600">Loading analytics...</p>
        </div>
      </div>
    );
  }

  const modeEmojis = {
    car: '🚗',
    ev: '🔋',
    train: '🚂',
    flight: '✈️',
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-4xl font-bold text-slate-900 mb-2">Analytics Dashboard</h1>
        <p className="text-slate-600 mb-8">Your travel and cost statistics</p>

        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-red-700">{error}</p>
          </div>
        )}

        {dashboard && (
          <div className="space-y-8">
            {/* Stats Cards */}
            <div className="grid md:grid-cols-2 lg:grid-cols-5 gap-4">
              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Total Trips</p>
                <p className="text-3xl font-bold text-blue-600">
                  {dashboard.total_trips || 0}
                </p>
              </div>

              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Total Distance</p>
                <p className="text-3xl font-bold text-green-600">
                  {formatKm(dashboard.total_distance_km || 0)}
                </p>
              </div>

              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Total Cost</p>
                <p className="text-3xl font-bold text-red-600">
                  {formatCurrency(dashboard.total_cost || 0)}
                </p>
              </div>

              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Most Used Mode</p>
                <p className="text-2xl font-bold text-slate-900">
                  {modeEmojis[dashboard.most_used_mode] || '🚗'} {dashboard.most_used_mode}
                </p>
              </div>

              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <p className="text-xs text-slate-500 uppercase tracking-wide mb-1">Avg Cost/km</p>
                <p className="text-3xl font-bold text-slate-900">
                  {formatCurrency(dashboard.avg_cost_per_km || 0)}
                </p>
              </div>
            </div>

            {/* Mode Usage */}
            {dashboard.mode_usage && dashboard.mode_usage.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 className="text-2xl font-semibold text-slate-900 mb-6">Transportation Mode Usage</h2>
                <div className="grid md:grid-cols-4 gap-4">
                  {dashboard.mode_usage.map((item) => (
                    <div key={item.mode} className="p-4 bg-slate-50 rounded-lg border border-slate-200">
                      <div className="text-3xl mb-2">{modeEmojis[item.mode] || '🚗'}</div>
                      <p className="text-sm font-medium text-slate-700 capitalize">{item.mode}</p>
                      <p className="text-2xl font-bold text-slate-900 mt-1">{item.count}</p>
                      <p className="text-xs text-slate-500 mt-1">trips</p>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Monthly Stats */}
            {dashboard.monthly_stats && dashboard.monthly_stats.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 className="text-2xl font-semibold text-slate-900 mb-6">Monthly Statistics</h2>
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead className="bg-slate-50 border-b border-slate-200">
                      <tr>
                        <th className="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                          Month
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                          Trips
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                          Distance
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                          Cost
                        </th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200">
                      {dashboard.monthly_stats.map((stat, idx) => (
                        <tr key={idx} className="hover:bg-slate-50">
                          <td className="px-4 py-3 text-sm font-medium text-slate-900">{stat.month}</td>
                          <td className="px-4 py-3 text-sm text-slate-600">{stat.trips}</td>
                          <td className="px-4 py-3 text-sm text-slate-600">
                            {formatKm(stat.distance_km)}
                          </td>
                          <td className="px-4 py-3 text-sm text-slate-600 font-medium">
                            {formatCurrency(stat.cost)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

