import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import {
  Compass, BarChart3, MapPin, Zap, ArrowRight, Sparkles,
  CheckCircle, ChevronLeft, ChevronRight,
  Route, TrendingDown, Navigation, Car, Train, Plane, 
  Fuel, Shield, Clock, Star, Users, Globe
} from 'lucide-react';

/* ═══════════════════════════════════════
   HERO SLIDES — full content per slide
   ═══════════════════════════════════════ */
const HERO_SLIDES = [
  {
    eyebrow: '✨ Intelligent Travel System',
    title: 'Plan your journey,',
    highlight: 'discover the world',
    titleEnd: '',
    description: 'Compare travel modes, discover amazing destinations, and optimize multi-stop routes — all in one beautiful interface.',
    bullets: [
      { icon: Compass, text: 'Compare 4+ travel modes instantly' },
      { icon: TrendingDown, text: 'Real-time cost & duration estimates' },
      { icon: Route, text: 'Smart multi-stop route optimization' },
    ],
    image: '/assets/hero-illustration.svg',
    imageAlt: 'RouteIQ dashboard with route map and connected travel modes',
    gradient: 'from-blue-500 via-indigo-500 to-purple-500'
  },
  {
    eyebrow: '🚀 Multi-Mode Travel',
    title: 'Car, train, or flight —',
    highlight: 'plan them all',
    titleEnd: 'here',
    description: 'One platform to design road trips, rail journeys, and flight itineraries with accurate cost breakdowns.',
    bullets: [
      { icon: Navigation, text: 'Seamless mode switching on any route' },
      { icon: MapPin, text: 'POIs auto-discovered along your corridor' },
      { icon: BarChart3, text: 'Side-by-side cost comparison per mode' },
    ],
    image: '/assets/travel-modes.svg',
    imageAlt: 'Car, train, and airplane connected by travel route lines',
    gradient: 'from-teal-400 via-cyan-500 to-blue-500'
  },
  {
    eyebrow: '💡 Smart Planning',
    title: 'Travel smarter,',
    highlight: 'save more',
    titleEnd: '',
    description: 'Get AI-powered recommendations, find the best routes, and make informed decisions for your trips.',
    bullets: [
      { icon: Zap, text: 'Instant route optimization' },
      { icon: Shield, text: 'Secure trip planning' },
      { icon: Clock, text: 'Save hours of planning time' },
    ],
    image: '/assets/smart-travel.svg',
    imageAlt: 'Smart travel planning interface',
    gradient: 'from-violet-500 via-purple-500 to-pink-500'
  },
];

/* ═══════════════════════════════════════
   FEATURE CARDS
   ═══════════════════════════════════════ */
const FEATURES = [
  {
    icon: Compass,
    iconColor: 'indigo',
    title: 'Multi-Mode Journey',
    description: 'Switch between car, EV, train, and flight with route-aware recommendations.',
  },
  {
    icon: BarChart3,
    iconColor: 'teal',
    title: 'Cost Clarity',
    description: 'Compare fuel, ticket, and blended trip costs before you commit.',
  },
  {
    icon: MapPin,
    iconColor: 'amber',
    title: 'POI Intelligence',
    description: 'Discover chargers, fuel stations, food, and essential stops on your route.',
  },
  {
    icon: Zap,
    iconColor: 'violet',
    title: 'Optimization Engine',
    description: 'Handle multi-stop routes and reduce detours with smarter stop ordering.',
  },
];

const HOW_IT_WORKS = [
  {
    step: '01',
    icon: MapPin,
    title: 'Enter Your Destinations',
    description: 'Simply input your starting point and destination. Add multiple stops if needed.',
    color: 'from-blue-500 to-cyan-500'
  },
  {
    step: '02',
    icon: Route,
    title: 'Compare Travel Modes',
    description: 'View side-by-side comparisons of car, train, flight, and bus options with costs.',
    color: 'from-purple-500 to-pink-500'
  },
  {
    step: '03',
    icon: Sparkles,
    title: 'Get Smart Recommendations',
    description: 'Receive AI-powered suggestions for the best route, timing, and travel mode.',
    color: 'from-teal-500 to-green-500'
  },
  {
    step: '04',
    icon: CheckCircle,
    title: 'Save & Share',
    description: 'Save your trip plans and share them with travel companions instantly.',
    color: 'from-orange-500 to-red-500'
  },
];

const TRAVEL_MODES = [
  {
    icon: Car,
    name: 'Car & EV',
    description: 'Flexible road trips',
    color: 'indigo',
  },
  {
    icon: Train,
    name: 'Train',
    description: 'Eco-friendly travel',
    color: 'teal',
  },
  {
    icon: Plane,
    name: 'Flight',
    description: 'Fast long-distance',
    color: 'purple',
  },
  {
    icon: Fuel,
    name: 'Bus',
    description: 'Budget-friendly',
    color: 'amber',
  },
];

const TESTIMONIALS = [
  {
    quote: "RouteIQ saved me hours of planning and hundreds of dollars on my last trip. The multi-mode comparison feature is a game-changer!",
    author: "Sarah Johnson",
    role: "Frequent Traveler",
    rating: 5
  },
  {
    quote: "Finally, a trip planner that actually understands real-world travel. The POI discovery feature is incredibly useful.",
    author: "Mike Chen",
    role: "Digital Nomad",
    rating: 5
  },
  {
    quote: "Best travel planning tool I've used. Clean interface, accurate estimates, and the multi-stop optimization is brilliant.",
    author: "Priya Sharma",
    role: "Travel Blogger",
    rating: 5
  },
];

const KPIS = [
  { value: '10K+', label: 'Happy Travelers', emoji: '✨' },
  { value: '4+', label: 'Travel Modes', emoji: '🚀' },
  { value: '50K+', label: 'Routes Planned', emoji: '🗺️' },
];

/* ═══════════════════════════════════════
   HOME PAGE
   ═══════════════════════════════════════ */
export default function HomePage() {
  const [slide, setSlide] = useState(0);
  const [animating, setAnimating] = useState(false);

  const goTo = useCallback(
    (idx) => {
      if (animating || idx === slide) return;
      setAnimating(true);
      setSlide(idx);
      setTimeout(() => setAnimating(false), 600);
    },
    [animating, slide],
  );

  const next = useCallback(() => goTo((slide + 1) % HERO_SLIDES.length), [slide, goTo]);
  const prev = useCallback(() => goTo((slide - 1 + HERO_SLIDES.length) % HERO_SLIDES.length), [slide, goTo]);

  // Auto-advance every 5s
  useEffect(() => {
    const t = setInterval(next, 5000);
    return () => clearInterval(t);
  }, [next]);

  const s = HERO_SLIDES[slide];

  /* ─── shared section wrapper ─── */
  const sectionStyle = { maxWidth: '1400px', margin: '0 auto', padding: '0 2rem' };

  return (
    <div style={{ width: '100%', overflow: 'hidden' }}>

      {/* ═══════════ HERO ═══════════ */}
      <section style={{ position: 'relative', paddingTop: '4rem', paddingBottom: '4rem', overflow: 'hidden', background: 'linear-gradient(160deg, rgba(99,102,241,0.04) 0%, rgba(20,184,166,0.03) 50%, rgba(139,92,246,0.04) 100%)' }}>
        {/* Animated Background Gradient */}
        <div className="hero-gradient-bg" style={{
          position: 'absolute', top: 0, left: 0, right: 0, height: '600px',
          background: `linear-gradient(135deg, ${s.gradient.replace('from-', 'rgba(').replace('via-', '0.1), rgba(').replace('to-', '0.05), rgba(')} 0.02))`,
          opacity: 0.15,
          zIndex: 0,
        }} />
        
        {/* Floating Shapes */}
        <div className="hero-shapes" style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, overflow: 'hidden', zIndex: 0 }}>
          <div className="float-shape shape-1" style={{ 
            position: 'absolute', width: '300px', height: '300px', 
            background: 'radial-gradient(circle, rgba(99,102,241,0.1), transparent)',
            borderRadius: '50%', top: '10%', right: '10%', animation: 'float 20s infinite ease-in-out'
          }} />
          <div className="float-shape shape-2" style={{ 
            position: 'absolute', width: '200px', height: '200px',
            background: 'radial-gradient(circle, rgba(20,184,166,0.1), transparent)',
            borderRadius: '50%', bottom: '20%', left: '5%', animation: 'float 15s infinite ease-in-out reverse'
          }} />
        </div>

        <div style={{ ...sectionStyle, position: 'relative', zIndex: 1 }}>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '4.5rem', alignItems: 'center' }}
            className="hero-grid">

            {/* Left — Text content (changes per slide) */}
            <div key={`text-${slide}`} className="animate-slideTextIn">
              <div style={{ 
                display: 'inline-flex', alignItems: 'center', gap: '0.5rem',
                padding: '0.6rem 1.2rem', borderRadius: '2rem',
                background: 'linear-gradient(135deg, rgba(99,102,241,0.12), rgba(20,184,166,0.08))',
                border: '1.5px solid rgba(99,102,241,0.2)',
                marginBottom: '1.5rem',
                boxShadow: '0 2px 12px rgba(99,102,241,0.15)'
              }}>
                <Sparkles size={18} style={{ color: '#6366f1' }} />
                <p className="riq-eyebrow" style={{ margin: 0, fontSize: '0.95rem', fontWeight: 700 }}>{s.eyebrow}</p>
              </div>

              <h1 className="riq-hero-title" style={{ fontSize: 'clamp(2.5rem, 4.5vw, 4rem)', fontWeight: 900, lineHeight: 1.08, marginBottom: '1.5rem' }}>
                {s.title}{' '}
                <span className="riq-gradient-text" style={{ 
                  backgroundImage: `linear-gradient(135deg, #6366f1, #14b8a6)`,
                  WebkitBackgroundClip: 'text',
                  WebkitTextFillColor: 'transparent',
                  backgroundClip: 'text',
                  backgroundSize: '200% auto',
                  animation: 'shimmer 3s linear infinite'
                }}>{s.highlight}</span>{' '}
                {s.titleEnd}
              </h1>

              <p style={{ color: 'var(--riq-text-muted)', fontSize: '1.15rem', lineHeight: 1.75, maxWidth: '560px' }}>
                {s.description}
              </p>

              <ul style={{ marginTop: '2.25rem', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                {s.bullets.map((b) => {
                  const Icon = b.icon;
                  return (
                    <li key={b.text} style={{ display: 'flex', alignItems: 'center', gap: '1rem', fontSize: '1.05rem', fontWeight: 600, color: 'var(--riq-text)' }}>
                      <div style={{ 
                        background: 'linear-gradient(135deg, #6366f1, #14b8a6)',
                        padding: '0.65rem', borderRadius: '0.75rem', display: 'flex', alignItems: 'center', justifyContent: 'center',
                        boxShadow: '0 4px 14px rgba(99,102,241,0.25)',
                        minWidth: '2.75rem', minHeight: '2.75rem'
                      }}>
                        <Icon size={20} style={{ color: 'white' }} />
                      </div>
                      {b.text}
                    </li>
                  );
                })}
              </ul>

              <div style={{ marginTop: '3rem', display: 'flex', flexWrap: 'wrap', gap: '1rem' }}>
                <Link to="/plan" className="riq-btn-primary" style={{ 
                  padding: '1rem 2rem', fontSize: '1.05rem', fontWeight: 700,
                  background: 'linear-gradient(135deg, #6366f1, #4f46e5)',
                  boxShadow: '0 6px 24px rgba(99,102,241,0.35)',
                  display: 'inline-flex', alignItems: 'center', gap: '0.5rem'
                }}>
                  🚀 Plan Your Trip
                  <ArrowRight size={20} />
                </Link>
                <Link to="/multi-stop" className="riq-btn-secondary" style={{ padding: '1rem 2rem', fontSize: '1.05rem', fontWeight: 600 }}>
                  🎯 Optimize Multi-Stop
                </Link>
              </div>
            </div>

            {/* Right — Image (changes per slide) */}
            <div style={{ display: 'flex', justifyContent: 'center' }}>
              <div style={{ position: 'relative', width: '100%', maxWidth: '480px' }}>
                {/* Animated Glow */}
                <div className="pulse-glow" style={{
                  position: 'absolute', inset: '-3rem', borderRadius: '2rem',
                  filter: 'blur(60px)', opacity: 0.3, pointerEvents: 'none',
                  background: 'radial-gradient(circle at 50% 50%, rgba(99,102,241,0.4), rgba(20,184,166,0.3), transparent 70%)',
                  animation: 'pulse 3s infinite ease-in-out'
                }} />
                {/* Image card */}
                <div style={{
                  position: 'relative', zIndex: 1, overflow: 'hidden', borderRadius: '1.5rem',
                  aspectRatio: '1 / 1',
                  border: '1px solid var(--riq-border)',
                  background: 'var(--riq-surface-elevated)',
                  boxShadow: 'var(--riq-shadow-xl)',
                  transform: 'translateZ(0)'
                }} className="hover-lift">
                  <div style={{
                    position: 'absolute', inset: 0,
                    background: `linear-gradient(135deg, ${s.gradient.replace('from-', 'rgba(').replace('via-', '0.05), rgba(').replace('to-', '0.03), rgba(')} 0.02))`,
                    opacity: 0.5
                  }} />
                  <img
                    key={`img-${slide}`}
                    src={s.image}
                    alt={s.imageAlt}
                    className="animate-slideImageIn"
                    style={{ width: '100%', height: '100%', objectFit: 'contain', padding: '2rem', position: 'relative', zIndex: 1 }}
                    onError={(e) => {
                      e.target.style.display = 'none';
                      e.target.nextSibling.style.display = 'flex';
                    }}
                  />
                  {/* Fallback Illustration */}
                  <div style={{ 
                    display: 'none', position: 'absolute', inset: 0, 
                    alignItems: 'center', justifyContent: 'center',
                    fontSize: '6rem', opacity: 0.3
                  }}>
                    <Globe size={180} strokeWidth={1} />
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Slide Navigation */}
          <div style={{ marginTop: '3rem', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '1.25rem' }}>
            <button onClick={prev} className="riq-carousel-arrow" aria-label="Previous slide" style={{
              background: 'var(--riq-surface-elevated)', border: '1px solid var(--riq-border)',
              width: '40px', height: '40px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center',
              cursor: 'pointer', transition: 'all 0.3s ease'
            }}>
              <ChevronLeft size={18} />
            </button>
            <div style={{ display: 'flex', alignItems: 'center', gap: '0.6rem' }}>
              {HERO_SLIDES.map((_, i) => (
                <button
                  key={i}
                  onClick={() => goTo(i)}
                  aria-label={`Go to slide ${i + 1}`}
                  style={{
                    width: i === slide ? '2rem' : '0.5rem',
                    height: '0.5rem',
                    borderRadius: '9999px',
                    background: i === slide ? 'linear-gradient(135deg, #6366f1, #14b8a6)' : 'var(--riq-border-strong)',
                    border: 'none',
                    cursor: 'pointer',
                    transition: 'all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)',
                  }}
                />
              ))}
            </div>
            <button onClick={next} className="riq-carousel-arrow" aria-label="Next slide" style={{
              background: 'var(--riq-surface-elevated)', border: '1px solid var(--riq-border)',
              width: '40px', height: '40px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center',
              cursor: 'pointer', transition: 'all 0.3s ease'
            }}>
              <ChevronRight size={18} />
            </button>
          </div>

          {/* KPI Stats Row */}
          <div className="kpi-stats-grid" style={{ marginTop: '4.5rem', display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '2rem', maxWidth: '900px', marginLeft: 'auto', marginRight: 'auto' }}>
            {KPIS.map((item, i) => (
              <div key={item.label} className={`riq-kpi-card riq-fade-up riq-stagger-${i + 1}`} style={{
                textAlign: 'center', padding: '2rem 1.5rem',
                background: 'var(--riq-surface)', border: '1.5px solid var(--riq-border)',
                borderRadius: 'var(--riq-radius-xl)', boxShadow: '0 4px 16px rgba(0,0,0,0.06)',
                transition: 'all 0.3s ease', cursor: 'pointer'
              }}
              onMouseEnter={e => {
                e.currentTarget.style.transform = 'translateY(-4px)';
                e.currentTarget.style.boxShadow = '0 8px 24px rgba(99,102,241,0.15)';
              }}
              onMouseLeave={e => {
                e.currentTarget.style.transform = 'translateY(0)';
                e.currentTarget.style.boxShadow = '0 4px 16px rgba(0,0,0,0.06)';
              }}>
                <span style={{ fontSize: '3rem' }}>{item.emoji}</span>
                <p className="riq-kpi-value" style={{ fontSize: '2.25rem', fontWeight: 900, marginTop: '0.75rem', color: 'var(--riq-text)' }}>{item.value}</p>
                <p className="riq-kpi-label" style={{ fontSize: '0.95rem', fontWeight: 600, color: 'var(--riq-text-muted)', marginTop: '0.4rem' }}>{item.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ═══════════ TRAVEL MODES ═══════════ */}
      <section style={{ background: 'var(--riq-bg-alt)', borderTop: '1px solid var(--riq-border)', borderBottom: '1px solid var(--riq-border)', padding: '4.5rem 0' }}>
        <div style={{ ...sectionStyle }}>
          <div style={{ textAlign: 'center', marginBottom: '3.5rem' }} className="riq-fade-up">
            <div style={{ 
              display: 'inline-flex', alignItems: 'center', gap: '0.75rem',
              padding: '0.6rem 1.5rem', borderRadius: '2rem',
              background: 'linear-gradient(135deg, rgba(99,102,241,0.08), rgba(20,184,166,0.06))',
              border: '1px solid rgba(99,102,241,0.15)',
              marginBottom: '1.25rem'
            }}>
              <span style={{ fontSize: '1.25rem' }}>🚗</span>
              <span style={{ fontSize: '1.25rem' }}>✈️</span>
              <span style={{ fontSize: '1.25rem' }}>🚆</span>
              <span style={{ fontSize: '1.25rem' }}>🚌</span>
            </div>
            <h2 style={{ fontSize: 'clamp(2rem, 3.5vw, 2.75rem)', fontWeight: 900, color: 'var(--riq-text)', lineHeight: 1.2 }}>
              All travel modes in {' '}
              <span className="riq-gradient-text">one place</span>
            </h2>
            <p style={{ fontSize: '1.1rem', color: 'var(--riq-text-muted)', marginTop: '1rem', maxWidth: '600px', marginLeft: 'auto', marginRight: 'auto' }}>
              Compare costs, check routes, and pick the perfect way to travel
            </p>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '2rem' }} className="travel-modes-grid">
            {TRAVEL_MODES.map((mode, index) => {
              const ModeIcon = mode.icon;
              return (
                <div
                  key={mode.name}
                  className={`riq-travel-mode-card riq-fade-up riq-stagger-${index + 1}`}
                  style={{
                    padding: '2.5rem 1.75rem', textAlign: 'center',
                    background: 'var(--riq-surface-elevated)', border: '1.5px solid var(--riq-border)',
                    borderRadius: 'var(--riq-radius-xl)', boxShadow: '0 4px 16px rgba(0,0,0,0.05)',
                    transition: 'all 0.3s ease', cursor: 'pointer', position: 'relative', overflow: 'hidden'
                  }}
                  onMouseEnter={e => {
                    e.currentTarget.style.transform = 'translateY(-8px)';
                    e.currentTarget.style.boxShadow = '0 12px 32px rgba(99,102,241,0.2)';
                    e.currentTarget.style.borderColor = 'rgba(99,102,241,0.3)';
                  }}
                  onMouseLeave={e => {
                    e.currentTarget.style.transform = 'translateY(0)';
                    e.currentTarget.style.boxShadow = '0 4px 16px rgba(0,0,0,0.05)';
                    e.currentTarget.style.borderColor = 'var(--riq-border)';
                  }}
                >
                  <div style={{position: 'absolute', top: '1rem', right: '1rem', fontSize: '2rem', opacity: 0.15}}>
                    {mode.name === 'Car' ? '🚗' : mode.name === 'Train' ? '🚆' : mode.name === 'Flight' ? '✈️' : '🚌'}
                  </div>
                  <div style={{
                    width: '80px', height: '80px', margin: '0 auto', marginBottom: '1.5rem',
                    background: `linear-gradient(135deg, rgba(99,102,241,0.15), rgba(20,184,166,0.15))`,
                    borderRadius: 'var(--riq-radius-lg)', display: 'flex', alignItems: 'center', justifyContent: 'center',
                    boxShadow: '0 4px 16px rgba(99,102,241,0.15)'
                  }}>
                    <ModeIcon size={40} strokeWidth={1.8} style={{ color: 'var(--riq-accent)' }} />
                  </div>
                  <h3 style={{ fontSize: '1.25rem', fontWeight: 800, marginBottom: '0.6rem', color: 'var(--riq-text)' }}>
                    {mode.name}
                  </h3>
                  <p style={{ fontSize: '0.95rem', lineHeight: 1.6, color: 'var(--riq-text-muted)' }}>
                    {mode.description}
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* ═══════════ HOW IT WORKS ═══════════ */}
      <section style={{ ...sectionStyle, paddingTop: '4rem', paddingBottom: '4rem' }}>
        <div style={{ textAlign: 'center', marginBottom: '3rem' }} className="riq-fade-up">
          <p className="riq-eyebrow" style={{ justifyContent: 'center' }}>⚡ Simple & Fast</p>
          <h2 style={{ fontSize: 'clamp(1.75rem, 3vw, 2.5rem)', fontWeight: 800, marginTop: '1rem', color: 'var(--riq-text)' }}>
            How RouteIQ {' '}
            <span className="riq-gradient-text">works</span>
          </h2>
          <p style={{ marginTop: '0.75rem', maxWidth: '600px', marginLeft: 'auto', marginRight: 'auto', fontSize: '1rem', lineHeight: 1.7, color: 'var(--riq-text-muted)' }}>
            Plan your perfect trip in four simple steps
          </p>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '2rem' }} className="how-it-works-grid">
          {HOW_IT_WORKS.map((step, index) => {
            const StepIcon = step.icon;
            return (
              <div
                key={step.step}
                className={`riq-how-card riq-fade-up riq-stagger-${index + 1}`}
                style={{ position: 'relative', textAlign: 'center' }}
              >
                {/* Connector Line */}
                {index < HOW_IT_WORKS.length - 1 && (
                  <div style={{
                    position: 'absolute', top: '40px', left: '50%', width: 'calc(100% + 2rem)', height: '2px',
                    background: 'linear-gradient(90deg, var(--riq-accent), var(--riq-secondary))',
                    opacity: 0.2, zIndex: 0
                  }} />
                )}
                
                {/* Step Number */}
                <div style={{ position: 'relative', zIndex: 1, marginBottom: '1.5rem', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1rem' }}>
                  <div style={{
                    width: '80px', height: '80px',
                    background: `linear-gradient(135deg, ${step.color})`,
                    borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center',
                    boxShadow: '0 8px 24px rgba(0,0,0,0.12)', position: 'relative'
                  }}>
                    <StepIcon size={36} strokeWidth={2} style={{ color: 'white' }} />
                  </div>
                  <span style={{
                    fontSize: '0.75rem', fontWeight: 800, color: 'var(--riq-text-faint)',
                    letterSpacing: '0.1em'
                  }}>STEP {step.step}</span>
                </div>
                
                <h3 style={{ fontSize: '1.1rem', fontWeight: 700, marginBottom: '0.75rem', color: 'var(--riq-text)' }}>
                  {step.title}
                </h3>
                <p style={{ fontSize: '0.9rem', lineHeight: 1.6, color: 'var(--riq-text-muted)' }}>
                  {step.description}
                </p>
              </div>
            );
          })}
        </div>
      </section>

      {/* ═══════════ FEATURES ═══════════ */}
      <section style={{ background: 'var(--riq-bg-alt)', borderTop: '1px solid var(--riq-border)' }}>
        <div style={{ ...sectionStyle, paddingTop: '4rem', paddingBottom: '4rem' }}>
          {/* Centered heading */}
          <div style={{ textAlign: 'center', marginBottom: '3rem' }} className="riq-fade-up">
            <p className="riq-eyebrow" style={{ justifyContent: 'center' }}>💎 Core Capabilities</p>
            <h2 style={{ fontSize: 'clamp(1.75rem, 3vw, 2.5rem)', fontWeight: 800, marginTop: '1rem', color: 'var(--riq-text)' }}>
              Everything for trip planning,{' '}
              <span className="riq-gradient-text">in one place</span>
            </h2>
            <p style={{ marginTop: '0.75rem', maxWidth: '600px', marginLeft: 'auto', marginRight: 'auto', fontSize: '1rem', lineHeight: 1.7, color: 'var(--riq-text-muted)' }}>
              From quick point-to-point trips to complex multi-city itineraries with mode switching.
            </p>
          </div>

          {/* 4 cards */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '1.5rem' }} className="features-grid">
            {FEATURES.map((feature, index) => {
              const IconComponent = feature.icon;
              return (
                <article
                  key={feature.title}
                  className={`riq-feature-card riq-fade-up riq-stagger-${index + 1}`}
                  style={{
                    padding: '2rem 1.5rem',
                    background: 'var(--riq-surface-elevated)', border: '1px solid var(--riq-border)',
                    borderRadius: 'var(--riq-radius-xl)', boxShadow: 'var(--riq-shadow-sm)',
                    transition: 'all 0.3s ease', cursor: 'pointer'
                  }}
                >
                  <div className={`riq-feature-icon ${feature.iconColor}`} style={{
                    width: '48px', height: '48px', marginBottom: '1.25rem',
                    background: 'var(--riq-accent-soft)', borderRadius: 'var(--riq-radius-md)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center'
                  }}>
                    <IconComponent size={24} strokeWidth={2} style={{ color: 'var(--riq-accent)' }} />
                  </div>
                  <h3 style={{ fontSize: '1.1rem', fontWeight: 700, marginBottom: '0.6rem', color: 'var(--riq-text)' }}>
                    {feature.title}
                  </h3>
                  <p style={{ fontSize: '0.9rem', lineHeight: 1.6, color: 'var(--riq-text-muted)' }}>
                    {feature.description}
                  </p>
                </article>
              );
            })}
          </div>
        </div>
      </section>

      {/* ═══════════ TESTIMONIALS ═══════════ */}
      <section style={{ ...sectionStyle, paddingTop: '4rem', paddingBottom: '4rem' }}>
        <div style={{ textAlign: 'center', marginBottom: '3rem' }} className="riq-fade-up">
          <p className="riq-eyebrow" style={{ justifyContent: 'center' }}>⭐ What Travelers Say</p>
          <h2 style={{ fontSize: 'clamp(1.75rem, 3vw, 2.5rem)', fontWeight: 800, marginTop: '1rem', color: 'var(--riq-text)' }}>
            Loved by {' '}
            <span className="riq-gradient-text">thousands</span>
          </h2>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '2rem' }} className="testimonials-grid">
          {TESTIMONIALS.map((testimonial, index) => (
            <div
              key={index}
              className={`riq-testimonial-card riq-fade-up riq-stagger-${index + 1}`}
              style={{
                padding: '2rem', background: 'var(--riq-surface-elevated)',
                border: '1px solid var(--riq-border)', borderRadius: 'var(--riq-radius-xl)',
                boxShadow: 'var(--riq-shadow-md)', transition: 'all 0.3s ease'
              }}
            >
              {/* Stars */}
              <div style={{ display: 'flex', gap: '0.25rem', marginBottom: '1rem' }}>
                {[...Array(testimonial.rating)].map((_, i) => (
                  <Star key={i} size={16} fill="#fbbf24" color="#fbbf24" />
                ))}
              </div>
              
              <blockquote style={{ fontSize: '0.95rem', lineHeight: 1.7, color: 'var(--riq-text)', marginBottom: '1.5rem', fontStyle: 'italic' }}>
                "{testimonial.quote}"
              </blockquote>
              
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                <div style={{
                  width: '40px', height: '40px', borderRadius: '50%',
                  background: 'linear-gradient(135deg, var(--riq-accent), var(--riq-secondary))',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  color: 'white', fontWeight: 700, fontSize: '0.9rem'
                }}>
                  {testimonial.author.charAt(0)}
                </div>
                <div>
                  <p style={{ fontSize: '0.95rem', fontWeight: 600, color: 'var(--riq-text)' }}>
                    {testimonial.author}
                  </p>
                  <p style={{ fontSize: '0.8rem', color: 'var(--riq-text-muted)' }}>
                    {testimonial.role}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* ═══════════ CTA ═══════════ */}
      <section style={{ ...sectionStyle, paddingTop: '3rem', paddingBottom: '5rem' }}>
        <div className="riq-panel riq-fade-up" style={{ 
          padding: '4rem 3rem', textAlign: 'center', position: 'relative', overflow: 'hidden',
          background: 'linear-gradient(135deg, rgba(99,102,241,0.08), rgba(20,184,166,0.08))',
          border: '1px solid var(--riq-border)', borderRadius: 'var(--riq-radius-2xl)',
          boxShadow: 'var(--riq-shadow-lg)'
        }}>
          {/* Decorative Background */}
          <div style={{
            position: 'absolute', inset: 0, opacity: 0.15, pointerEvents: 'none',
            background: 'radial-gradient(circle at 30% 50%, rgba(99,102,241,0.3), transparent 50%), radial-gradient(circle at 70% 50%, rgba(20,184,166,0.3), transparent 50%)',
          }} />
          
          <div style={{ position: 'relative', zIndex: 1 }}>
            <div style={{ 
              display: 'inline-block', padding: '0.5rem 1rem', marginBottom: '1.5rem',
              background: 'var(--riq-accent-soft)', borderRadius: 'var(--riq-radius-md)',
              fontSize: '0.85rem', fontWeight: 600, color: 'var(--riq-accent)'
            }}>
              🎉 Start Your Journey Today
            </div>
            
            <h2 style={{ fontSize: 'clamp(1.75rem, 3vw, 2.75rem)', fontWeight: 900, color: 'var(--riq-text)', lineHeight: 1.2 }}>
              Ready to plan your next{' '}
              <span className="riq-gradient-text" style={{
                backgroundImage: 'linear-gradient(135deg, #6366f1, #14b8a6)',
                WebkitBackgroundClip: 'text',
                WebkitTextFillColor: 'transparent',
                backgroundClip: 'text'
              }}>adventure?</span>
            </h2>
            
            <p style={{ marginTop: '1.25rem', maxWidth: '580px', marginLeft: 'auto', marginRight: 'auto', fontSize: '1.05rem', lineHeight: 1.7, color: 'var(--riq-text-muted)' }}>
              Join thousands of travelers who trust RouteIQ for smarter, more efficient trip planning.
            </p>
            
            <div style={{ marginTop: '2.5rem', display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: '1rem' }}>
              <Link to="/plan" className="riq-btn-primary" style={{ 
                padding: '1rem 2.5rem', fontSize: '1.05rem', fontWeight: 600,
                background: 'linear-gradient(135deg, #6366f1, #4f46e5)',
                boxShadow: '0 8px 24px rgba(99,102,241,0.35)'
              }}>
                <Sparkles size={18} />
                Start Planning Now
                <ArrowRight size={18} />
              </Link>
              <Link to="/trips" className="riq-btn-secondary" style={{ padding: '1rem 2rem', fontSize: '1.05rem' }}>
                View Examples
              </Link>
            </div>
            
            {/* Trust Indicators */}
            <div style={{ marginTop: '3rem', display: 'flex', justifyContent: 'center', gap: '3rem', flexWrap: 'wrap' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <Users size={20} style={{ color: 'var(--riq-accent)' }} />
                <span style={{ fontSize: '0.9rem', fontWeight: 600, color: 'var(--riq-text)' }}>10,000+ Users</span>
              </div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <Star size={20} fill="#fbbf24" color="#fbbf24" />
                <span style={{ fontSize: '0.9rem', fontWeight: 600, color: 'var(--riq-text)' }}>4.9/5 Rating</span>
              </div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <Globe size={20} style={{ color: 'var(--riq-secondary)' }} />
                <span style={{ fontSize: '0.9rem', fontWeight: 600, color: 'var(--riq-text)' }}>Global Coverage</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Responsive overrides */}
      <style>{`
        @media (max-width: 968px) {
          .hero-grid { grid-template-columns: 1fr !important; gap: 2.5rem !important; }
          .travel-modes-grid { grid-template-columns: repeat(2, 1fr) !important; }
          .how-it-works-grid { grid-template-columns: repeat(2, 1fr) !important; }
          .features-grid { grid-template-columns: repeat(2, 1fr) !important; }
          .testimonials-grid { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 640px) {
          .travel-modes-grid { grid-template-columns: 1fr !important; }
          .how-it-works-grid { grid-template-columns: 1fr !important; }
          .features-grid { grid-template-columns: 1fr !important; }
          .kpi-stats-grid { grid-template-columns: 1fr !important; gap: 1rem !important; max-width: 100% !important; }
        }
        @media (max-width: 480px) {
          .kpi-stats-grid { padding: 0 0.5rem; }
          .riq-kpi-card { padding: 1.25rem 1rem !important; }
        }
        
        /* Hover Effects */
        .riq-travel-mode-card:hover,
        .riq-feature-card:hover,
        .riq-testimonial-card:hover {
          transform: translateY(-4px);
          box-shadow: var(--riq-shadow-card-hover) !important;
        }
        
        .hover-lift:hover {
          transform: translateY(-8px) scale(1.02);
        }
        
        /* Animations */
        @keyframes float {
          0%, 100% { transform: translateY(0px) rotate(0deg); }
          50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        @keyframes pulse {
          0%, 100% { opacity: 0.3; transform: scale(1); }
          50% { opacity: 0.5; transform: scale(1.05); }
        }
        
        .riq-carousel-arrow:hover {
          transform: scale(1.1);
          box-shadow: var(--riq-shadow-md);
        }
      `}</style>
    </div>
  );
}
