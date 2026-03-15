import apiClient from './client.js';

const NS = '/peartree/v1';

export const siteConfigApi = {
  /** Fetch all site configuration options. */
  get: () =>
    apiClient.get(`${NS}/site-config`).then((r) => r.data),

  /** Save site configuration. */
  save: (payload) =>
    apiClient.post(`${NS}/site-config`, payload).then((r) => r.data),

  /** Reset configuration to defaults. */
  reset: () =>
    apiClient.post(`${NS}/site-config/reset`).then((r) => r.data),

  /** Get multisite / network sites list. */
  sites: () =>
    apiClient.get(`${NS}/sites`).then((r) => r.data),

  /** Get configuration for a specific site. */
  getSiteConfig: (siteId) =>
    apiClient.get(`${NS}/sites/${siteId}/config`).then((r) => r.data),

  /** Save configuration for a specific site. */
  saveSiteConfig: (siteId, payload) =>
    apiClient.post(`${NS}/sites/${siteId}/config`, payload).then((r) => r.data),
};
