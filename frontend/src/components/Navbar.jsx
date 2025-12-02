import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export default function Navbar() {
  const { isAuthenticated, user, logout } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/');
  };

  return (
    <nav className="bg-white shadow-md sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Brand */}
          <Link to="/" className="flex items-center">
            <span className="text-2xl font-bold text-blue-600">RouteIQ</span>
          </Link>

          {/* Desktop Menu */}
          <div className="hidden md:flex items-center space-x-8">
            <Link to="/" className="text-slate-700 hover:text-blue-600 transition">
              Home
            </Link>
            {isAuthenticated && (
              <>
                <Link
                  to="/plan"
                  className="text-slate-700 hover:text-blue-600 transition"
                >
                  Plan Trip
                </Link>
                <Link
                  to="/multi-stop"
                  className="text-slate-700 hover:text-blue-600 transition"
                >
                  Multi-Stop
                </Link>
                <Link
                  to="/trips"
                  className="text-slate-700 hover:text-blue-600 transition"
                >
                  Trips
                </Link>
                <Link
                  to="/analytics"
                  className="text-slate-700 hover:text-blue-600 transition"
                >
                  Analytics
                </Link>
              </>
            )}
          </div>

          {/* Auth Buttons / User Menu */}
          <div className="hidden md:flex items-center space-x-4">
            {isAuthenticated ? (
              <>
                <span className="text-slate-600">
                  {user?.name || user?.email}
                </span>
                <button
                  onClick={handleLogout}
                  className="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition"
                >
                  Logout
                </button>
              </>
            ) : (
              <>
                <Link
                  to="/login"
                  className="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition"
                >
                  Login
                </Link>
                <Link
                  to="/register"
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  Register
                </Link>
              </>
            )}
          </div>

          {/* Mobile Menu Toggle */}
          <div className="md:hidden">
            <button
              onClick={() => setMenuOpen(!menuOpen)}
              className="text-slate-700 focus:outline-none"
            >
              <svg
                className="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile Menu */}
        {menuOpen && (
          <div className="md:hidden pb-4 border-t">
            <Link to="/" className="block py-2 text-slate-700 hover:text-blue-600">
              Home
            </Link>
            {isAuthenticated && (
              <>
                <Link
                  to="/plan"
                  className="block py-2 text-slate-700 hover:text-blue-600"
                >
                  Plan Trip
                </Link>
                <Link
                  to="/multi-stop"
                  className="block py-2 text-slate-700 hover:text-blue-600"
                >
                  Multi-Stop
                </Link>
                <Link
                  to="/trips"
                  className="block py-2 text-slate-700 hover:text-blue-600"
                >
                  Trips
                </Link>
                <Link
                  to="/analytics"
                  className="block py-2 text-slate-700 hover:text-blue-600"
                >
                  Analytics
                </Link>
              </>
            )}
            {isAuthenticated ? (
              <button
                onClick={handleLogout}
                className="w-full mt-4 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
              >
                Logout
              </button>
            ) : (
              <div className="mt-4 space-y-2">
                <Link
                  to="/login"
                  className="block w-full text-center px-4 py-2 text-blue-600 border border-blue-600 rounded-lg"
                >
                  Login
                </Link>
                <Link
                  to="/register"
                  className="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg"
                >
                  Register
                </Link>
              </div>
            )}
          </div>
        )}
      </div>
    </nav>
  );
}
