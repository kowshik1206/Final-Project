import axiosClient from './axiosClient';

const routeAPI = {
  planRoute: (data) => axiosClient.post('/trips/plan', data),
};

export default routeAPI;
