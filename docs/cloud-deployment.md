# Cloud Deployment Guide – Poradnik.pro

## Overview

This guide covers deploying the consolidated **poradnik.pro** repository to cloud environments.
Canonical product scope: `../../PORADNIK_PRO_MASTER_PROMPT_CONTENT_ENGINE_3_0.md`

Poradnik.pro should be deployed as an **independent WordPress platform** with the following active product areas:
- poradniki SEO
- recenzje i porownania afiliacyjne
- Q&A
- marketplace specjalistow
- lead generation
- premium visibility / ads

The repository bundles:

- `theme/` – GeneratePress child theme (WordPress frontend)
- `backend/` – MU-plugin platform core (WordPress backend logic)
- `migrations/` – Database schema reference and migration notes
- `docs/` – This documentation

---

## Repository Structure

```
poradnik.pro/
├── theme/                          # GeneratePress child theme
│   ├── style.css                   # Theme metadata + CSS variables
│   ├── functions.php               # Theme setup, CPT, enqueues
│   ├── assets/
│   │   ├── css/                    # Compiled CSS (main, layout, components, responsive)
│   │   └── js/                     # Frontend JS (main, search, ajax, filters)
│   └── template-parts/             # Modular PHP templates (poradnik, recenzja, porownanie, ranking, Q&A, specialist)
├── backend/                        # MU-plugin platform core
│   ├── Core/                       # Bootstrap, ModuleRegistry, runtime
│   ├── Admin/                      # Admin pages and flags UI
│   ├── Api/                        # REST API kernel and controllers
│   ├── Domain/                     # Business logic (Affiliate, Ads, AI, SEO, Leads, Specialists, …)
│   ├── Infrastructure/             # Database migrator, integrations
│   └── Modules/                    # Feature modules with feature-flag bootstraps
├── migrations/
│   ├── README.md                   # Migration runbook
│   └── v1.4.0.sql                  # Reference SQL schema
├── docs/                           # Deployment and architecture docs
├── poradnik-platform-loader.php    # MU-plugin entry point
└── README.md
```

---

## Prerequisites

| Requirement | Minimum version |
|-------------|----------------|
| PHP | 8.1 |
| WordPress | 6.4 |
| MySQL / MariaDB | 8.0 / 10.6 |
| GeneratePress (parent theme) | 3.x |
| ACF Pro | 6.x |

Recommended runtime integrations:
- Redis object cache
- WP-CLI
- Search Console / Analytics credentials
- OpenAI API key for Content Engine 3.0 jobs

---

## Cloud Provider Setup

### AWS (recommended for production)

#### Compute
- **EC2** – `t3.medium` minimum for WordPress + PHP 8.1 (Nginx + PHP-FPM).
- **Elastic Beanstalk** – Use the PHP 8.1 platform for zero-downtime deployments.
- **ECS / Fargate** – Containerised option; use the Dockerfile in `docs/docker/`.

#### Storage
- **RDS** – MySQL 8.0 (Multi-AZ for production). Use `db.t3.micro` for staging.
- **S3 + CloudFront** – Offload `wp-content/uploads/` to S3 via WP Offload Media.
- **ElastiCache (Redis)** – Object cache for WordPress; configure via `wp-config.php`.

#### DNS & TLS
- Route 53 for DNS, ACM for free TLS certificates attached to a CloudFront distribution.

---

### Google Cloud Platform

- **Cloud Run** – Serverless container option; scale to zero on staging.
- **Cloud SQL** – Managed MySQL 8.0.
- **Cloud Storage** – Media offload via WP Offload Media (GCS adapter).
- **Cloud CDN** – Front CloudFront equivalent.

---

### Azure

- **App Service** – PHP 8.1 plan (P1v3+).
- **Azure Database for MySQL** – Flexible server, MySQL 8.0.
- **Azure Blob Storage** – Media offload.
- **Azure CDN** – For static asset caching.

---

## WordPress Configuration

### wp-config.php keys

```php
// Database
define('DB_NAME',     getenv('DB_NAME'));
define('DB_USER',     getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST',     getenv('DB_HOST') ?: 'localhost');

// Redis object cache
define('WP_REDIS_HOST', getenv('REDIS_HOST') ?: '127.0.0.1');
define('WP_REDIS_PORT', 6379);

// Disable file editing in admin
define('DISALLOW_FILE_EDIT', true);

// Force HTTPS
define('FORCE_SSL_ADMIN', true);

// Stripe (used by backend/Modules/SaasPlans and Stripe modules)
define('PORADNIK_STRIPE_SECRET_KEY',       getenv('STRIPE_SECRET_KEY'));
define('PORADNIK_STRIPE_WEBHOOK_SECRET',   getenv('STRIPE_WEBHOOK_SECRET'));
define('PORADNIK_STRIPE_PRICE_BASIC',      getenv('STRIPE_PRICE_BASIC'));
define('PORADNIK_STRIPE_PRICE_FEATURED',   getenv('STRIPE_PRICE_FEATURED'));
define('PORADNIK_STRIPE_PRICE_HOMEPAGE',   getenv('STRIPE_PRICE_HOMEPAGE'));

// AI (used by backend/Modules/AiContent and AiImage modules)
define('PORADNIK_OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
```

### Environment variables (`.env.example`)

```
DB_NAME=poradnik_pro
DB_USER=wp_user
DB_PASSWORD=secret
DB_HOST=127.0.0.1
REDIS_HOST=127.0.0.1
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_BASIC=price_...
STRIPE_PRICE_FEATURED=price_...
STRIPE_PRICE_HOMEPAGE=price_...
OPENAI_API_KEY=sk-...
```

---

## WordPress File Placement

This repository maps to the following WordPress paths:

| Repository path | WordPress path |
|-----------------|----------------|
| `theme/` | `wp-content/themes/generatepress-child-poradnik/` |
| `backend/` | `wp-content/mu-plugins/platform-core/` |
| `poradnik-platform-loader.php` | `wp-content/mu-plugins/poradnik-platform-loader.php` |

### Deployment script (rsync example)

```bash
# Theme
rsync -az --delete \
  theme/ \
  /var/www/html/wp-content/themes/generatepress-child-poradnik/

# MU-plugin loader
cp poradnik-platform-loader.php \
  /var/www/html/wp-content/mu-plugins/poradnik-platform-loader.php

# MU-plugin backend
rsync -az --delete \
  backend/ \
  /var/www/html/wp-content/mu-plugins/platform-core/
```

---

## Database Migrations

Migrations run **automatically** on every `init` hook via `backend/Infrastructure/Database/Migrator.php`.
The `poradnik_platform_db_version` WordPress option tracks the installed version.

To force a re-run in staging:

```bash
wp option delete poradnik_platform_db_version --allow-root
wp eval 'Poradnik\Platform\Infrastructure\Database\Migrator::maybeMigrate();' --allow-root
```

Reference SQL is in `migrations/v1.4.0.sql`.

---

## CI/CD Pipeline (GitHub Actions example)

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Sync theme
        uses: easingthemes/ssh-deploy@main
        with:
          SSH_PRIVATE_KEY: ${{ secrets.SSH_KEY }}
          REMOTE_HOST: ${{ secrets.SSH_HOST }}
          REMOTE_USER: deploy
          SOURCE: theme/
          TARGET: /var/www/html/wp-content/themes/generatepress-child-poradnik/

      - name: Sync backend
        uses: easingthemes/ssh-deploy@main
        with:
          SSH_PRIVATE_KEY: ${{ secrets.SSH_KEY }}
          REMOTE_HOST: ${{ secrets.SSH_HOST }}
          REMOTE_USER: deploy
          SOURCE: backend/
          TARGET: /var/www/html/wp-content/mu-plugins/platform-core/
```

---

## Feature Flags

Module feature flags are managed via **WP Admin → Tools → Poradnik Platform Modules**.
Each module can be enabled/disabled without code changes.

The stored option is `poradnik_platform_module_flags` (JSON array of enabled module slugs).

---

## Health Check

The platform exposes a health endpoint:

```
GET /wp-json/poradnik/v1/health
```

Expected response:

```json
{ "status": "ok", "version": "1.4.0" }
```

Use this endpoint in load-balancer health checks and uptime monitors.

---

## Rollback

See `PORADNIK-PRO-P1-RUNBOOK-ROLLBACK-2026-03-13.md` for the full rollback runbook.

Quick rollback steps:
1. Revert the previous theme and backend code via deployment tooling.
2. If schema changes were applied: restore from the pre-deployment RDS snapshot.
3. Verify `/wp-json/poradnik/v1/health` returns `200 OK`.
