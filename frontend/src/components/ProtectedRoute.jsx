import React, { useEffect, useState } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { AlertCircle, Lock } from 'lucide-react';

export default function ProtectedRoute({ children }) {
  const { isAuthenticated, loading } = useAuth();
  const location = useLocation();
  const [showNotification, setShowNotification] = useState(false);

  useEffect(() => {
    if (!loading && !isAuthenticated) {
      setShowNotification(true);
      const timer = setTimeout(() => setShowNotification(false), 5000);
      return () => clearTimeout(timer);
    }
  }, [isAuthenticated, loading]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center" style={{ background: 'var(--riq-bg)' }}>
        <div className="text-center">
          <div className="inline-block w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mb-4"></div>
          <p style={{ color: 'var(--riq-text-muted)', fontSize: '1rem', fontWeight: 600 }}>
            Loading...
          </p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <>
        {/* Login Required Notification */}
        {showNotification && (
          <div 
            style={{
              position: 'fixed',
              top: '5rem',
              left: '50%',
              transform: 'translateX(-50%)',
              zIndex: 9999,
              animation: 'slideDown 0.3s ease-out',
              maxWidth: '90vw',
              width: '500px'
            }}
          >
            <div style={{
              background: 'linear-gradient(135deg, rgba(99, 102, 241, 0.95), rgba(139, 92, 246, 0.95))',
              backdropFilter: 'blur(10px)',
              padding: '1.25rem 1.5rem',
              borderRadius: '1rem',
              boxShadow: '0 20px 60px rgba(99, 102, 241, 0.4), 0 0 0 1px rgba(255,255,255,0.1)',
              display: 'flex',
              alignItems: 'center',
              gap: '1rem',
              color: '#fff'
            }}>
              <div style={{
                background: 'rgba(255, 255, 255, 0.2)',
                borderRadius: '50%',
                padding: '0.75rem',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
              }}>
                <Lock size={24} />
              </div>
              <div style={{ flex: 1 }}>
                <div style={{ 
                  fontSize: '1.1rem', 
                  fontWeight: 800, 
                  marginBottom: '0.25rem',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '0.5rem'
                }}>
                  <AlertCircle size={18} />
                  Login Required
                </div>
                <p style={{ fontSize: '0.9rem', opacity: 0.95, margin: 0 }}>
                  Please log in to access this feature and start planning your trips!
                </p>
              </div>
            </div>
          </div>
        )}
        
        <Navigate to="/login" state={{ from: location }} replace />

        <style>{`
          @keyframes slideDown {
            from {
              opacity: 0;
              transform: translateX(-50%) translateY(-20px);
            }
            to {
              opacity: 1;
              transform: translateX(-50%) translateY(0);
            }
          }
        `}</style>
      </>
    );
  }

  return children;
}
