import React, { useState, useEffect } from 'react';
import TripSummaryCard from '../components/TripSummaryCard';
import tripsAPI from '../api/trips';

export default function TripsListPage() {
  const [trips, setTrips] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchTrips();
  }, []);

  const fetchTrips = async () => {
    setLoading(true);
    try {
      const response = await tripsAPI.getTrips();
      setTrips(response.data || []);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to fetch trips');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-slate-600">Loading your trips...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-4xl font-bold text-slate-900 mb-2">Your Trips</h1>
        <p className="text-slate-600 mb-8">View and manage all your planned trips</p>

        {error && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-red-700">{error}</p>
          </div>
        )}

        {trips.length === 0 ? (
          <div className="text-center py-20">
            <div className="text-6xl mb-4">🗺️</div>
            <h2 className="text-2xl font-semibold text-slate-900 mb-2">No trips yet</h2>
            <p className="text-slate-600 mb-6">Start planning your first trip to get started</p>
            <a
              href="/plan"
              className="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
            >
              Plan a Trip
            </a>
          </div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {trips.map((trip) => (
              <TripSummaryCard
                key={trip.id}
                source={trip.source}
                destination={trip.destination}
                distance={trip.distance_km}
                duration={trip.duration_min}
                cost={trip.cost || 0}
                mode={trip.mode}
                date={trip.created_at}
                onView={() => alert(`Trip details for ${trip.source} to ${trip.destination}`)}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
