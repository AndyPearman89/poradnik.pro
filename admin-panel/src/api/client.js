import axios from 'axios';

const BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

const apiClient = axios.create({
  baseURL: `${BASE_URL}/wp-json`,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// Attach WP nonce or JWT token on every request
apiClient.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('wp_token') || localStorage.getItem('wp_token');
  const nonce = import.meta.env.VITE_WP_NONCE || sessionStorage.getItem('wp_nonce');

  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  if (nonce) {
    config.headers['X-WP-Nonce'] = nonce;
  }
  return config;
});

// Global error handler
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      sessionStorage.removeItem('wp_token');
      localStorage.removeItem('wp_token');
      window.dispatchEvent(new Event('auth:logout'));
    }
    return Promise.reject(error);
  }
);

export default apiClient;
