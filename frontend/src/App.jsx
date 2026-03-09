import React from 'react';
import { createBrowserRouter, RouterProvider, Navigate, Outlet } from 'react-router-dom';
import { TripProvider } from './context/TripContext';
import { AuthProvider } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import ProtectedRoute from './components/ProtectedRoute';

import HomePage from './pages/HomePage';
import PlanTripPage from './pages/PlanTripPage';
import MultiStopPage from './pages/MultiStopPage';
import TripsListPage from './pages/TripsListPage';
import AnalyticsPage from './pages/AnalyticsPage';
import CarConfigurator from './pages/CarConfigurator';
import EvCalculatorPage from './pages/EvCalculatorPage';
import TripDetailsPage from './pages/TripDetailsPage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';

// Journey pages
import TrainJourneyPage from './pages/TrainJourneyPage';
import FlightJourneyPage from './pages/FlightJourneyPage';
import BusJourneyPage from './pages/BusJourneyPage';

// Layout wrapper component
function RootLayout() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <TripProvider>
          <div className="min-h-screen riq-app-shell text-slate-900 dark:text-slate-100 transition-colors duration-300" style={{ width: '100%', overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
            <Navbar />
            <main className="relative w-full flex-1" style={{ paddingTop: '3.5rem' }}>
              <Outlet />
            </main>
            <Footer />
          </div>
        </TripProvider>
      </AuthProvider>
    </ThemeProvider>
  );
}

// Router configuration with v7 future flags
const router = createBrowserRouter(
  [
    {
      element: <RootLayout />,
      children: [
        { path: '/', element: <HomePage /> },
        { path: '/login', element: <LoginPage /> },
        { path: '/register', element: <RegisterPage /> },
        
        // Protected Routes
        { path: '/plan', element: <ProtectedRoute><PlanTripPage /></ProtectedRoute> },
        { path: '/plan-trip', element: <ProtectedRoute><PlanTripPage /></ProtectedRoute> },
        { path: '/car-configurator', element: <ProtectedRoute><CarConfigurator /></ProtectedRoute> },
        { path: '/journey/car', element: <ProtectedRoute><CarConfigurator /></ProtectedRoute> },
        { path: '/journey/ev', element: <ProtectedRoute><EvCalculatorPage /></ProtectedRoute> },
        { path: '/journey/bus', element: <ProtectedRoute><BusJourneyPage /></ProtectedRoute> },
        { path: '/multi-stop', element: <ProtectedRoute><MultiStopPage /></ProtectedRoute> },
        { path: '/trips', element: <ProtectedRoute><TripsListPage /></ProtectedRoute> },
        { path: '/trips/:id', element: <ProtectedRoute><TripDetailsPage /></ProtectedRoute> },
        { path: '/analytics', element: <ProtectedRoute><AnalyticsPage /></ProtectedRoute> },

        // Journey pages (Protected)
        { path: '/journey/train', element: <ProtectedRoute><TrainJourneyPage /></ProtectedRoute> },
        { path: '/journey/flight', element: <ProtectedRoute><FlightJourneyPage /></ProtectedRoute> },

        { path: '*', element: <Navigate to="/" replace /> },
      ],
    },
  ],
  {
    future: {
      v7_startTransition: true,
      v7_relativeSplatPath: true,
    },
  }
);

export default function App() {
  return <RouterProvider router={router} />;
}
