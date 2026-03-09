import React from 'react';
import { Link } from 'react-router-dom';
import { Home, Map, FolderOpen, BarChart3, Mail, Github, Twitter, Linkedin } from 'lucide-react';

export default function Footer() {
  const currentYear = new Date().getFullYear();

  return (
    <footer 
      className="w-full border-t"
      style={{ 
        background: 'var(--riq-surface)',
        borderColor: 'var(--riq-border)',
        color: 'var(--riq-text)'
      }}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Main Footer Content */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
          
          {/* About RouteIQ */}
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <div 
                className="w-8 h-8 rounded-lg flex items-center justify-center"
                style={{
                  background: 'linear-gradient(135deg, #6366f1 0%, #14b8a6 100%)',
                  boxShadow: '0 2px 8px rgba(99, 102, 241, 0.3)'
                }}
              >
                <span className="text-lg">🗺️</span>
              </div>
              <h3 className="text-lg font-bold">
                Route<span style={{ 
                  background: 'linear-gradient(135deg, #6366f1, #14b8a6)',
                  WebkitBackgroundClip: 'text',
                  WebkitTextFillColor: 'transparent',
                  backgroundClip: 'text'
                }}>IQ</span>
              </h3>
            </div>
            <p style={{ 
              color: 'var(--riq-text-muted)', 
              fontSize: '0.875rem',
              lineHeight: '1.6'
            }}>
              AI-powered route and cost optimization platform that compares travel modes like car, bus, EV, train, and flight.
            </p>
            <div className="flex gap-3 pt-2">
              <a 
                href="https://github.com" 
                target="_blank" 
                rel="noopener noreferrer"
                className="p-2 rounded-lg transition-all duration-200 hover:scale-110"
                style={{ 
                  background: 'var(--riq-surface-elevated)',
                  border: '1px solid var(--riq-border)'
                }}
                aria-label="GitHub"
              >
                <Github size={18} style={{ color: 'var(--riq-text-muted)' }} />
              </a>
              <a 
                href="https://twitter.com" 
                target="_blank" 
                rel="noopener noreferrer"
                className="p-2 rounded-lg transition-all duration-200 hover:scale-110"
                style={{ 
                  background: 'var(--riq-surface-elevated)',
                  border: '1px solid var(--riq-border)'
                }}
                aria-label="Twitter"
              >
                <Twitter size={18} style={{ color: 'var(--riq-text-muted)' }} />
              </a>
              <a 
                href="https://linkedin.com" 
                target="_blank" 
                rel="noopener noreferrer"
                className="p-2 rounded-lg transition-all duration-200 hover:scale-110"
                style={{ 
                  background: 'var(--riq-surface-elevated)',
                  border: '1px solid var(--riq-border)'
                }}
                aria-label="LinkedIn"
              >
                <Linkedin size={18} style={{ color: 'var(--riq-text-muted)' }} />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div className="space-y-4">
            <h4 className="font-bold text-base" style={{ color: 'var(--riq-text)' }}>
              Quick Links
            </h4>
            <ul className="space-y-3">
              <li>
                <Link 
                  to="/" 
                  className="flex items-center gap-2 text-sm transition-colors duration-200 hover:translate-x-1"
                  style={{ 
                    color: 'var(--riq-text-muted)',
                    transition: 'all 0.2s'
                  }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  <Home size={16} />
                  Home
                </Link>
              </li>
              <li>
                <Link 
                  to="/plan" 
                  className="flex items-center gap-2 text-sm transition-colors duration-200 hover:translate-x-1"
                  style={{ 
                    color: 'var(--riq-text-muted)',
                    transition: 'all 0.2s'
                  }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  <Map size={16} />
                  Route Planner
                </Link>
              </li>
              <li>
                <Link 
                  to="/trips" 
                  className="flex items-center gap-2 text-sm transition-colors duration-200 hover:translate-x-1"
                  style={{ 
                    color: 'var(--riq-text-muted)',
                    transition: 'all 0.2s'
                  }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  <FolderOpen size={16} />
                  Trip History
                </Link>
              </li>
              <li>
                <Link 
                  to="/analytics" 
                  className="flex items-center gap-2 text-sm transition-colors duration-200 hover:translate-x-1"
                  style={{ 
                    color: 'var(--riq-text-muted)',
                    transition: 'all 0.2s'
                  }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  <BarChart3 size={16} />
                  Analytics
                </Link>
              </li>
            </ul>
          </div>

          {/* Resources */}
          <div className="space-y-4">
            <h4 className="font-bold text-base" style={{ color: 'var(--riq-text)' }}>
              Resources
            </h4>
            <ul className="space-y-3">
              <li>
                <Link 
                  to="/multi-stop" 
                  className="text-sm block transition-colors duration-200"
                  style={{ color: 'var(--riq-text-muted)' }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  Multi-Stop Routes
                </Link>
              </li>
              <li>
                <Link 
                  to="/car-configurator" 
                  className="text-sm block transition-colors duration-200"
                  style={{ color: 'var(--riq-text-muted)' }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  Car Configurator
                </Link>
              </li>
              <li>
                <Link 
                  to="/ev-calculator" 
                  className="text-sm block transition-colors duration-200"
                  style={{ color: 'var(--riq-text-muted)' }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  EV Calculator
                </Link>
              </li>
              <li>
                <a 
                  href="#" 
                  className="text-sm block transition-colors duration-200"
                  style={{ color: 'var(--riq-text-muted)' }}
                  onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
                >
                  Documentation
                </a>
              </li>
            </ul>
          </div>

          {/* Contact */}
          <div className="space-y-4">
            <h4 className="font-bold text-base" style={{ color: 'var(--riq-text)' }}>
              Contact
            </h4>
            <div className="space-y-3">
              <a 
                href="mailto:hello@routeiq.com"
                className="flex items-start gap-2 text-sm transition-colors duration-200 group"
                style={{ color: 'var(--riq-text-muted)' }}
              >
                <Mail size={16} className="mt-0.5 flex-shrink-0 group-hover:scale-110 transition-transform" />
                <span 
                  className="group-hover:underline"
                  onMouseEnter={(e) => e.target.parentElement.style.color = 'var(--riq-text)'}
                  onMouseLeave={(e) => e.target.parentElement.style.color = 'var(--riq-text-muted)'}
                >
                  hello@routeiq.com
                </span>
              </a>
              
              <div 
                className="p-4 rounded-lg"
                style={{ 
                  background: 'var(--riq-surface-elevated)',
                  border: '1px solid var(--riq-border)'
                }}
              >
                <p className="text-xs font-semibold mb-1" style={{ color: 'var(--riq-text)' }}>
                  Project Info
                </p>
                <p className="text-xs leading-relaxed" style={{ color: 'var(--riq-text-muted)' }}>
                  Built with ❤️ using React, PHP, and AI-powered algorithms for intelligent route optimization.
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div 
          className="pt-6 border-t"
          style={{ borderColor: 'var(--riq-border)' }}
        >
          <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
            <p className="text-sm" style={{ color: 'var(--riq-text-muted)' }}>
              © {currentYear} RouteIQ. All rights reserved.
            </p>
            <div className="flex gap-6">
              <a 
                href="#" 
                className="text-sm transition-colors duration-200"
                style={{ color: 'var(--riq-text-muted)' }}
                onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
              >
                Privacy Policy
              </a>
              <a 
                href="#" 
                className="text-sm transition-colors duration-200"
                style={{ color: 'var(--riq-text-muted)' }}
                onMouseEnter={(e) => e.target.style.color = 'var(--riq-text)'}
                onMouseLeave={(e) => e.target.style.color = 'var(--riq-text-muted)'}
              >
                Terms of Service
              </a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
