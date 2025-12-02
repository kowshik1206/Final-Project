import axiosClient from './axiosClient';

const tripsAPI = {
  saveTrip: (data) => axiosClient.post('/trips/save', data),
  getTrips: () => axiosClient.get('/trips'),
  getTrip: (id) => axiosClient.get(`/trips/${id}`),
  getDashboardSummary: () => axiosClient.get('/dashboard/summary'),
};

export default tripsAPI;
