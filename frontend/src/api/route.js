import axiosClient from './axiosClient';

const routeAPI = {
  planRoute: async (data) => {
    try {
      console.log('🛣️ Route API - planRoute called with:', data);
      console.log('📤 Route API - posting to /plan-route');
      const res = await axiosClient.post('/plan-route', data, { timeout: 65000 });
      console.log('✅ Route API - response received:', res.status, res.data);
      return res;
    } catch (err) {
      console.group('❌ Route API - HTTP Error');
      console.error('Status:', err.response?.status);
      console.error('Status Text:', err.response?.statusText);
      console.error('Data:', err.response?.data);
      console.error('Error:', err.message);
      console.groupEnd();
      throw err;
    }
  },

  planTrainRoute: async (data) => {
    try {
      console.log('🚂 Train Route API - planTrainRoute called with:', data);
      console.log('📤 Train Route API - posting to /plan-route-train');
      const res = await axiosClient.post('/plan-route-train', data, { timeout: 65000 });
      console.log('✅ Train Route API - response received:', res.status, res.data);
      return res;
    } catch (err) {
      console.group('❌ Train Route API - HTTP Error');
      console.error('Status:', err.response?.status);
      console.error('Status Text:', err.response?.statusText);
      console.error('Data:', err.response?.data);
      console.error('Error:', err.message);
      console.groupEnd();
      throw err;
    }
  },
};

export default routeAPI;


