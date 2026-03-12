# Migration Notes

## Scope
Refaktor i rozszerzenie pluginu do architektury SaaS-ready z zachowaniem kompatybilności.

## Backward Compatibility
- Zachowano istniejące slugi stron admin i shortcode API.
- Zachowano mechanizm migracji danych historycznych (`DataMigrator`).
- Nie usunięto dotychczasowych tabel, tylko rozszerzono warstwę dostępową.

## New Components
- `src/Admin/DashboardPage.php`
- `src/Admin/ToolsPage.php`
- `src/Rest/StatsController.php`
- `src/Rest/CatalogController.php`

## Changed Components
- `src/Core/ServiceProvider.php` — rejestracja nowych modułów admin i REST.
- `src/Admin/AdminMenu.php` — nowa struktura menu enterprise.
- `src/SEO/Infrastructure/SeoPageRepository.php` — cache + paginacja.
- `src/Affiliate/Infrastructure/AffiliateRepository.php` — paginacja, metryki, recent activity, cache utilities.

## Operational Notes
- Po deploy zalecane: wejść do `Narzędzia` i wykonać `Odśwież rewrite rules`.
- W razie migracji środowiska wykonać `Wyczyść cache pluginu`.

## REST Auth
- Endpointy `ppae/v1/*` są `admin-only` (`manage_options`).
- Do wywołań z panelu JS używać nonce WordPress + cookie auth.

## Next Upgrade Path
- Dodać API write-side (POST/PUT/DELETE) z audit logging.
- Dodać testy integracyjne dla repozytoriów i REST.
- Dodać tenant-aware scoping dla multi-instance SaaS.
