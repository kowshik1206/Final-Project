import axiosClient from './axiosClient';

const costAPI = {
  calculateCost: (data) => axiosClient.post('/calc-cost', data),
};

export default costAPI;
