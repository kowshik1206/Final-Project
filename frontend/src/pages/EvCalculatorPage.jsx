import React, { useState, useEffect } from 'react';
import { useSearchParams, useLocation } from 'react-router-dom';
import { Zap, Battery, TrendingUp, Sparkles, Calculator } from 'lucide-react';
import axiosClient from '../api/axiosClient';

export default function EvCalculatorPage() {
  const [searchParams] = useSearchParams();
  const location = useLocation();

  // Get distance from route state or query params
  // Use explicit null check to handle edge case where distance = 0
  const routeDistance = location.state?.trip?.distance_km;
  const queryDistance = searchParams.get('distance');
  const distanceFromRoute = routeDistance != null ? routeDistance : Number(queryDistance);

  const [distanceKm, setDistanceKm] = useState(distanceFromRoute || '');
  const [batteryKwh, setBatteryKwh] = useState('');
  const [whPerKm, setWhPerKm] = useState('');
  const [costPerKwh, setCostPerKwh] = useState('');
  const [currentCharge, setCurrentCharge] = useState(100);

  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleCalculate = async () => {
    setError(null);
    setResult(null);
    setLoading(true);

    try {
      const res = await axiosClient.post('/ev/calculate', {
        distance_km: Number(distanceKm),
        battery_kwh: Number(batteryKwh),
        consumption_wh_per_km: Number(whPerKm),
        cost_per_kwh: Number(costPerKwh),
        current_charge_percent: Number(currentCharge)
      });

      if (!res.data.ok) {
        setError(res.data.error || 'Calculation failed');
        setLoading(false);
        return;
      }

      setResult(res.data);
    } catch (err) {
      console.error('EV calculation error:', err);
      setError(err.response?.data?.error || 'Backend error while calculating EV trip');
    } finally {
      setLoading(false);
    }
  };

  const handleReset = () => {
    setDistanceKm('');
    setBatteryKwh('');
    setWhPerKm('');
    setCostPerKwh('');
    setCurrentCharge(100);
    setResult(null);
    setError(null);
  };

  const cardStyle = { background: 'var(--riq-surface)', border: '1px solid var(--riq-border)', borderRadius: '0.75rem', padding: '1.25rem', transition: 'all 0.2s' };

  return (
    <div className="riq-page-shell min-h-screen transition-colors">
      <div style={{ maxWidth: '680px', margin: '0 auto', padding: '1.5rem' }}>

        {/* ── Enhanced Header ── */}
        <div className="riq-fade-up" style={{ textAlign: 'center', marginBottom: '2rem' }}>
          <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '1rem' }}>
            <div style={{ width: '4rem', height: '4rem', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'linear-gradient(135deg, #10b981, #14b8a6)', borderRadius: 'var(--riq-radius-xl)', boxShadow: '0 8px 20px rgba(16, 185, 129, 0.3)', color: 'white' }}>
              <Zap size={28} />
            </div>
          </div>
          <h1 style={{ fontSize: 'clamp(1.75rem, 3.5vw, 2.25rem)', fontWeight: 900, color: 'var(--riq-text)', margin: 0, lineHeight: 1.1 }}>
            EV Trip <span className="riq-gradient-text">Calculator</span>
          </h1>
          <p style={{ fontSize: '1rem', color: 'var(--riq-text-muted)', margin: '0.5rem 0 0' }}>Calculate your electric vehicle trip cost and range</p>
          <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'center', marginTop: '1rem' }}>
            <span className="riq-badge success"><Battery size={12} /> Range Analysis</span>
            <span className="riq-badge info"><TrendingUp size={12} /> Cost Estimate</span>
          </div>
        </div>

        {/* ── Main Input Card ── */}
        <div className="riq-fade-up" style={{ ...cardStyle, padding: '1.5rem', marginBottom: '1.25rem' }}>
          <h2 style={{ fontSize: '1.1rem', fontWeight: 700, color: 'var(--riq-text)', marginBottom: '1rem' }}>Trip Parameters</h2>

          {error && (
            <div style={{ marginBottom: '1rem', padding: '0.65rem 0.85rem', background: 'rgba(239,68,68,0.06)', border: '1px solid rgba(239,68,68,0.2)', borderRadius: '0.5rem' }}>
              <p style={{ color: '#dc2626', fontSize: '0.85rem', fontWeight: 600, margin: 0 }}>❌ {error}</p>
            </div>
          )}

          <div className="riq-ev-grid" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', marginBottom: '1rem' }}>
            {/* Distance */}
            <div>
              <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 600, color: 'var(--riq-text-muted)', marginBottom: '0.35rem' }}>📏 Distance (km)</label>
              {distanceKm ? (
                <div style={{ padding: '0.5rem 0.75rem', background: 'rgba(99,102,241,0.06)', border: '1px solid rgba(99,102,241,0.2)', borderRadius: '0.5rem', fontWeight: 700, fontSize: '0.9rem', color: '#6366f1' }}>
                  {Number(distanceKm).toFixed(1)} km
                </div>
              ) : (
                <input type="number" min="0" step="0.1" value={distanceKm} onChange={(e) => setDistanceKm(e.target.value)} placeholder="e.g., 420" style={{ width: '100%' }} />
              )}
            </div>

            {/* Battery */}
            <div>
              <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 600, color: 'var(--riq-text-muted)', marginBottom: '0.35rem' }}>🔋 Battery (kWh)</label>
              <input type="number" min="0" step="0.1" value={batteryKwh} onChange={(e) => setBatteryKwh(e.target.value)} placeholder="e.g., 40" style={{ width: '100%' }} />
            </div>

            {/* Consumption */}
            <div>
              <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 600, color: 'var(--riq-text-muted)', marginBottom: '0.35rem' }}>⚙️ Consumption (Wh/km)</label>
              <input type="number" min="0" step="1" value={whPerKm} onChange={(e) => setWhPerKm(e.target.value)} placeholder="e.g., 150" style={{ width: '100%' }} />
            </div>

            {/* Cost per kWh */}
            <div>
              <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 600, color: 'var(--riq-text-muted)', marginBottom: '0.35rem' }}>💰 Cost/kWh (₹)</label>
              <input type="number" min="0" step="0.1" value={costPerKwh} onChange={(e) => setCostPerKwh(e.target.value)} placeholder="e.g., 8" style={{ width: '100%' }} />
            </div>
          </div>

          {/* Current Charge Slider */}
          <div style={{ marginBottom: '1.25rem' }}>
            <label style={{ display: 'block', fontSize: '0.75rem', fontWeight: 600, color: 'var(--riq-text-muted)', marginBottom: '0.35rem' }}>
              Current Charge: <span style={{ color: '#6366f1', fontWeight: 800 }}>{currentCharge}%</span>
            </label>
            <input type="range" min="0" max="100" step="5" value={currentCharge} onChange={(e) => setCurrentCharge(Number(e.target.value))}
              style={{ width: '100%', height: '6px', borderRadius: '4px', appearance: 'none', background: `linear-gradient(to right, #6366f1 ${currentCharge}%, var(--riq-border) ${currentCharge}%)`, cursor: 'pointer' }} />
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.65rem', color: 'var(--riq-text-muted)', marginTop: '0.2rem' }}>
              <span>0%</span><span>50%</span><span>100%</span>
            </div>
          </div>

          {/* Buttons */}
          <div style={{ display: 'flex', gap: '0.65rem' }}>
            <button onClick={handleCalculate} disabled={loading || !distanceKm || !batteryKwh || !whPerKm || !costPerKwh}
              className="riq-btn-primary" style={{ flex: 1, justifyContent: 'center' }}>
              {loading ? '⏳ Calculating…' : '📊 Calculate Trip'}
            </button>
            <button onClick={handleReset}
              style={{ padding: '0.55rem 1rem', background: 'var(--riq-surface-elevated)', border: '1px solid var(--riq-border)', borderRadius: '0.5rem', fontWeight: 600, fontSize: '0.85rem', color: 'var(--riq-text)', cursor: 'pointer', transition: 'all 0.15s' }}>
              🔄 Reset
            </button>
          </div>
        </div>

        {/* ── Results Card ── */}
        {result && (
          <div className="riq-fade-up" style={{ ...cardStyle, padding: '1.5rem', background: 'rgba(16,185,129,0.03)', borderColor: 'rgba(16,185,129,0.2)', marginBottom: '1.25rem' }}>
            <h2 style={{ fontSize: '1.1rem', fontWeight: 700, color: 'var(--riq-text)', marginBottom: '1rem' }}>✅ Trip Results</h2>

            <div className="riq-ev-grid" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', marginBottom: '1rem' }}>
              {[
                { label: 'Energy Consumed', value: `${result.energy_consumed_kwh} kWh`, accent: '#6366f1', border: '#6366f1' },
                { label: 'Estimated Cost', value: `₹${result.estimated_cost}`, accent: '#10b981', border: '#10b981' },
                { label: 'Range on Charge', value: `${result.estimated_range_km} km`, accent: '#8b5cf6', border: '#8b5cf6' },
                { label: 'Charging Stops', value: `${result.charging_stops}`, accent: '#f59e0b', border: '#f59e0b' },
              ].map((item, i) => (
                <div key={i} style={{ ...cardStyle, borderLeft: `3px solid ${item.border}`, padding: '0.85rem 1rem' }}>
                  <p style={{ fontSize: '0.6rem', textTransform: 'uppercase', letterSpacing: '0.05em', fontWeight: 600, color: 'var(--riq-text-muted)', marginBottom: '0.2rem' }}>{item.label}</p>
                  <p style={{ fontSize: '1.35rem', fontWeight: 800, color: item.accent, margin: 0 }}>{item.value}</p>
                </div>
              ))}
            </div>

            {/* Summary */}
            <div style={{ padding: '0.75rem 1rem', background: 'rgba(99,102,241,0.05)', border: '1px solid rgba(99,102,241,0.15)', borderRadius: '0.5rem', marginBottom: '1rem' }}>
              <p style={{ fontSize: '0.8rem', color: 'var(--riq-text)', lineHeight: 1.6, margin: 0 }}>
                <strong>📌 Summary:</strong> Your EV needs {result.energy_consumed_kwh} kWh to cover {result.distance_km} km,
                costing approximately ₹{result.estimated_cost}. With {currentCharge}% charge, you can drive {result.estimated_range_km} km
                before needing {result.charging_stops} charging stop{result.charging_stops !== 1 ? 's' : ''}.
              </p>
            </div>

            {/* Details Table */}
            <div style={{ borderRadius: '0.5rem', overflow: 'hidden', border: '1px solid var(--riq-border)' }}>
              {[
                { label: 'Distance', value: `${result.distance_km} km` },
                { label: 'Battery Capacity', value: `${result.battery_kwh} kWh` },
                { label: 'Consumption Rate', value: `${result.consumption_wh_per_km} Wh/km` },
                { label: 'Electricity Cost', value: `₹${result.cost_per_kwh}/kWh` },
                { label: 'Current Charge', value: `${result.current_charge_percent}%` },
              ].map((row, i) => (
                <div key={i} style={{ display: 'flex', justifyContent: 'space-between', padding: '0.55rem 0.85rem', fontSize: '0.8rem', background: i % 2 === 0 ? 'var(--riq-surface-elevated)' : 'transparent', borderBottom: i < 4 ? '1px solid var(--riq-border)' : 'none' }}>
                  <span style={{ fontWeight: 600, color: 'var(--riq-text-muted)' }}>{row.label}</span>
                  <span style={{ fontWeight: 700, color: 'var(--riq-text)' }}>{row.value}</span>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* ── Info Footer ── */}
        <div className="riq-fade-up" style={{ ...cardStyle, background: 'rgba(99,102,241,0.04)', borderColor: 'rgba(99,102,241,0.15)', padding: '1rem' }}>
          <p style={{ fontSize: '0.8rem', color: 'var(--riq-text)', margin: 0, lineHeight: 1.6 }}>
            <strong>💡 How it works:</strong> This calculator estimates your EV trip costs and charging stops based on battery capacity, consumption rate, and electricity prices.
          </p>
          <p style={{ fontSize: '0.8rem', color: 'var(--riq-text-muted)', margin: '0.5rem 0 0', lineHeight: 1.6 }}>
            <strong>⚡ Note:</strong> These are estimates. Actual consumption may vary based on driving conditions, weather, and terrain.
          </p>
        </div>
      </div>
    </div>
  );
}
