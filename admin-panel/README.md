# Poradnik.pro Admin Panel

A modular React (Vite) frontend admin panel for managing the Poradnik.pro multisite marketplace platform.

## Features

| Feature | Description |
|---|---|
| **Tenant Management** | List, add, edit, delete, and toggle-status for all tenants |
| **Vendor Management** | Onboard, approve, suspend, and monitor vendor accounts |
| **Site Configuration** | Dynamic multisite and module configuration with live preview |
| **User & Role Management** | RBAC: admin, tenant owner (specialist), vendor/advertiser |
| **Articles** | Browse all content (posts, guides, rankings, reviews) |
| **Analytics** | Impressions, clicks, revenue charts per period |

## Architecture

```
admin-panel/
├── src/
│   ├── api/              – API service modules (axios, REST)
│   │   ├── auth.js       – JWT / WP authentication
│   │   ├── tenants.js    – Tenant CRUD
│   │   ├── vendors.js    – Vendor management
│   │   ├── siteConfig.js – Site configuration
│   │   └── dashboard.js  – Dashboard & analytics
│   ├── components/
│   │   ├── Layout/       – Sidebar, Header, Layout wrapper
│   │   ├── Auth/         – LoginForm
│   │   ├── Dashboard/    – StatsCard
│   │   └── common/       – Button, Input, Modal, Card, Badge, Spinner
│   ├── context/
│   │   └── AuthContext.jsx – Auth state & role detection
│   ├── pages/
│   │   ├── Dashboard.jsx
│   │   ├── Tenants.jsx
│   │   ├── Vendors.jsx
│   │   ├── SiteConfig.jsx
│   │   ├── Users.jsx
│   │   ├── Articles.jsx
│   │   └── Analytics.jsx
│   ├── styles/globals.css
│   ├── App.jsx
│   └── main.jsx
├── index.html
├── vite.config.js
└── package.json
```

## Backend REST Endpoints

New PHP REST controllers are registered under the `peartree/v1` namespace:

| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/wp-json/peartree/v1/tenants` | List / create tenants |
| GET/PUT/DELETE | `/wp-json/peartree/v1/tenants/{id}` | Get / update / delete tenant |
| POST | `/wp-json/peartree/v1/tenants/{id}/status` | Toggle active/inactive |
| GET | `/wp-json/peartree/v1/tenants/{id}/stats` | Tenant statistics |
| GET/POST | `/wp-json/peartree/v1/vendors` | List / create vendors |
| GET/PUT/DELETE | `/wp-json/peartree/v1/vendors/{id}` | Get / update / delete vendor |
| POST | `/wp-json/peartree/v1/vendors/{id}/approve` | Approve vendor |
| POST | `/wp-json/peartree/v1/vendors/{id}/suspend` | Suspend vendor |
| GET | `/wp-json/peartree/v1/vendors/{id}/metrics` | Vendor performance |
| GET/POST | `/wp-json/peartree/v1/site-config` | Get / save site config |
| POST | `/wp-json/peartree/v1/site-config/reset` | Reset to defaults |
| GET | `/wp-json/peartree/v1/sites` | List multisite network sites |

## Setup

### Prerequisites

- Node.js ≥ 18
- A running WordPress installation with the `platform-core` MU-plugin loaded
- [JWT Authentication for WP-API](https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/) plugin installed in WordPress

### Development

```bash
cd admin-panel
cp .env.example .env          # edit VITE_API_BASE_URL
npm install
npm run dev
```

Open [http://localhost:3000/admin-panel/](http://localhost:3000/admin-panel/)

### Production Build

```bash
npm run build
# Output in admin-panel/dist/
```

Deploy `dist/` to your web server. Update `vite.config.js → base` if serving from a different path.

## Authentication

Authentication uses **JWT Bearer tokens** via the WordPress REST API. On login, a JWT token is stored in `localStorage` and sent as `Authorization: Bearer <token>` on all API requests.

**Role-based Access Control:**

| Role | Pages accessible |
|---|---|
| `administrator` | All pages |
| `specialist` (Tenant Owner) | Dashboard, Vendors, Articles, Analytics |
| `advertiser` (Vendor) | Dashboard, Articles, Analytics |
