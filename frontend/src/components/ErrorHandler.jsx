import React from 'react';
import { useNavigate } from 'react-router-dom';

/**
 * PHASE 5: Error Intelligence Component
 * Maps error codes to:
 * 1. User-friendly message
 * 2. Actionable buttons for recovery
 * 3. Contextual help text
 */

const ERROR_INTELLIGENCE_MAP = {
  RATE_LIMITED: {
    icon: '⏱️',
    title: 'Too Many Requests',
    message: 'You\'ve made too many route planning requests. Please wait before trying again.',
    actions: [
      {
        label: '⏳ Wait 10 Minutes',
        type: 'info',
        handler: () => {
          // Disable button for 10 minutes
          const btn = document.querySelector('[data-error-action="RATE_LIMITED"]');
          if (btn) {
            btn.disabled = true;
            setTimeout(() => { btn.disabled = false; }, 600000);
          }
        }
      }
    ]
  },
  OSRM_DOWN: {
    icon: '🗺️',
    title: 'Routing Service Unavailable',
    message: 'The routing service is temporarily down. Your route cannot be planned right now.',
    actions: [
      {
        label: '🔄 Try Again',
        type: 'primary',
        handler: () => window.location.reload()
      },
      {
        label: '📞 Contact Support',
        type: 'secondary',
        handler: () => window.open('mailto:support@routeiq.com')
      }
    ]
  },
  INVALID_SIGNATURE: {
    icon: '🔐',
    title: 'Route Expired',
    message: 'Your route data has expired or been modified. Please re-plan your journey.',
    actions: [
      {
        label: '🔄 Re-plan Journey',
        type: 'primary',
        handler: () => window.location.reload()
      }
    ]
  },
  NO_POIS_FOUND: {
    icon: '📍',
    title: 'No Facilities Found',
    message: 'No points of interest (fuel, chargers, restrooms, etc.) found along this route.',
    actions: [
      {
        label: '📍 Expand Radius',
        type: 'primary',
        handler: () => {
          // This could trigger expanding search radius in parent component
          console.log('User wants to expand search radius');
        }
      },
      {
        label: '➡️ Continue Anyway',
        type: 'secondary',
        handler: () => {
          console.log('User continuing without POIs');
        }
      }
    ]
  },
  INVALID_INPUT: {
    icon: '❌',
    title: 'Invalid Location',
    message: 'One or both of your locations couldn\'t be found. Please check spelling and try again.',
    actions: [
      {
        label: '✏️ Edit Locations',
        type: 'primary',
        handler: () => {
          // Scroll to form
          document.querySelector('form')?.scrollIntoView({ behavior: 'smooth' });
        }
      },
      {
        label: '📖 Example Cities',
        type: 'secondary',
        handler: () => {
          alert('Try: Delhi, Mumbai, Bangalore, Hyderabad, Chennai, Pune, Kolkata');
        }
      }
    ]
  },
  DUPLICATE_ENTRY: {
    icon: '📋',
    title: 'Trip Already Saved',
    message: 'You\'ve already saved this exact trip. Check your saved trips list.',
    actions: [
      {
        label: '📋 View My Trips',
        type: 'primary',
        navigateTo: '/trips'
      }
    ]
  },
  DB_ERROR: {
    icon: '🔧',
    title: 'Server Error',
    message: 'A server error occurred while processing your request. Please try again.',
    actions: [
      {
        label: '🔄 Try Again',
        type: 'primary',
        handler: () => window.location.reload()
      }
    ]
  },
  VALIDATION_FAIL: {
    icon: '⚠️',
    title: 'Data Validation Failed',
    message: 'Your trip data failed validation. This may indicate a technical issue.',
    actions: [
      {
        label: '🔄 Re-plan Journey',
        type: 'primary',
        handler: () => window.location.reload()
      },
      {
        label: '💬 Chat Support',
        type: 'secondary',
        handler: () => window.open('https://support.routeiq.com')
      }
    ]
  },
  NO_MODE_SELECTED: {
    icon: '🚗',
    title: 'Select a Transportation Mode',
    message: 'Please select one of the available transportation modes (Car, EV, Train, etc.) before saving.',
    actions: [
      {
        label: '⬆️ Select Mode Above',
        type: 'primary',
        handler: () => {
          document.querySelector('[class*="Cost"]')?.scrollIntoView({ behavior: 'smooth' });
        }
      }
    ]
  },
  ROUTE_PLANNING_FAILED: {
    icon: '🗺️',
    title: 'Route Planning Failed',
    message: 'We couldn\'t find a route between these locations. Try different cities or check the spelling.',
    actions: [
      {
        label: '✏️ Try Different Cities',
        type: 'primary',
        handler: () => {
          document.querySelector('form')?.scrollIntoView({ behavior: 'smooth' });
        }
      }
    ]
  },
  SAVE_FAILED: {
    icon: '💾',
    title: 'Failed to Save Trip',
    message: 'Your trip couldn\'t be saved due to a server error. Your selections are still here.',
    actions: [
      {
        label: '💾 Try Saving Again',
        type: 'primary',
        handler: () => console.log('Retry save')
      }
    ]
  }
};

export default function ErrorHandler({ error, onRetry, onDismiss }) {
  const navigate = useNavigate();

  if (!error) return null;

  // Get error intelligence data
  const errorCode = error.code || 'UNKNOWN';
  const errorInfo = ERROR_INTELLIGENCE_MAP[errorCode] || {
    icon: '❌',
    title: 'Error',
    message: error.message || 'An unexpected error occurred.',
    actions: [
      {
        label: '🔄 Try Again',
        type: 'primary',
        handler: onRetry
      }
    ]
  };

  const handleAction = (action) => {
    if (action.navigateTo) {
      navigate(action.navigateTo);
      onDismiss?.();
    } else if (action.handler) {
      action.handler();
      // Only dismiss if handler completed (non-navigation actions don't dismiss)
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 animate-in">
      <div className="bg-white rounded-lg shadow-2xl max-w-md w-full p-6 animate-in slide-in-from-bottom-4">
        {/* Header with icon */}
        <div className="flex items-start gap-4 mb-4">
          <div className="text-4xl flex-shrink-0">{errorInfo.icon}</div>
          <div className="flex-1">
            <h2 className="text-xl font-bold text-slate-900">{errorInfo.title}</h2>
          </div>
        </div>

        {/* Message */}
        <p className="text-slate-600 mb-6 leading-relaxed">
          {errorInfo.message}
        </p>

        {/* Action buttons */}
        <div className="space-y-2">
          {errorInfo.actions.map((action, idx) => (
            <button
              key={idx}
              data-error-action={errorCode}
              onClick={() => handleAction(action)}
              className={`w-full py-2 px-4 rounded-lg font-medium transition ${
                action.type === 'primary'
                  ? 'bg-blue-600 text-white hover:bg-blue-700'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}
            >
              {action.label}
            </button>
          ))}

          {/* Dismiss button if there are other actions */}
          {errorInfo.actions.length > 0 && (
            <button
              onClick={onDismiss}
              className="w-full py-2 px-4 rounded-lg font-medium text-slate-600 hover:bg-slate-50 transition"
            >
              ✕ Dismiss
            </button>
          )}
        </div>

        {/* Footer help text */}
        <div className="mt-6 pt-4 border-t border-slate-200">
          <p className="text-xs text-slate-500 text-center">
            💡 Still having trouble? <a href="mailto:support@routeiq.com" className="text-blue-600 hover:underline">Contact support</a>
          </p>
        </div>
      </div>
    </div>
  );
}

export { ERROR_INTELLIGENCE_MAP };
