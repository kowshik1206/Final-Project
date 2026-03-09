import axiosClient from './axiosClient';

/**
 * Calculate train trip details
 * Train mode: realistic, deterministic, comfort-focused
 * 
 * @param {number} distance_km - Distance in kilometers
 * @returns {Promise<Object>} Train trip details { ok, mode, distance_km, estimated_cost, total_duration_min, boarding_buffer_min }
 */
async function calculateTrainTrip(distance_km) {
  try {
    if (!distance_km || distance_km <= 0) {
      throw new Error('Invalid distance_km: must be > 0');
    }

    const response = await axiosClient.post('/train/calculate', {
      distance_km: Number(distance_km)
    });

    if (!response.data?.ok) {
      throw new Error(response.data?.error || 'Train calculation failed');
    }

    return response.data;
  } catch (error) {
    console.error('Train trip calculation error:', error);
    throw error;
  }
}

const trainAPI = {
  calculateTrainTrip
};

export default trainAPI;
