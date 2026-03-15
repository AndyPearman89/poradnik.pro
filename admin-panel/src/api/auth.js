import apiClient from './client.js';

/**
 * Authenticate via WordPress JWT or Application Password.
 * Endpoint: POST /wp-json/jwt-auth/v1/token  (JWT Authentication for WP-API plugin)
 * Falls back to a nonce-based session check.
 */
export async function login({ username, password }) {
  const { data } = await apiClient.post('/jwt-auth/v1/token', { username, password });
  const token = data?.token;
  if (token) {
    sessionStorage.setItem('wp_token', token);
  }
  return data;
}

export async function logout() {
  sessionStorage.removeItem('wp_token');
  sessionStorage.removeItem('wp_nonce');
}

/**
 * Validate the stored JWT token and return the current user's info.
 */
export async function validateToken() {
  const { data } = await apiClient.post('/jwt-auth/v1/token/validate');
  return data;
}

/**
 * Return current WP user via REST API.
 */
export async function getCurrentUser() {
  const { data } = await apiClient.get('/wp/v2/users/me?context=edit');
  return data;
}
