import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { Bus, MapPin, Navigation, Sparkles, Save, Loader2, RadioTower, CircleDot, ChevronLeft } from 'lucide-react';
import MapView from '../components/MapView';
import JourneyStageCard from '../components/JourneyStageCard';
import BusStandInfoCard from '../components/BusStandInfoCard';
import { formatMinutes } from '../utils/formatters';
import axiosClient from '../api/axiosClient';
import tripsAPI from '../api/trips';
import poiAPI from '../api/poi';

/**
 * PHASE 4: BusJourneyPage - Pure Renderer
 * 
 * CRITICAL RULES:
 * ✓ Frontend accepts journeyData from backend /api/plan-route-bus
 * ✓ Frontend renders ONLY what backend provides
 * ✓ Frontend saves data VERBATIM (no reshaping)
 * ✓ No frontend calculations (distance, duration, polyline construction)
 * ✓ Single source of truth: journeyData from backend
 */
export default function BusJourneyPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [journeyData, setJourneyData] = useState(null);
  const [saving, setSaving] = useState(false);

  // POI/Stops Management
  const [activeTab, setActiveTab] = useState('auto'); // 'auto' (auto suggestions), 'temple', 'restaurant'
  const [stations, setStations] = useState([]);
  const [stationsLoading, setStationsLoading] = useState(false);
  const [pinnedStops, setPinnedStops] = useState([]);
  const [searchRadiusKm, setSearchRadiusKm] = useState(5);

  const source = searchParams.get('source');
  const destination = searchParams.get('destination');
  const cost = searchParams.get('cost');

  // Fetch POIs for temples and restaurants (MOVED BEFORE useEffect)
  const fetchPOIsForRoute = useCallback(async () => {
    if (!journeyData?.segments || journeyData.segments.length === 0) return;

    try {
      setStationsLoading(true);

      // Build polyline from all segments
      const polyline = [];
      journeyData.segments.forEach(seg => {
        if (seg.polyline && Array.isArray(seg.polyline)) {
          polyline.push(...seg.polyline);
        }
      });

      if (polyline.length === 0) {
        setStations([]);
        return;
      }

      // Determine categories based on active tab
      let categories = [];
      if (activeTab === 'temple') {
        categories = ['temple'];
      } else if (activeTab === 'restaurant') {
        categories = ['restaurant'];
      } else {
        // auto tab shows both temples and restaurants as suggestions
        categories = ['temple', 'restaurant'];
      }

      // Fetch POIs from backend
      const response = await poiAPI.getPoisForRoute({
        polyline,
        categories,
        radius_km: searchRadiusKm
      });

      if (response.ok && response.pois) {
        // Sort by distance and rating
        const sorted = response.pois.sort((a, b) => {
          const distDiff = (a.distance_to_route_m || 0) - (b.distance_to_route_m || 0);
          if (distDiff !== 0) return distDiff;
          return (b.rating || 0) - (a.rating || 0);
        });
        setStations(sorted);
      } else {
        setStations([]);
      }
    } catch (err) {
      console.error('Error fetching POIs:', err);
      setStations([]);
    } finally {
      setStationsLoading(false);
    }
  }, [journeyData, activeTab, searchRadiusKm]);

  const fetchBusJourney = async () => {
    try {
      setLoading(true);
      setError(null);

      // Call backend bus routing API - must return Phase 3 contract
      const response = await axiosClient.post('/plan-route-bus', {
        source,
        destination,
      });

      const data = response.data;

      if (!data.ok) {
        throw new Error(data.message || 'Bus journey planning failed');
      }

      // Store ENTIRE response from backend
      setJourneyData(data);
    } catch (err) {
      console.error('Bus route error:', err);
      setError(err.response?.data?.message || err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (!source || !destination) {
      setError('Missing source or destination');
      setLoading(false);
      return;
    }

    fetchBusJourney();
  }, [source, destination]);

  // Trigger POI fetch when journey data or filters change
  useEffect(() => {
    if (journeyData) {
      fetchPOIsForRoute();
    }
  }, [journeyData, activeTab, searchRadiusKm, fetchPOIsForRoute]);

  const handleSaveTrip = async () => {
    if (!journeyData) {
      alert('Journey data not loaded');
      return;
    }

    try {
      setSaving(true);

      // Save journey data EXACTLY as received from backend
      const tripData = {
        source: journeyData.source,
        destination: journeyData.destination,
        selected_mode: journeyData.mode,
        distance_km: journeyData.total_distance_km,
        duration_min: journeyData.total_duration_min,
        cost: cost ? parseFloat(cost) : journeyData.cost || 0,
        source_coords: journeyData.source_coords,
        destination_coords: journeyData.destination_coords,
        polyline: [], // Empty, segments contain polylines
        stops_json: JSON.stringify(pinnedStops),
        vehicle_json: JSON.stringify({}),
        segments: journeyData.segments, // Pass segments verbatim
      };

      await tripsAPI.saveTrip(tripData);
      alert('✅ Trip saved successfully!');
      navigate('/trips');
    } catch (err) {
      console.error('Save error:', err);
      alert('❌ Failed to save trip: ' + (err.response?.data?.message || err.message));
    } finally {
      setSaving(false);
    }
  };

  // Filter stations by active tab category
  const filteredStations = stations.filter(s => {
    if (activeTab === 'temple') {
      return s.category === 'temple';
    } else if (activeTab === 'restaurant') {
      return s.category === 'restaurant';
    }
    return true; // auto shows all
  });

  // Add stop to pinned stops
  function handleAddStop(poi) {
    const stop = {
      id: poi.id,
      name: poi.name,
      lat: poi.lat,
      lng: poi.lng,
      category: poi.category
    };
    setPinnedStops(prev => {
      const exists = prev.some(s => s.id === stop.id);
      if (exists) {
        alert('This stop is already pinned');
        return prev;
      }
      return [...prev, stop];
    });
  }

  // Remove stop from pinned stops
  function handleRemoveStop(poiId) {
    setPinnedStops(prev => prev.filter(s => s.id !== poiId));
  }

  const cardStyle = { background: 'var(--riq-surface)', border: '1px solid var(--riq-border)', borderRadius: '0.75rem', padding: '1.25rem', transition: 'all 0.2s' };
  const sectionTitle = { fontSize: '1rem', fontWeight: 700, color: 'var(--riq-text)', marginBottom: '0.75rem' };

  if (loading) {
    return (
      <div className="riq-page-shell min-h-screen transition-colors">
        <div style={{ maxWidth: '1280px', margin: '0 auto', padding: '1.5rem' }}>
          <div className="flex flex-col items-center justify-center min-h-[400px] gap-4">
            <div className="riq-journey-icon-lg">
              <Bus size={32} />
            </div>
            <div className="flex items-center gap-2">
              <Loader2 className="animate-spin" size={20} style={{ color: '#6366f1' }} />
              <p className="text-lg font-semibold" style={{ color: 'var(--riq-text)' }}>Planning your bus journey...</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="riq-page-shell min-h-screen transition-colors">
        <div style={{ maxWidth: '1280px', margin: '0 auto', padding: '1.5rem' }}>
          <div style={{ ...cardStyle, background: 'rgba(239,68,68,0.06)', borderColor: 'rgba(239,68,68,0.2)', textAlign: 'center', padding: '2rem' }}>
            <div style={{ fontSize: '2.5rem', marginBottom: '0.5rem' }}>⚠️</div>
            <h2 style={{ fontSize: '1.1rem', fontWeight: 700, color: '#dc2626', marginBottom: '0.5rem' }}>Error Planning Route</h2>
            <p style={{ color: '#b91c1c', fontSize: '0.9rem' }}>{error}</p>
            <button onClick={() => navigate('/plan')} className="riq-btn-primary" style={{ marginTop: '1rem' }}>Back to Planning</button>
          </div>
        </div>
      </div>
    );
  }

  if (!journeyData) return null;

  const { segments, total_distance_km, total_duration_min, source_coords, destination_coords } = journeyData;

  return (
    <div className="riq-page-shell min-h-screen transition-colors">
      <div style={{ maxWidth: '1280px', margin: '0 auto', padding: '1.5rem' }}>

        {/* ── Enhanced Header ── */}
        <div className="riq-journey-header riq-fade-up" style={{ marginBottom: '1.5rem' }}>
          <div className="riq-journey-icon-lg">
            <Bus size={32} />
          </div>
          <div className="flex-1">
            <button onClick={() => navigate('/plan')} className="flex items-center gap-1 text-sm font-medium hover:gap-2 transition-all" style={{ color: '#6366f1', background: 'none', border: 'none', cursor: 'pointer', padding: 0, marginBottom: '0.5rem' }}>
              <ChevronLeft size={16} /> Back to Planning
            </button>
            <h1 style={{ fontSize: 'clamp(1.75rem, 3.5vw, 2.25rem)', fontWeight: 900, color: 'var(--riq-text)', margin: 0, lineHeight: 1.1 }}>
              Bus <span className="riq-gradient-text">Journey</span>
            </h1>
            <div className="flex items-center gap-2 mt-2">
              <MapPin size={16} style={{ color: 'var(--riq-text-muted)' }} />
              <p style={{ fontSize: '1rem', color: 'var(--riq-text-muted)', margin: 0, fontWeight: 600 }}>{source} → {destination}</p>
            </div>
            <div className="flex gap-2 mt-3">
              <span className="riq-badge primary"><RadioTower size={12} /> Bus Route</span>
              <span className="riq-badge info"><CircleDot size={12} /> Terminal Info</span>
            </div>
          </div>
        </div>

        {/* ── KPI Strip ── */}
        <div className="riq-kpi-strip riq-fade-up">
          {[
            { label: 'Distance', value: `${total_distance_km.toFixed(1)} km`, accent: false },
            { label: 'Duration', value: formatMinutes(total_duration_min), accent: false },
            { label: 'Cost', value: `₹${cost ? Number(cost).toFixed(0) : '0'}`, accent: true },
          ].map((kpi, i) => (
            <div key={i} style={{ ...cardStyle, padding: '1rem 1.25rem' }}>
              <p style={{ fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--riq-text-muted)', marginBottom: '0.25rem' }}>{kpi.label}</p>
              <p style={{ fontSize: '1.5rem', fontWeight: 800, color: kpi.accent ? '#6366f1' : 'var(--riq-text)', margin: 0 }}>{kpi.value}</p>
            </div>
          ))}
        </div>

        {/* ── Two Column Layout ── */}
        <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">

          {/* Left Column – 3/5 */}
          <div className="lg:col-span-3 space-y-5">

            {/* Journey Stages */}
            <div className="riq-fade-up">
              <h2 style={sectionTitle}>Journey Stages</h2>
              <div className="space-y-3">
                {segments?.map((segment, index) => (
                  <JourneyStageCard key={index} segment={segment} index={index} />
                ))}
              </div>
            </div>

            {/* Bus Terminals */}
            <div className="riq-fade-up">
              <h2 style={sectionTitle}>Bus Terminals</h2>
              <div className="space-y-3">
                <BusStandInfoCard name={source + ' Bus Terminal'} city={source} type="interstate" facilities={['toilet', 'food', 'parking']} />
                <BusStandInfoCard name={destination + ' Bus Terminal'} city={destination} type="interstate" facilities={['toilet', 'food', 'water']} />
              </div>
            </div>

            {/* Temples & Restaurants */}
            <div className="riq-fade-up" style={cardStyle}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '0.75rem', marginBottom: '1rem' }}>
                <h2 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--riq-text)', margin: 0 }}>✨ Temples & Restaurants</h2>
                <div style={{ display: 'flex', background: 'var(--riq-surface-elevated)', padding: '3px', borderRadius: '0.5rem', border: '1px solid var(--riq-border)' }}>
                  {[{ key: 'auto', label: 'All' }, { key: 'temple', label: '🕉️ Temples' }, { key: 'restaurant', label: '🍽️ Restaurants' }].map(tab => (
                    <button key={tab.key} onClick={() => setActiveTab(tab.key)}
                      style={{ padding: '0.3rem 0.65rem', fontSize: '0.75rem', fontWeight: activeTab === tab.key ? 600 : 400, borderRadius: '0.375rem', border: 'none', cursor: 'pointer', transition: 'all 0.15s', background: activeTab === tab.key ? '#6366f1' : 'transparent', color: activeTab === tab.key ? '#fff' : 'var(--riq-text-muted)' }}>
                      {tab.label}
                    </button>
                  ))}
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: '0.35rem' }}>
                  <span style={{ fontSize: '0.7rem', color: 'var(--riq-text-muted)' }}>Radius</span>
                  <input type="number" min="1" max="100" value={searchRadiusKm} onChange={e => setSearchRadiusKm(Math.max(1, Number(e.target.value || 1)))}
                    style={{ width: '3.5rem', padding: '0.25rem 0.4rem', fontSize: '0.8rem', borderRadius: '0.375rem' }} />
                  <span style={{ fontSize: '0.7rem', color: 'var(--riq-text-muted)' }}>km</span>
                </div>
              </div>

              <div className="riq-poi-cols" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div>
                  <p style={{ fontSize: '0.8rem', color: 'var(--riq-text-muted)', marginBottom: '0.5rem' }}>
                    {stationsLoading ? <span className="flex items-center gap-2"><span className="animate-spin inline-block h-3 w-3 border-2 border-indigo-500 border-t-transparent rounded-full"></span> Loading…</span>
                      : <>Found <strong>{filteredStations.length}</strong> {activeTab === 'temple' ? 'temples' : activeTab === 'restaurant' ? 'restaurants' : 'places'}</>}
                  </p>
                  {filteredStations.length > 0 ? (
                    <div className="space-y-2" style={{ maxHeight: '20rem', overflowY: 'auto' }}>
                      {filteredStations.slice(0, 10).map((poi, i) => (
                        <div key={`${poi.id}-${i}`} style={{ ...cardStyle, padding: '0.65rem 0.85rem' }}>
                          <div className="flex items-start justify-between gap-2">
                            <div className="flex-1 min-w-0">
                              <div style={{ fontWeight: 600, fontSize: '0.85rem', color: 'var(--riq-text)' }} className="truncate">{poi.category === 'temple' ? '🕉️' : '🍽️'} {poi.name}</div>
                              <div style={{ fontSize: '0.7rem', color: 'var(--riq-text-muted)', marginTop: '2px' }}>{poi.category} • {poi.distance_to_route_m ? `${(poi.distance_to_route_m / 1000).toFixed(1)} km away` : ''}</div>
                              {poi.rating && <div style={{ fontSize: '0.7rem', color: '#d97706', marginTop: '2px' }}>⭐ {poi.rating.toFixed(1)}/5</div>}
                            </div>
                            <button onClick={() => handleAddStop(poi)} style={{ padding: '0.25rem 0.5rem', fontSize: '0.7rem', fontWeight: 600, background: '#10b981', color: '#fff', borderRadius: '0.375rem', border: 'none', cursor: 'pointer', whiteSpace: 'nowrap' }}>+ Add</button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p style={{ textAlign: 'center', padding: '1.5rem 0', color: 'var(--riq-text-muted)', fontSize: '0.8rem' }}>
                      {stationsLoading ? 'Loading…' : `No ${activeTab === 'temple' ? 'temples' : activeTab === 'restaurant' ? 'restaurants' : 'places'} found`}
                    </p>
                  )}
                </div>
                <div>
                  <p style={{ fontSize: '0.8rem', fontWeight: 600, color: 'var(--riq-text)', marginBottom: '0.5rem' }}>Pinned Stops ({pinnedStops.length})</p>
                  {pinnedStops.length > 0 ? (
                    <div className="space-y-2" style={{ maxHeight: '20rem', overflowY: 'auto' }}>
                      {pinnedStops.map((stop, i) => (
                        <div key={`${stop.id}-${i}`} style={{ ...cardStyle, background: 'rgba(16,185,129,0.06)', borderColor: 'rgba(16,185,129,0.2)', padding: '0.65rem 0.85rem' }}>
                          <div className="flex items-start justify-between gap-2">
                            <div className="flex-1 min-w-0">
                              <div style={{ fontWeight: 600, fontSize: '0.85rem', color: 'var(--riq-text)' }} className="truncate">{stop.category === 'temple' ? '🕉️' : '🍽️'} {stop.name}</div>
                              <div style={{ fontSize: '0.7rem', color: 'var(--riq-text-muted)', textTransform: 'capitalize', marginTop: '2px' }}>{stop.category}</div>
                            </div>
                            <button onClick={() => handleRemoveStop(stop.id)} style={{ padding: '0.25rem 0.5rem', fontSize: '0.7rem', fontWeight: 600, background: '#ef4444', color: '#fff', borderRadius: '0.375rem', border: 'none', cursor: 'pointer', whiteSpace: 'nowrap' }}>Remove</button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div style={{ textAlign: 'center', padding: '1.5rem', border: '1px dashed var(--riq-border)', borderRadius: '0.5rem', color: 'var(--riq-text-muted)', fontSize: '0.8rem' }}>No stops pinned yet. Select from suggestions.</div>
                  )}
                </div>
              </div>
            </div>

            {/* Save Trip */}
            <div className="riq-fade-up" style={{ ...cardStyle, background: 'rgba(99,102,241,0.04)', borderColor: 'rgba(99,102,241,0.15)' }}>
              <div className="flex items-center gap-2 mb-2">
                <Save size={18} style={{ color: '#6366f1' }} />
                <h3 style={{ fontWeight: 700, fontSize: '0.95rem', color: 'var(--riq-text)', margin: 0 }}>Save This Trip</h3>
              </div>
              <p style={{ fontSize: '0.8rem', color: 'var(--riq-text-muted)', marginBottom: '0.75rem' }}>Save this bus journey to your trip history with selected stops.</p>
              <button onClick={handleSaveTrip} disabled={saving} className="riq-btn-primary" style={{ width: '100%', justifyContent: 'center', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                {saving ? (
                  <><Loader2 className="animate-spin" size={18} /> Saving…</>
                ) : (
                  <><Save size={18} /> Save Trip</>
                )}
              </button>
            </div>

            {/* Travel Options */}
            <div className="riq-fade-up" style={{ ...cardStyle, background: 'rgba(239,68,68,0.04)', borderColor: 'rgba(239,68,68,0.15)' }}>
              <h3 style={{ fontWeight: 700, fontSize: '0.95rem', color: 'var(--riq-text)', marginBottom: '0.2rem' }}>Travel Options</h3>
              <p style={{ fontSize: '0.8rem', color: 'var(--riq-text-muted)', marginBottom: '0.75rem' }}>External bookings open in a new tab.</p>
              <a href="https://www.redbus.in" target="_blank" rel="noopener noreferrer"
                style={{ display: 'block', width: '100%', padding: '0.65rem 1rem', background: 'linear-gradient(135deg, #ef4444, #dc2626)', color: '#fff', fontWeight: 700, fontSize: '0.9rem', borderRadius: '0.6rem', border: 'none', textAlign: 'center', textDecoration: 'none', cursor: 'pointer' }}>
                🚌 Book Bus Ticket →
              </a>
              <p style={{ fontSize: '0.65rem', color: 'var(--riq-text-muted)', marginTop: '0.75rem', lineHeight: 1.5 }}>⚠️ Prices and schedules may change. Always verify details before booking.</p>
            </div>
          </div>

          {/* Right Column – 2/5: Map */}
          <div className="lg:col-span-2 riq-fade-up">
            <h2 style={sectionTitle}>Route Map</h2>
            <div style={{ ...cardStyle, padding: 0, overflow: 'hidden' }}>
              <MapView center={source_coords} zoom={6} routeSegments={segments} markers={[{ ...source_coords, type: 'source', content: source }, { ...destination_coords, type: 'destination', content: destination }]} />
            </div>
            <div style={{ ...cardStyle, marginTop: '0.5rem', padding: '0.75rem 1rem', fontSize: '0.8rem', color: 'var(--riq-text-muted)' }}>
              <strong style={{ color: 'var(--riq-text)' }}>Legend:</strong>
              <ul style={{ marginTop: '0.35rem', listStyle: 'none', padding: 0 }}>
                <li className="flex items-center gap-2">
                  <span style={{ width: '1.5rem', height: '2px', background: '#16a34a', display: 'inline-block', borderRadius: '1px' }}></span>
                  <span>Bus Route (solid green)</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
