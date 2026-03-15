import apiClient from './client.js';

const NS = '/peartree/v1';

export const dashboardApi = {
  /** Admin overview stats. */
  overview: () =>
    apiClient.get(`${NS}/dashboard`).then((r) => r.data),

  /** Article list with filters. */
  articles: (params = {}) =>
    apiClient.get(`${NS}/articles`, { params }).then((r) => r.data),

  /** Analytics data. */
  analytics: (params = {}) =>
    apiClient.get(`${NS}/analytics`, { params }).then((r) => r.data),

  /** SaaS plans. */
  plans: () =>
    apiClient.get(`${NS}/plans`).then((r) => r.data),
};
