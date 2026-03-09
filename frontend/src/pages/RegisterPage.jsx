import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { validateEmail } from '../utils/helpers';

export default function RegisterPage() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { register } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!name || !email || !password || !confirmPassword) {
      setError('Please fill in all fields');
      return;
    }

    if (!validateEmail(email)) {
      setError('Please enter a valid email');
      return;
    }

    if (password.length < 6) {
      setError('Password must be at least 6 characters');
      return;
    }

    if (password !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    setLoading(true);
    const result = await register(name, email, password);
    setLoading(false);

    if (result.success) {
      navigate('/plan');
    } else {
      setError(result.message);
    }
  };

  return (
    <div className="riq-page-shell fixed inset-0 flex items-center justify-center" style={{
      background: 'var(--riq-bg)',
      overflow: 'hidden',
      height: '100vh',
      width: '100vw'
    }}>
      {/* Animated Background Gradient */}
      <div style={{
        position: 'absolute',
        inset: 0,
        background: 'radial-gradient(circle at 20% 50%, rgba(16, 185, 129, 0.08) 0%, transparent 50%), radial-gradient(circle at 80% 80%, rgba(20, 184, 166, 0.08) 0%, transparent 50%)',
        animation: 'gradient-shift 15s ease infinite',
        zIndex: 0
      }} />
      
      {/* Floating Orbs */}
      <div className="floating-orb" style={{
        position: 'absolute',
        top: '10%',
        left: '15%',
        width: '300px',
        height: '300px',
        background: 'radial-gradient(circle, rgba(16, 185, 129, 0.15), transparent)',
        borderRadius: '50%',
        filter: 'blur(60px)',
        animation: 'float 20s ease-in-out infinite',
        zIndex: 0
      }} />
      <div className="floating-orb" style={{
        position: 'absolute',
        bottom: '10%',
        right: '15%',
        width: '250px',
        height: '250px',
        background: 'radial-gradient(circle, rgba(20, 184, 166, 0.15), transparent)',
        borderRadius: '50%',
        filter: 'blur(60px)',
        animation: 'float 25s ease-in-out infinite reverse',
        zIndex: 0
      }} />

      {/* Register Card */}
      <div className="w-full max-w-md mx-auto px-4" style={{ position: 'relative', zIndex: 10 }}>
        {/* Logo Section */}
        <div className="text-center mb-6" style={{ animation: 'fadeInUp 0.6s ease-out' }}>
          <Link to="/" className="inline-block">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-3" style={{
              background: 'linear-gradient(135deg, #10b981 0%, #14b8a6 100%)',
              boxShadow: '0 10px 40px rgba(16, 185, 129, 0.3)',
              transform: 'rotate(-5deg)',
              transition: 'transform 0.3s ease'
            }}
            onMouseEnter={(e) => e.currentTarget.style.transform = 'rotate(0deg) scale(1.05)'}
            onMouseLeave={(e) => e.currentTarget.style.transform = 'rotate(-5deg) scale(1)'}
            >
              <span style={{ fontSize: '2rem' }}>🌟</span>
            </div>
          </Link>
          <h1 className="text-3xl font-black mb-1" style={{ color: 'var(--riq-text)' }}>
            Join RouteIQ!
          </h1>
          <p style={{ color: 'var(--riq-text-muted)', fontSize: '0.9rem' }}>
            Start your adventure today 🚀
          </p>
        </div>

        {/* Register Form Card */}
        <div style={{ 
          background: 'var(--riq-surface)',
          border: '1px solid var(--riq-border)',
          borderRadius: '1.5rem',
          padding: '2rem',
          boxShadow: '0 20px 60px rgba(0, 0, 0, 0.1)',
          backdropFilter: 'blur(20px)',
          animation: 'fadeInUp 0.8s ease-out'
        }}>
          {error && (
            <div style={{
              marginBottom: '1.5rem',
              padding: '1rem',
              background: 'rgba(239, 68, 68, 0.1)',
              border: '1px solid rgba(239, 68, 68, 0.3)',
              borderRadius: '0.75rem',
              animation: 'shake 0.5s ease-in-out'
            }}>
              <p style={{ color: '#dc2626', fontSize: '0.875rem', fontWeight: 600, margin: 0, display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <span>⚠️</span>
                {error}
              </p>
            </div>
          )}

          <form onSubmit={handleSubmit} style={{ position: 'relative', zIndex: 100 }}>
            <div style={{ marginBottom: '1.25rem' }}>
              <label style={{ 
                display: 'block', 
                fontSize: '0.875rem', 
                fontWeight: 600, 
                color: 'var(--riq-text)', 
                marginBottom: '0.5rem'
              }}>
                👤 Full Name
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Adventure Seeker"
                autoComplete="name"
                required
                className="riq-input"
                style={{
                  width: '100%',
                  padding: '0.875rem 1rem',
                  fontSize: '1rem',
                  border: '2px solid var(--riq-border)',
                  borderRadius: '0.75rem',
                  background: 'var(--riq-surface-elevated)',
                  color: 'var(--riq-text)',
                  transition: 'all 0.2s ease',
                  outline: 'none',
                  position: 'relative',
                  zIndex: 100
                }}
              />
            </div>

            <div style={{ marginBottom: '1.25rem' }}>
              <label style={{ 
                display: 'block', 
                fontSize: '0.875rem', 
                fontWeight: 600, 
                color: 'var(--riq-text)', 
                marginBottom: '0.5rem'
              }}>
                📧 Email Address
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="explorer@routeiq.com"
                autoComplete="email"
                required
                className="riq-input"
                style={{
                  width: '100%',
                  padding: '0.875rem 1rem',
                  fontSize: '1rem',
                  border: '2px solid var(--riq-border)',
                  borderRadius: '0.75rem',
                  background: 'var(--riq-surface-elevated)',
                  color: 'var(--riq-text)',
                  transition: 'all 0.2s ease',
                  outline: 'none',
                  position: 'relative',
                  zIndex: 100
                }}
              />
            </div>

            <div style={{ marginBottom: '1.25rem' }}>
              <label style={{ 
                display: 'block', 
                fontSize: '0.875rem', 
                fontWeight: 600, 
                color: 'var(--riq-text)', 
                marginBottom: '0.5rem'
              }}>
                🔒 Password
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                autoComplete="new-password"
                required
                className="riq-input"
                style={{
                  width: '100%',
                  padding: '0.875rem 1rem',
                  fontSize: '1rem',
                  border: '2px solid var(--riq-border)',
                  borderRadius: '0.75rem',
                  background: 'var(--riq-surface-elevated)',
                  color: 'var(--riq-text)',
                  transition: 'all 0.2s ease',
                  outline: 'none',
                  position: 'relative',
                  zIndex: 100
                }}
              />
            </div>

            <div style={{ marginBottom: '1.5rem' }}>
              <label style={{ 
                display: 'block', 
                fontSize: '0.875rem', 
                fontWeight: 600, 
                color: 'var(--riq-text)', 
                marginBottom: '0.5rem'
              }}>
                🔐 Confirm Password
              </label>
              <input
                type="password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="••••••••"
                autoComplete="new-password"
                required
                className="riq-input"
                style={{
                  width: '100%',
                  padding: '0.875rem 1rem',
                  fontSize: '1rem',
                  border: '2px solid var(--riq-border)',
                  borderRadius: '0.75rem',
                  background: 'var(--riq-surface-elevated)',
                  color: 'var(--riq-text)',
                  transition: 'all 0.2s ease',
                  outline: 'none',
                  position: 'relative',
                  zIndex: 100
                }}
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="riq-btn-primary"
              style={{
                width: '100%',
                padding: '1rem',
                fontSize: '1rem',
                fontWeight: 700,
                borderRadius: '0.75rem',
                background: loading ? 'var(--riq-border)' : 'linear-gradient(135deg, #10b981 0%, #14b8a6 100%)',
                color: '#fff',
                border: 'none',
                cursor: loading ? 'not-allowed' : 'pointer',
                transition: 'all 0.3s ease',
                boxShadow: loading ? 'none' : '0 4px 20px rgba(16, 185, 129, 0.4)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '0.5rem',
                position: 'relative',
                zIndex: 100
              }}
            >
              {loading ? (
                <>
                  <span className="inline-block w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                  Creating account...
                </>
              ) : (
                <>
                  🎉 Create My Account
                </>
              )}
            </button>
          </form>

          <div style={{ marginTop: '1.5rem', textAlign: 'center', paddingTop: '1.5rem', borderTop: '1px solid var(--riq-border)' }}>
            <p style={{ color: 'var(--riq-text-muted)', fontSize: '0.9rem' }}>
              Already have an account?{' '}
              <Link 
                to="/login" 
                style={{ 
                  color: '#10b981', 
                  fontWeight: 700,
                  textDecoration: 'none',
                  transition: 'color 0.2s'
                }}
              >
                Sign in →
              </Link>
            </p>
          </div>
        </div>

        {/* Trust Badges */}
        <div style={{ 
          marginTop: '2rem', 
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: '2rem',
          flexWrap: 'wrap',
          opacity: 0.7
        }}>
          <div style={{ fontSize: '0.75rem', color: 'var(--riq-text-muted)', display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
            <span>🔒</span> Secure
          </div>
          <div style={{ fontSize: '0.75rem', color: 'var(--riq-text-muted)', display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
            <span>⚡</span> Fast
          </div>
          <div style={{ fontSize: '0.75rem', color: 'var(--riq-text-muted)', display: 'flex', alignItems: 'center', gap: '0.4rem' }}>
            <span>🌐</span> Global
          </div>
        </div>
      </div>

      <style>{`
        @keyframes float {
          0%, 100% { transform: translateY(0px) translateX(0px); }
          33% { transform: translateY(-25px) translateX(10px); }
          66% { transform: translateY(15px) translateX(-10px); }
        }

        @keyframes fadeInUp {
          from {
            opacity: 0;
            transform: translateY(30px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        @keyframes shake {
          0%, 100% { transform: translateX(0); }
          10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
          20%, 40%, 60%, 80% { transform: translateX(8px); }
        }

        @keyframes gradient-shift {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.8; }
        }

        .riq-input:focus {
          border-color: #10b981 !important;
          box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
        }

        .riq-btn-primary:hover:not(:disabled) {
          transform: translateY(-2px);
          box-shadow: 0 8px 30px rgba(16, 185, 129, 0.5) !important;
        }

        @media (max-width: 640px) {
          .floating-orb {
            width: 200px !important;
            height: 200px !important;
          }
        }
      `}</style>
    </div>
  );
}

