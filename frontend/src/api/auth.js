import axiosClient from './axiosClient';

const authAPI = {
  register: (data) => axiosClient.post('/register', data),
  login: (data) => axiosClient.post('/login', data),
  getMe: () => axiosClient.get('/me'),
  logout: () => axiosClient.post('/logout'),
};

export default authAPI;
