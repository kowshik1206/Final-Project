import React, { createContext, useState } from 'react';

export const TripContext = createContext();

// PHASE 5: Explicit Journey States
// States define what the user is doing and what UI should show
const JourneyStates = {
  IDLE: 'IDLE',           // Initial state: no route planned
  PLANNING: 'PLANNING',   // Planning route: loading, no interaction
  PLANNED: 'PLANNED',     // Route planned: can select mode and save
  SAVING: 'SAVING',       // Saving trip: no interaction, show loader
  SAVED: 'SAVED',         // Trip saved: redirect imminent
  ERROR: 'ERROR'          // Error occurred: show message + recovery actions
};

export { JourneyStates };

export function TripProvider({ children }) {
  const [currentTrip, setCurrentTrip] = useState(null);
  const [lastSearch, setLastSearch] = useState({ source: '', destination: '' });

  // PHASE 5: Explicit Journey State Machine
  const [journeyState, setJourneyState] = useState(JourneyStates.IDLE);
  const [journeyError, setJourneyError] = useState(null); // { code, message, action, actionLabel }

  // Add a stop to the trip itinerary
  const addStop = (stop) => {
    setCurrentTrip(prev => {
      if (!prev) return prev;
      return {
        ...prev,
        stops: [...(prev.stops || []), stop]
      };
    });
  };

  // Remove a stop from the trip itinerary by id
  const removeStop = (stopId) => {
    setCurrentTrip(prev => {
      if (!prev) return prev;
      return {
        ...prev,
        stops: (prev.stops || []).filter(s => s.id !== stopId)
      };
    });
  };

  // Clear all stops
  const clearStops = () => {
    setCurrentTrip(prev => {
      if (!prev) return prev;
      return { ...prev, stops: [] };
    });
  };

  return (
    <TripContext.Provider value={{
      currentTrip,
      setCurrentTrip,
      addStop,
      removeStop,
      clearStops,
      lastSearch,
      setLastSearch,
      // PHASE 5: State machine methods
      journeyState,
      setJourneyState,
      journeyError,
      setJourneyError
    }}>
      {children}
    </TripContext.Provider>
  );
}
