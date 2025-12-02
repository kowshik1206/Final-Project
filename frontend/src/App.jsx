import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { TripProvider } from './context/TripContext';
import Navbar from './components/Navbar';
import ProtectedRoute from './components/ProtectedRoute';

import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import PlanTripPage from './pages/PlanTripPage';
import MultiStopPage from './pages/MultiStopPage';
import TripsListPage from './pages/TripsListPage';
import AnalyticsPage from './pages/AnalyticsPage';

export default function App() {
  return (
    <Router>
      <AuthProvider>
        <TripProvider>
          <div className="min-h-screen bg-slate-50">
            <Navbar />
            <Routes>
              <Route path="/" element={<HomePage />} />
              <Route path="/login" element={<LoginPage />} />
              <Route path="/register" element={<RegisterPage />} />

              <Route
                path="/plan"
                element={
                  <ProtectedRoute>
                    <PlanTripPage />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/multi-stop"
                element={
                  <ProtectedRoute>
                    <MultiStopPage />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/trips"
                element={
                  <ProtectedRoute>
                    <TripsListPage />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/analytics"
                element={
                  <ProtectedRoute>
                    <AnalyticsPage />
                  </ProtectedRoute>
                }
              />

              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </div>
        </TripProvider>
      </AuthProvider>
    </Router>
  );
}

