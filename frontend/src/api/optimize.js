import axiosClient from './axiosClient';

const optimizeAPI = {
  optimizeStops: (data) => axiosClient.post('/trips/optimize-stops', data),
};

export default optimizeAPI;
