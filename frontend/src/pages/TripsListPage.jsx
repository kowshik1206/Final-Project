import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Loader2, FolderOpen, BarChart3, Activity, PlusCircle } from 'lucide-react';
import TripSummaryCard from '../components/TripSummaryCard';
import tripsAPI from '../api/trips';

export default function TripsListPage() {
  const [trips, setTrips] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  useEffect(() => {
    fetchTrips();
  }, []);

  const fetchTrips = async () => {
    setLoading(true);
    try {
      const response = await tripsAPI.getTrips();
      setTrips(response.data.trips || []);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to fetch trips');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen riq-page-shell flex items-center justify-center transition-colors">
        <div className="text-center riq-fade-up">
          <Loader2 size={32} className="riq-spin text-teal-500 dark:text-teal-400 mx-auto" />
          <p className="mt-4 font-medium" style={{ color: 'var(--riq-text-muted)' }}>Loading travel history...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen riq-page-shell transition-colors pt-16">
      <div className="max-w-[1400px] mx-auto px-6 sm:px-8 lg:px-12 py-10 relative z-10">
        {/* Hero Strip */}
        <section className="riq-hero-strip riq-fade-up p-10 sm:p-12 mb-10" style={{
          background: 'linear-gradient(135deg, rgba(99,102,241,0.08), rgba(20,184,166,0.06))',
          boxShadow: '0 4px 24px rgba(0,0,0,0.06)'
        }}>
          <div className="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
              <div className="flex items-center gap-3 mb-4">
                <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-500 to-sky-500 flex items-center justify-center" style={{
                  boxShadow: '0 8px 24px rgba(20,184,166,0.3)'
                }}>
                  <FolderOpen size={32} className="text-white" />
                </div>
                <div>
                  <div style={{
                    display: 'inline-flex', alignItems: 'center', gap: '0.5rem',
                    padding: '0.4rem 1rem', borderRadius: '1.5rem',
                    background: 'rgba(20,184,166,0.1)', border: '1px solid rgba(20,184,166,0.2)',
                    marginBottom: '0.5rem'
                  }}>
                    <span style={{ fontSize: '1rem' }}>📋</span>
                    <span style={{ fontSize: '0.85rem', fontWeight: 700, color: '#14b8a6' }}>Trip Management</span>
                  </div>
                </div>
              </div>
              <h1 className="font-black" style={{ color: 'var(--riq-text)', fontSize: 'clamp(2rem, 3.5vw, 3rem)', lineHeight: 1.2 }}>
                🧭 Your RouteIQ <span className="riq-gradient-text">Trips</span>
              </h1>
              <p className="mt-4 text-xl" style={{ color: 'var(--riq-text-muted)', maxWidth: '700px', lineHeight: 1.7 }}>
                🗺️ Reopen, compare, and continue journeys from one place. Your travel history at your fingertips.
              </p>
            </div>

            {/* Stats */}
            <div className="riq-quick-stats w-full lg:w-auto" style={{ gap: '1.5rem' }}>
              <div className="riq-stat-tile" style={{ padding: '1.5rem', minWidth: '140px' }}>
                <div style={{
                  width: '3rem', height: '3rem', borderRadius: '1rem',
                  background: 'linear-gradient(135deg, #14b8a6, #10b981)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  margin: '0 auto 0.75rem',
                  boxShadow: '0 4px 12px rgba(20,184,166,0.25)'
                }}>
                  <FolderOpen size={20} className="text-white" />
                </div>
                <p className="text-xs uppercase tracking-wider font-extrabold" style={{ color: 'var(--riq-text-faint)' }}>Saved</p>
                <p className="text-3xl font-black" style={{ color: 'var(--riq-text)' }}>{trips.length}</p>
              </div>
              <div className="riq-stat-tile" style={{ padding: '1.5rem', minWidth: '140px' }}>
                <div style={{
                  width: '3rem', height: '3rem', borderRadius: '1rem',
                  background: 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  margin: '0 auto 0.75rem',
                  boxShadow: '0 4px 12px rgba(14,165,233,0.25)'
                }}>
                  <BarChart3 size={20} className="text-white" />
                </div>
                <p className="text-xs uppercase tracking-wider font-extrabold" style={{ color: 'var(--riq-text-faint)' }}>Modes</p>
                <p className="text-3xl font-black" style={{ color: 'var(--riq-text)' }}>
                  {new Set(trips.map((t) => t.selected_mode || t.mode).filter(Boolean)).size}
                </p>
              </div>
              <div className="riq-stat-tile" style={{ padding: '1.5rem', minWidth: '140px' }}>
                <div style={{
                  width: '3rem', height: '3rem', borderRadius: '1rem',
                  background: 'linear-gradient(135deg, #10b981, #059669)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  margin: '0 auto 0.75rem',
                  boxShadow: '0 4px 12px rgba(16,185,129,0.25)'
                }}>
                  <Activity size={20} className="text-white" />
                </div>
                <p className="text-xs uppercase tracking-wider font-extrabold" style={{ color: 'var(--riq-text-faint)' }}>Status</p>
                <p className="text-lg font-black text-teal-600 dark:text-teal-400">✅ Ready</p>
              </div>
            </div>
          </div>
        </section>

        {/* Error */}
        {error && (
          <div className="mb-6 p-4 bg-red-50/70 dark:bg-red-900/15 border border-red-200/60 dark:border-red-800/40 rounded-xl riq-fade-up">
            <p className="text-red-700 dark:text-red-300 font-medium">{error}</p>
          </div>
        )}

        {/* Content */}
        {trips.length === 0 ? (
          <div className="riq-panel text-center py-28 px-8 riq-fade-up" style={{ 
            background: 'linear-gradient(135deg, var(--riq-surface-elevated), var(--riq-surface))',
            boxShadow: '0 4px 20px rgba(0,0,0,0.06)'
          }}>
            <div className="riq-empty-state-icon" style={{ fontSize: '5rem' }}>🧭</div>
            <h2 className="text-4xl font-black mb-4" style={{ color: 'var(--riq-text)' }}>No trips saved yet</h2>
            <p className="text-xl mb-10" style={{ color: 'var(--riq-text-muted)', maxWidth: '600px', margin: '0 auto 2.5rem', lineHeight: 1.7 }}>
              🚀 Plan your first route and it will appear here. Start exploring the world with RouteIQ!
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a href="/plan" className="riq-btn-primary inline-flex items-center gap-2.5 px-8 py-4 text-lg font-bold" style={{
                boxShadow: '0 6px 20px rgba(99,102,241,0.3)'
              }}>
                <PlusCircle size={22} />
                📍 Plan a Trip
              </a>
              <a href="/multi-stop" className="riq-btn-secondary inline-flex items-center gap-2 px-6 py-3">
                Multi-Stop Route
              </a>
            </div>
          </div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {trips.map((trip, index) => (
              <div key={trip.id} className={`riq-fade-up riq-stagger-${Math.min(index + 1, 5)}`}>
                <TripSummaryCard
                  source={trip.source_name || trip.source}
                  destination={trip.dest_name || trip.destination}
                  distance={trip.distance_km}
                  duration={trip.duration_min}
                  cost={parseFloat(trip.cost_amount) || 0}
                  mode={trip.selected_mode || trip.mode}
                  date={trip.created_at}
                  onView={() => navigate(`/trips/${trip.id}`)}
                  vehicleName={(() => {
                    try {
                      const v = typeof trip.vehicle_json === 'string' ? JSON.parse(trip.vehicle_json) : trip.vehicle_json;
                      return v?.name;
                    } catch (e) { return null; }
                  })()}
                  stopsCount={(() => {
                    try {
                      const s = typeof trip.stops_json === 'string' ? JSON.parse(trip.stops_json) : trip.stops_json;
                      return Array.isArray(s) ? s.length : 0;
                    } catch (e) { return 0; }
                  })()}
                />
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
