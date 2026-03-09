import axiosClient from './axiosClient';

/**
 * Calculate flight trip details
 * 
 * @param {number} distance_km - Distance in kilometers
 * @returns {Promise<Object>} Flight trip details { ok, mode, distance_km, estimated_cost, total_duration_min }
 */
async function calculateFlightTrip(distance_km) {
    try {
        if (!distance_km || distance_km <= 0) {
            throw new Error('Invalid distance_km: must be > 0');
        }

        const response = await axiosClient.post('/flight/calculate', {
            distance_km: Number(distance_km)
        });

        if (!response.data?.ok) {
            throw new Error(response.data?.error || 'Flight calculation failed');
        }

        return response.data;
    } catch (error) {
        console.error('Flight trip calculation error:', error);
        throw error;
    }
}

const flightAPI = {
    calculateFlightTrip
};

export default flightAPI;
