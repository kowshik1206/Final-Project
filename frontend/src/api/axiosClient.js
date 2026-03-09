import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
const API_TIMEOUT = import.meta.env.VITE_API_TIMEOUT || 60000; // 60 seconds (increased from 30s)

const axiosClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: API_TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
  },
});

export default axiosClient;
