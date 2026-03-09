/**
 * AnalyticsHonestUI.jsx
 * Phase 6: Analytics Credibility
 * 
 * Honest analytics UI that shows trip statistics
 * All aggregation happens on backend, frontend is read-only
 */

import React, { useEffect, useState } from 'react';
import { BarChart3, TrendingUp, Loader2, DollarSign, MapPin, Activity } from 'lucide-react';
import axiosClient from '../api/axiosClient';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement } from 'chart.js';
import { Pie, Bar } from 'react-chartjs-2';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement);

export default function AnalyticsHonestUI() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [data, setData] = useState(null);

    // Confidence Filters (Backend enforced)
    const [includeEstimated, setIncludeEstimated] = useState(false);
    const [includeDemo, setIncludeDemo] = useState(false);

    useEffect(() => {
        fetchAnalytics();
    }, [includeEstimated, includeDemo]);

    const fetchAnalytics = async () => {
        setLoading(true);
        setError(null);
        try {
            const params = new URLSearchParams();
            if (includeEstimated) params.append('include_estimated', 'true');
            if (includeDemo) params.append('include_demo', 'true');

            const res = await axiosClient.get(`/analytics?${params.toString()}`);
            if (res.data.ok) {
                setData(res.data);
            } else {
                throw new Error(res.data.message || 'Failed to load analytics');
            }
        } catch (err) {
            console.error('Analytics Fetch Error:', err);
            setError('Could not load authoritative metrics.');
        } finally {
            setLoading(false);
        }
    };

    const getScopeLabel = () => {
        if (includeDemo) return '⚠️ Showing ALL data (including Demo)';
        if (includeEstimated) return '⚠️ Showing HIGH + ESTIMATED confidence';
        return '✅ Showing HIGH confidence trips only';
    };

    // Safe Math for UI display (Backend does heavy lifting, we just format)
    const modeShareData = data?.metrics?.mode_share || {};
    const pieData = {
        labels: Object.keys(modeShareData).map(k => k.toUpperCase()),
        datasets: [
            {
                data: Object.values(modeShareData),
                backgroundColor: [
                    '#3b82f6', // blue (car)
                    '#22c55e', // green (train)
                    '#f59e0b', // amber (bus)
                    '#6366f1', // indigo (ev)
                    '#ef4444', // red (flight)
                ],
                borderWidth: 1,
            },
        ],
    };

    return (
        <div className="riq-page-shell max-w-6xl mx-auto px-4 py-8 min-h-screen transition-colors">
            {/* Enhanced Header */}
            <div className="riq-fade-up mb-8 text-center">
                <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '1rem' }}>
                    <div style={{ width: '4rem', height: '4rem', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'linear-gradient(135deg, #6366f1, #8b5cf6)', borderRadius: 'var(--riq-radius-xl)', boxShadow: '0 8px 20px rgba(99, 102, 241, 0.3)', color: 'white' }}>
                        <BarChart3 size={28} />
                    </div>
                </div>
                <h1 style={{ fontSize: 'clamp(1.75rem, 3.5vw, 2.25rem)', fontWeight: 900, color: 'var(--riq-text)', margin: 0, lineHeight: 1.1 }}>
                    Trip <span className="riq-gradient-text">Analytics</span>
                </h1>
                <p style={{ fontSize: '1rem', color: 'var(--riq-text-muted)', margin: '0.5rem 0 0' }}>Authoritative insights based on your saved trips</p>
            </div>

            {/* Controls & Scope */}
            <div className="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 mb-8 flex flex-col md:flex-row justify-between items-center gap-4 transition-colors">
                <div className="flex items-center gap-2">
                    <span className={`px-3 py-1 rounded-full text-sm font-semibold ${includeDemo || includeEstimated ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300'
                        }`}>
                        {getScopeLabel()}
                    </span>
                </div>

                <div className="flex gap-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            checked={includeEstimated}
                            onChange={e => setIncludeEstimated(e.target.checked)}
                            className="rounded text-blue-600 dark:text-blue-400 focus:ring-blue-500"
                        />
                        Include Estimated (Train/Bus)
                    </label>
                    <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            checked={includeDemo}
                            onChange={e => setIncludeDemo(e.target.checked)}
                            className="rounded text-blue-600 dark:text-blue-400 focus:ring-blue-500"
                        />
                        Include Demo (Flight)
                    </label>
                </div>
            </div>

            {/* Loading State */}
            {loading && (
                <div className="text-center py-12">
                    <div className="flex items-center justify-center gap-2">
                        <Loader2 className="animate-spin" size={32} style={{ color: '#6366f1' }} />
                    </div>
                    <p className="text-lg font-semibold mt-4" style={{ color: 'var(--riq-text-muted)' }}>Verifying data authority...</p>
                </div>
            )}

            {/* Error State */}
            {!loading && error && (
                <div className="bg-red-50 dark:bg-red-900/20 p-6 rounded-lg border border-red-200 dark:border-red-800 text-center">
                    <p className="text-red-700 dark:text-red-300 font-semibold mb-2">Analytics Unavailable</p>
                    <p className="text-sm text-red-600 dark:text-red-400">{error}</p>
                </div>
            )}

            {/* Insufficient Data State */}
            {!loading && !error && data?.status === 'INSUFFICIENT_DATA' && (
                <div className="bg-blue-50 dark:bg-blue-900/20 p-8 rounded-lg border border-blue-200 dark:border-blue-800 text-center">
                    <h3 className="text-xl font-semibold text-blue-900 dark:text-blue-200 mb-2">Not enough data to draw conclusions yet.</h3>
                    <p className="text-blue-700 dark:text-blue-300 mb-4">{data.message}</p>
                    <p className="text-sm text-blue-600 dark:text-blue-400">
                        Please save at least 5 trips with {includeEstimated ? 'current settings' : 'HIGH confidence'} to unlock insights.
                    </p>
                </div>
            )}

            {/* Dashboard */}
            {!loading && !error && data?.metrics && (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {/* Key Metrics Cards */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 transition-colors">
                        <p className="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Avg Cost / KM</p>
                        <p className="text-3xl font-extrabold text-slate-900 dark:text-slate-100">₹{data.metrics.avg_cost_per_km}</p>
                        <p className="text-xs text-slate-400 dark:text-slate-500 mt-2">Weighted average across all modes</p>
                    </div>

                    <div className="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 transition-colors">
                        <p className="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Avg Trip Distance</p>
                        <p className="text-3xl font-extrabold text-slate-900 dark:text-slate-100">{data.metrics.avg_trip_distance} km</p>
                        <p className="text-xs text-slate-400 dark:text-slate-500 mt-2">Per saved journey</p>
                    </div>

                    <div className="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 transition-colors">
                        <p className="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Total Trips</p>
                        <p className="text-3xl font-extrabold text-slate-900 dark:text-slate-100">{data.metrics.total_trips}</p>
                        <p className="text-xs text-slate-400 dark:text-slate-500 mt-2">In current scope</p>
                    </div>

                    {/* Mode Share Chart */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 md:col-span-1 lg:col-span-1 flex flex-col items-center transition-colors">
                        <p className="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-4 w-full text-left">Mode Share</p>
                        <div className="w-40 h-40">
                            <Pie data={pieData} />
                        </div>
                    </div>
                </div>
            )}

            {/* Disclaimer (Mandatory) */}
            {!loading && (
                <div className="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                    <p className="mb-1"><strong>Data Authority:</strong> Analytics reflect saved trips only. Real-world traffic conditions may vary.</p>
                    <p>Train and Flight costs are estimated. Comparative results are not predictive of future pricing.</p>
                </div>
            )}
        </div>
    );
}
