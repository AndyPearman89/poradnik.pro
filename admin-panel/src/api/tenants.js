import apiClient from './client.js';

const NS = '/peartree/v1';

export const tenantApi = {
  /** List all tenants (admin-only). */
  list: (params = {}) =>
    apiClient.get(`${NS}/tenants`, { params }).then((r) => r.data),

  /** Get a single tenant by ID. */
  get: (id) =>
    apiClient.get(`${NS}/tenants/${id}`).then((r) => r.data),

  /** Create a new tenant. */
  create: (payload) =>
    apiClient.post(`${NS}/tenants`, payload).then((r) => r.data),

  /** Update an existing tenant. */
  update: (id, payload) =>
    apiClient.put(`${NS}/tenants/${id}`, payload).then((r) => r.data),

  /** Delete a tenant. */
  delete: (id) =>
    apiClient.delete(`${NS}/tenants/${id}`).then((r) => r.data),

  /** Toggle tenant active/inactive status. */
  toggleStatus: (id, active) =>
    apiClient.post(`${NS}/tenants/${id}/status`, { active }).then((r) => r.data),

  /** Get tenant statistics. */
  stats: (id) =>
    apiClient.get(`${NS}/tenants/${id}/stats`).then((r) => r.data),
};
