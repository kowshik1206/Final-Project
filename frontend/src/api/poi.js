import axiosClient from './axiosClient';

const poiAPI = {
  getPoisForRoute: (data) => axiosClient.post('/pois/for-route', data),
};

export default poiAPI;
