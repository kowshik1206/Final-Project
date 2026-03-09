import axiosClient from './axiosClient';

/**
 * Calculate bus trip details
 * Bus mode: station-based, abstract routing (like train)
 * 
 * @param {number} distance_km - Distance in kilometers
 * @returns {Promise<Object>} Bus trip details { ok, mode, distance_km, estimated_cost, total_duration_min, stops }
 */
async function calculateBusTrip(distance_km) {
  try {
    if (!distance_km || distance_km <= 0) {
      throw new Error('Invalid distance_km: must be > 0');
    }

    const response = await axiosClient.post('/bus/calculate', {
      distance_km: Number(distance_km)
    });

    if (!response.data?.ok) {
      throw new Error(response.data?.error || 'Bus calculation failed');
    }

    return response.data;
  } catch (error) {
    console.error('Bus trip calculation error:', error);
    throw error;
  }
}

const busAPI = {
  calculateBusTrip
};

export default busAPI;
