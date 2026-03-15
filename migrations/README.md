# Migrations

Database schema migrations for the Poradnik.pro platform.

## How It Works

Migrations are managed by `backend/Infrastructure/Database/Migrator.php` using WordPress `dbDelta()`.
The current schema version is tracked in the `poradnik_platform_db_version` WordPress option.

On each WordPress `init`, the `Migrator::maybeMigrate()` method compares the installed version against
`SCHEMA_VERSION`. If the installed version is older, all `CREATE TABLE` statements in `Migrator::schema()`
are re-run via `dbDelta`, which safely adds missing columns and indexes without dropping existing data.

## Schema Files

SQL reference files in this directory document the current table structure for each version.
They are **not** executed directly – the authoritative source is `Migrator.php`.

| File | Description |
|------|-------------|
| `v1.4.0.sql` | Current production schema (v1.4.0) |

## Adding a New Migration

1. Increment `SCHEMA_VERSION` in `backend/Infrastructure/Database/Migrator.php`.
2. Add or modify the relevant `CREATE TABLE` statement in `Migrator::schema()`.
3. Export the updated schema to a new versioned `.sql` file here for documentation.
4. Test with `dbDelta` by clearing the `poradnik_platform_db_version` option locally.

## Tables

| Table | Purpose |
|-------|---------|
| `{prefix}poradnik_affiliate_products` | Affiliate product catalogue |
| `{prefix}poradnik_affiliate_clicks` | Affiliate click tracking |
| `{prefix}poradnik_affiliate_categories` | Affiliate product categories |
| `{prefix}poradnik_ad_campaigns` | Ad campaign definitions |
| `{prefix}poradnik_ad_slots` | Ad slot placements |
| `{prefix}poradnik_ad_clicks` | Ad click events |
| `{prefix}poradnik_ad_impressions` | Ad impression events |
| `{prefix}poradnik_sponsored_articles` | Sponsored article orders |
| `{prefix}poradnik_stripe_sessions` | Stripe webhook idempotency log |
| `{prefix}poradnik_image_generation_queue` | AI image generation queue |
