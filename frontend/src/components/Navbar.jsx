import React, { useState, useEffect } from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { Home, Map, Route, FolderOpen, Menu, X, Sparkles, LogIn, LogOut, User } from 'lucide-react';
import ThemeToggle from './ThemeToggle';
import { useAuth } from '../hooks/useAuth';

const navItems = [
  { to: '/', label: 'Home', icon: Home },
  { to: '/plan', label: 'Plan Trip', icon: Map },
  { to: '/multi-stop', label: 'Multi-Stop', icon: Route },
  { to: '/trips', label: 'Trips', icon: FolderOpen },
];

const travelerEmojis = ['🚶', '🚶‍♂️', '🚶‍♀️', '🏃', '🏃‍♂️', '🏃‍♀️', '🚴', '🚴‍♂️', '🚴‍♀️', '🧳', '🎒'];

export default function Navbar() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [travelerPosition, setTravelerPosition] = useState(-100);
  const [isScrolling, setIsScrolling] = useState(false);
  const [scrollDirection, setScrollDirection] = useState('down');
  const [travelerEmoji, setTravelerEmoji] = useState('🚶');
  const { user, isAuthenticated, logout } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    let lastScrollY = 0;
    let scrollTimeout;
    
    const onScroll = () => {
      const currentScrollY = window.scrollY;
      setScrolled(currentScrollY > 8);
      
      // Determine scroll direction
      const direction = currentScrollY > lastScrollY ? 'down' : 'up';
      setScrollDirection(direction);
      
      // Show traveler when actively scrolling
      setIsScrolling(true);
      
      // Pick random emoji when changing direction
      if ((direction === 'up' && lastScrollY > currentScrollY + 5) || 
          (direction === 'down' && currentScrollY > lastScrollY + 5)) {
        const randomEmoji = travelerEmojis[Math.floor(Math.random() * travelerEmojis.length)];
        setTravelerEmoji(randomEmoji);
      }
      
      // Calculate traveler position based on scroll (0-100%)
      // Map scroll position to navbar width
      const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPercent = maxScroll > 0 ? (currentScrollY / maxScroll) * 100 : 0;
      
      // If scrolling up, move left to right; if down, show but reverse direction
      setTravelerPosition(scrollPercent);
      
      console.log('📍 Scrolling:', direction, 'Position:', scrollPercent.toFixed(1) + '%');
      
      // Hide traveler after scroll stops
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(() => {
        setIsScrolling(false);
      }, 150);
      
      lastScrollY = currentScrollY;
    };
    
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => {
      window.removeEventListener('scroll', onScroll);
      clearTimeout(scrollTimeout);
    };
  }, []);

  const linkClassName = ({ isActive }) =>
    `riq-nav-link${isActive ? ' active' : ''}`;

  const handleLogout = async () => {
    await logout();
    setMenuOpen(false);
    navigate('/');
  };

  return (
    <header
      className="fixed top-0 z-50 w-full"
      style={{ width: '100vw', marginLeft: 'calc(-50vw + 50%)', position: 'fixed', top: 0 }}
    >
      <nav
        className={`riq-glass-nav w-full${scrolled ? ' scrolled' : ''}`}
        style={{ width: '100%', position: 'relative', overflow: 'visible' }}
      >
        {/* Walking Traveler Animation (moves with scroll) */}
        {isScrolling && (
          <div style={{ 
            position: 'absolute',
            left: 0,
            bottom: '0',
            width: '100%',
            height: '2.5rem',
            pointerEvents: 'none',
            zIndex: 1000,
            overflow: 'visible'
          }}>
            <div style={{ 
              fontSize: '2rem',
              position: 'absolute',
              left: `${travelerPosition}%`,
              bottom: '0',
              transform: scrollDirection === 'up' 
                ? 'translateX(-50%) scaleX(-1)' 
                : 'translateX(-50%) scaleX(1)',
              transition: 'left 0.1s linear',
              filter: 'drop-shadow(0 2px 8px rgba(0, 0, 0, 0.3))',
              animation: 'bounceTraveler 0.4s ease-in-out infinite'
            }}>
              {travelerEmoji}
            </div>
          </div>
        )}

        <div
          className="w-full h-14 flex items-center justify-between"
          style={{ maxWidth: '1280px', margin: '0 auto', padding: '0 1.5rem', position: 'relative' }}
        >
          {/* Logo */}
          <Link to="/" className="flex items-center gap-2.5 group shrink-0" onClick={() => setMenuOpen(false)}>
            <span className="riq-logo-mark" aria-hidden="true" />
            <span className="text-lg font-extrabold tracking-tight" style={{ color: 'var(--riq-text)' }}>
              Route<span className="riq-gradient-text">IQ</span>
            </span>
          </Link>

          {/* Desktop Nav — centered */}
          <div className="hidden md:flex items-center gap-1">
            {navItems.map((item) => {
              const Icon = item.icon;
              return (
                <NavLink key={item.to} to={item.to} className={linkClassName}>
                  <Icon size={14} style={{ marginRight: '0.3rem', opacity: 0.65 }} />
                  {item.label}
                </NavLink>
              );
            })}
          </div>

          {/* Desktop Actions */}
          <div className="hidden md:flex items-center gap-2.5 shrink-0">
            <ThemeToggle />
            {isAuthenticated ? (
              <>
                <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg" style={{ 
                  background: 'var(--riq-surface-elevated)', 
                  border: '1px solid var(--riq-border)',
                  fontSize: '0.85rem',
                  color: 'var(--riq-text)'
                }}>
                  <User size={14} />
                  <span style={{ fontWeight: 600 }}>{user?.name || 'User'}</span>
                </div>
                <button
                  onClick={handleLogout}
                  className="riq-btn-primary"
                  style={{ 
                    minHeight: '2.1rem', 
                    fontSize: '0.82rem', 
                    padding: '0.35rem 0.9rem',
                    background: 'linear-gradient(135deg, #ef4444, #dc2626)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.4rem'
                  }}
                >
                  <LogOut size={13} />
                  Logout
                </button>
              </>
            ) : (
              <>
                <Link
                  to="/login"
                  className="riq-btn-secondary"
                  style={{ 
                    minHeight: '2.1rem', 
                    fontSize: '0.82rem', 
                    padding: '0.35rem 0.9rem',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.4rem',
                    background: 'var(--riq-surface-elevated)',
                    border: '1px solid var(--riq-border)',
                    color: 'var(--riq-text)',
                    fontWeight: 600,
                    borderRadius: '0.5rem',
                    transition: 'all 0.2s'
                  }}
                >
                  <LogIn size={13} />
                  Login
                </Link>
                <Link
                  to="/plan"
                  className="riq-btn-primary"
                  style={{ minHeight: '2.1rem', fontSize: '0.82rem', padding: '0.35rem 0.9rem' }}
                >
                  <Sparkles size={13} />
                  Start Planning
                </Link>
              </>
            )}
          </div>

          {/* Mobile Actions */}
          <div className="md:hidden flex items-center gap-2">
            <ThemeToggle />
            <button
              type="button"
              onClick={() => setMenuOpen((prev) => !prev)}
              className="riq-mobile-toggle"
              aria-expanded={menuOpen}
              aria-controls="mobile-menu"
              aria-label="Toggle menu"
            >
              {menuOpen ? <X size={16} /> : <Menu size={16} />}
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {menuOpen && (
          <div id="mobile-menu" className="md:hidden px-4 pb-3">
            <div className="riq-mobile-menu">
              {navItems.map((item) => {
                const Icon = item.icon;
                return (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    onClick={() => setMenuOpen(false)}
                    className={linkClassName}
                  >
                    <Icon size={14} style={{ marginRight: '0.3rem', opacity: 0.65 }} />
                    {item.label}
                  </NavLink>
                );
              })}
              {isAuthenticated ? (
                <>
                  <div className="flex items-center gap-2 px-3 py-2 rounded-lg mt-2" style={{ 
                    background: 'var(--riq-surface-elevated)', 
                    border: '1px solid var(--riq-border)',
                    fontSize: '0.9rem',
                    color: 'var(--riq-text)',
                    justifyContent: 'center'
                  }}>
                    <User size={16} />
                    <span style={{ fontWeight: 600 }}>{user?.name || 'User'}</span>
                  </div>
                  <button
                    onClick={handleLogout}
                    className="riq-btn-primary text-center mt-2"
                    style={{ 
                      background: 'linear-gradient(135deg, #ef4444, #dc2626)',
                      width: '100%',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '0.5rem'
                    }}
                  >
                    <LogOut size={13} />
                    Logout
                  </button>
                </>
              ) : (
                <>
                  <Link
                    to="/login"
                    className="riq-btn-secondary text-center mt-2"
                    onClick={() => setMenuOpen(false)}
                    style={{ 
                      background: 'var(--riq-surface-elevated)',
                      border: '1px solid var(--riq-border)',
                      color: 'var(--riq-text)',
                      fontWeight: 600,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '0.5rem',
                      padding: '0.65rem 1rem',
                      borderRadius: '0.5rem'
                    }}
                  >
                    <LogIn size={13} />
                    Login
                  </Link>
                  <Link
                    to="/plan"
                    className="riq-btn-primary text-center mt-1"
                    onClick={() => setMenuOpen(false)}
                  >
                    <Sparkles size={13} />
                    Start Planning
                  </Link>
                </>
              )}
            </div>
          </div>
        )}
      </nav>
    </header>
  );
}
