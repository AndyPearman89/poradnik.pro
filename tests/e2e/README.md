# End-to-End Tests

## Agenty Copilot (workspace)

Przewodnik agentów multi-repo: `../.github/agents/README.md`.

Smoke and integration tests for the consolidated **poradnik.pro** repository.

## Running Tests

### Prerequisites

```bash
# Install WP-CLI (if not available)
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

# Install PHPUnit (via Composer)
composer require --dev phpunit/phpunit ^10
```

### REST API smoke tests

```bash
WP_URL=https://poradnik.pro
bash tests/e2e/rest-smoke.sh "$WP_URL"
```

### PHP structure validation

```bash
php tests/e2e/validate-structure.php
```

---

## Test Files

| File | Description |
|------|-------------|
| `rest-smoke.sh` | HTTP smoke tests for all REST endpoints |
| `validate-structure.php` | Validates the repository directory structure |
| `theme-structure.php` | Validates required theme files are present |
