import axiosClient from './axiosClient';

const costAPI = {
  calculateCost: (data) => axiosClient.post('/trips/calc-cost', data),
};

export default costAPI;
