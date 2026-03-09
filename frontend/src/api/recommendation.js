import axiosClient from './axiosClient';

/**
 * Get mode recommendation for a trip
 * Uses deterministic scoring to recommend the best travel mode
 * @param {Object} payload - Trip data for recommendation
 * @param {number} payload.distance_km - Distance in kilometers (required)
 * @param {number} payload.car_cost - Cost for car mode (optional)
 * @param {number} payload.ev_cost - Cost for EV mode (optional)
 * @param {number} payload.ev_charging_stops - Number of charging stops for EV (optional)
 * @param {number} payload.passengers - Total number of passengers (required)
 * @param {number} payload.elders - Number of elderly passengers (optional)
 * @returns {Promise<Object>} Recommendation with { ok, recommended_mode, reason, scores }
 */
const recommendMode = async (payload) => {
  try {
    const response = await axiosClient.post('/recommend-mode', {
      distance_km: payload.distance_km,
      car_cost: payload.car_cost || null,
      ev_cost: payload.ev_cost || null,
      ev_charging_stops: payload.ev_charging_stops || null,
      passengers: payload.passengers || 1,
      elders: payload.elders || 0
    });
    return response.data;
  } catch (error) {
    console.error('Mode recommendation error:', error);
    throw error;
  }
};

/**
 * DEPRECATED: Use recommendMode() instead
 * Keeping for backward compatibility
 */
const getModeRecommendation = (payload) => 
  axiosClient.post('/recommend-mode', payload);

const recommendationAPI = {
  recommendMode,
  getModeRecommendation
};

export default recommendationAPI;

