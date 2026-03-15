import apiClient from './client.js';

const NS = '/peartree/v1';

export const vendorApi = {
  /** List all vendors. */
  list: (params = {}) =>
    apiClient.get(`${NS}/vendors`, { params }).then((r) => r.data),

  /** Get a single vendor. */
  get: (id) =>
    apiClient.get(`${NS}/vendors/${id}`).then((r) => r.data),

  /** Onboard / create a new vendor. */
  create: (payload) =>
    apiClient.post(`${NS}/vendors`, payload).then((r) => r.data),

  /** Update vendor profile. */
  update: (id, payload) =>
    apiClient.put(`${NS}/vendors/${id}`, payload).then((r) => r.data),

  /** Remove a vendor. */
  delete: (id) =>
    apiClient.delete(`${NS}/vendors/${id}`).then((r) => r.data),

  /** Approve vendor onboarding. */
  approve: (id) =>
    apiClient.post(`${NS}/vendors/${id}/approve`).then((r) => r.data),

  /** Suspend a vendor account. */
  suspend: (id) =>
    apiClient.post(`${NS}/vendors/${id}/suspend`).then((r) => r.data),

  /** Get vendor performance metrics. */
  metrics: (id) =>
    apiClient.get(`${NS}/vendors/${id}/metrics`).then((r) => r.data),
};
