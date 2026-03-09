import axiosClient from './axiosClient';

const optimizeAPI = {
  // POST /trips/optimize-stops expects: { start, end?, stops: [{name, lat, lng}, ...] }
  optimizeStops: (data) => axiosClient.post('/trips/optimize-stops', data),
};

export default optimizeAPI;
