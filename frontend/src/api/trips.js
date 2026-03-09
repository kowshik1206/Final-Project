import axiosClient from './axiosClient';

export default {
  saveTrip: (payload) => axiosClient.post('/trips', payload),
  getTrips: ({ limit = 100, offset = 0 } = {}) => axiosClient.get('/trips', { params: { limit, offset } }),
  getTrip: (id) => axiosClient.get('/trips', { params: { id } }),
  deleteTrip: (id) => axiosClient.delete('/trips', { data: { id } }),
  getDashboardSummary: () => axiosClient.get('/analytics')
};
