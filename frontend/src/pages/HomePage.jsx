import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export default function HomePage() {
  const { isAuthenticated } = useAuth();

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100">
      {/* Hero Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-32 text-center">
        <h1 className="text-5xl sm:text-6xl font-bold text-slate-900 mb-6">
          RouteIQ – Intelligent Travel Cost & Route Planner
        </h1>
        <p className="text-xl text-slate-600 mb-12 max-w-3xl mx-auto">
          Plan smarter routes, compare multi-mode transportation costs, discover POIs along your
          journey, and optimize multi-stop itineraries—all in one intelligent platform.
        </p>

        {isAuthenticated ? (
          <Link
            to="/plan"
            className="inline-block px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-lg"
          >
            Plan Your First Trip
          </Link>
        ) : (
          <div className="space-x-4">
            <Link
              to="/login"
              className="inline-block px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold text-lg"
            >
              Get Started
            </Link>
            <Link
              to="/register"
              className="inline-block px-8 py-4 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition font-semibold text-lg"
            >
              Sign Up Free
            </Link>
          </div>
        )}
      </section>

      {/* Features Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <h2 className="text-4xl font-bold text-slate-900 text-center mb-16">Features</h2>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          {/* Feature 1 */}
          <div className="bg-white rounded-lg shadow-sm p-8 hover:shadow-md transition border border-slate-200">
            <div className="text-5xl mb-4">🗺️</div>
            <h3 className="text-xl font-semibold text-slate-900 mb-3">Multi-Mode Comparison</h3>
            <p className="text-slate-600">
              Compare routes across car, EV, train, and flight options instantly.
            </p>
          </div>

          {/* Feature 2 */}
          <div className="bg-white rounded-lg shadow-sm p-8 hover:shadow-md transition border border-slate-200">
            <div className="text-5xl mb-4">💰</div>
            <h3 className="text-xl font-semibold text-slate-900 mb-3">Smart Cost Analysis</h3>
            <p className="text-slate-600">
              Get accurate cost estimates with AI-powered recommendations.
            </p>
          </div>

          {/* Feature 3 */}
          <div className="bg-white rounded-lg shadow-sm p-8 hover:shadow-md transition border border-slate-200">
            <div className="text-5xl mb-4">📍</div>
            <h3 className="text-xl font-semibold text-slate-900 mb-3">POI Discovery</h3>
            <p className="text-slate-600">
              Find restaurants, fuel stations, chargers, and more along your route.
            </p>
          </div>

          {/* Feature 4 */}
          <div className="bg-white rounded-lg shadow-sm p-8 hover:shadow-md transition border border-slate-200">
            <div className="text-5xl mb-4">🎯</div>
            <h3 className="text-xl font-semibold text-slate-900 mb-3">Multi-Stop Optimizer</h3>
            <p className="text-slate-600">
              Automatically optimize routes with multiple stops for efficiency.
            </p>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="bg-blue-600 text-white py-20">
        <div className="max-w-4xl mx-auto text-center px-4">
          <h2 className="text-4xl font-bold mb-6">Ready to Plan Smarter Trips?</h2>
          <p className="text-lg mb-8 opacity-90">
            Join thousands of users optimizing their travel routes and costs every day.
          </p>
          {!isAuthenticated && (
            <Link
              to="/register"
              className="inline-block px-8 py-4 bg-white text-blue-600 rounded-lg hover:bg-slate-100 transition font-semibold"
            >
              Start Planning Now
            </Link>
          )}
        </div>
      </section>
    </div>
  );
}

