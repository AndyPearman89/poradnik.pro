# poradnik.pro

## Agenty Copilot (workspace)

Przewodnik agentów multi-repo: `../.github/agents/README.md`.

Consolidated repository for the **Poradnik.pro** portal – a standalone knowledge, Q&A, affiliate and lead monetisation platform built on WordPress.

## Repository Structure

```
poradnik.pro/
├── theme/          # GeneratePress child theme (frontend UI)
├── backend/        # MU-plugin platform core (WordPress backend logic)
├── migrations/     # Database schema reference and migration notes
└── docs/           # Deployment and architecture documentation
```

### `theme/`

WordPress child theme (`generatepress-child-poradnik`) previously maintained in the
[poradnik.pro-theme](https://github.com/AndyPearman89/poradnik.pro-theme) repository.

Zakres produktu i terminologia platformy 3.0 są opisane w
[PORADNIK_PRO_MASTER_PROMPT_CONTENT_ENGINE_3_0.md](../PORADNIK_PRO_MASTER_PROMPT_CONTENT_ENGINE_3_0.md).

Deploy to: `wp-content/themes/generatepress-child-poradnik/`

Key files:
- `functions.php` – Theme setup, CPT registration, REST API localization
- `assets/css/` – Compiled CSS (main, layout, components, responsive)
- `assets/js/` – Frontend JS (main, search, ajax, filters)
- `template-parts/` – Modular PHP templates (front-page, poradnik, ranking, recenzja, porownanie, Q&A, specialist, …)

### `backend/`

MU-plugin platform core (previously `platform-core/`), loaded by `poradnik-platform-loader.php`.

Deploy to: `wp-content/mu-plugins/platform-core/`

Subsystems:
- `Core/` – Bootstrap, ModuleRegistry, feature flags, event logger
- `Admin/` – Admin dashboard pages
- `Api/` – REST API kernel and controllers
- `Domain/` – Business logic (Affiliate, Ads, AI, SEO, Stripe, …)
- `Infrastructure/` – Database migrator, integrations
- `Modules/` – Feature modules (Rankings, Recenzje, Q&A, AiContent, Sponsored, Specialists, …)

### `migrations/`

Reference SQL files for each schema version. Migrations run automatically via
`backend/Infrastructure/Database/Migrator.php` on WordPress `init`.

See [migrations/README.md](migrations/README.md) for the full runbook.

### `docs/`

| Document | Description |
|----------|-------------|
| [cloud-deployment.md](docs/cloud-deployment.md) | Cloud deployment guide (AWS, GCP, Azure) |

---

## Quick Start (local development)

```bash
# 1. Clone into your WordPress mu-plugins directory
cd /var/www/html/wp-content/mu-plugins
git clone https://github.com/AndyPearman89/poradnik.pro.git poradnik-pro

# 2. Symlink or copy the loader and backend
cp poradnik-pro/poradnik-platform-loader.php .
ln -s poradnik-pro/backend backend
# alternative (legacy path also supported by loader):
# ln -s poradnik-pro/backend platform-core

# 3. Install the theme
ln -s poradnik-pro/theme ../themes/generatepress-child-poradnik

# 4. Activate the theme in WP Admin and run migrations
wp option delete poradnik_platform_db_version --allow-root
wp eval 'Poradnik\Platform\Infrastructure\Database\Migrator::maybeMigrate();' --allow-root
```

---

## Technology Stack

- WordPress 6+ / PHP 8.1+
- GeneratePress (parent theme) + custom child theme
- MySQL 8.0 / MariaDB 10.6+
- Stripe API (payments)
- OpenAI API (AI content & image generation)
- Schema.org structured data
- Google Analytics / Search Console

---

## REST API

Base namespace: `/wp-json/poradnik/v1/`

Quick REST smoke (PowerShell):

```powershell
.\tools\rest-smoke.ps1 -BaseUrl https://poradnik.pro -Strict
```

Script auto-detects active namespace (`poradnik/v1` or `peartree/v1`).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Platform health check |
| GET | `/affiliate/products` | List affiliate products |
| POST | `/affiliate/click` | Track affiliate click |
| POST | `/ads/click` | Track ad click |
| POST | `/ads/impression` | Track ad impression |
| GET | `/dashboard/statistics` | Advertiser dashboard stats |
| POST | `/ai/content/generate` | Generate AI content |
| POST | `/ai/image/generate` | Generate AI image |
| POST | `/seo/programmatic/build` | Build Content Engine 3.0 pages (poradniki / rankingi / affiliate / Q&A) |

---

## Feature Flags

Module feature flags are managed via **WP Admin → Tools → Poradnik Platform Modules**.
Each module can be toggled without code changes.

---

## Documentation

- [Cloud Deployment Guide](docs/cloud-deployment.md)
- [CI/CD Secrets](docs/CI_CD_SECRETS.md)
- [Platform Blueprint](PORADNIK-PRO-PLATFORM-BLUEPRINT.md)
- [Master Prompt Content Engine 3.0](../PORADNIK_PRO_MASTER_PROMPT_CONTENT_ENGINE_3_0.md)
- [Sprint 1 Tasks](PORADNIK-PRO-SPRINT-1-TASKS.md)
- [Migrations Runbook](migrations/README.md)
