# platform-core (MU Plugin)

## Status zmian (2026-03-13)

### API / Security
- Utwardzono endpointy kampanii w `Api/Controllers/DashboardController.php`.
- Dodano scoping `advertiser_id` do zalogowanego użytkownika (dla nie-admina).
- Dodano kontrolę własności kampanii dla operacji update/delete/pause.
- Zachowano pełny dostęp administracyjny dla kont z uprawnieniami platformowymi.

### Role i kompatybilność
- Ujednolicono obsługę ról reklamodawcy: `advertiser` oraz `reklamodawca`.
- API akceptuje obie role przy autoryzacji dostępu do dashboard/campaign endpoints.

### Integracja z motywem
- Formularz `new-campaign` z motywu korzysta z REST (`/wp-json/poradnik/v1/api/campaigns`) i `wp_rest` nonce.
- Ścieżka zapisu kampanii jest gotowa do dalszych testów E2E po stronie UI.

### Testy i środowisko
- Zweryfikowano składnię PHP kontrolerów po zmianach.
- Przeprowadzono testy HTTP/REST (autoryzacja i routing).
- Konto testowe `REKLAMAPRO` istnieje w środowisku lokalnym do dalszych testów integracyjnych.

---

## Multi-Tenancy & Vendor Management (v2.0.0)

Scalone z `peartree.pro` — centralne zarządzanie portalami marketplace i środowiskami multisite.

### Architektura

```
platform-core/
├── Domain/
│   └── Tenant/
│       ├── Tenant.php             # Value object (niezmienialny rekord portalu)
│       ├── TenantRepository.php   # Warstwa CRUD (tenants + tenant_vendors)
│       └── TenantService.php      # Logika biznesowa (provisioning, lifecycle, vendor mgmt)
├── Api/
│   └── Controllers/
│       └── TenantController.php   # REST API (peartree/v1/tenants)
├── Admin/
│   └── TenantManagementPage.php   # Panel WP Admin → PearTree → Tenants
├── Modules/
│   └── TenantManager/
│       ├── Module.php             # Bootstrap modułu + widget dashboardu
│       └── bootstrap.php          # Punkt wejścia modułu
└── Infrastructure/
    └── Database/
        └── Migrator.php           # Schemat bazy danych (v2.0.0 — tabele tenant)
```

### Tabele bazy danych

| Tabela | Opis |
|--------|------|
| `{prefix}poradnik_tenants` | Portale marketplace (jeden wiersz = jeden tenant) |
| `{prefix}poradnik_tenant_vendors` | Przypisanie sprzedawców (vendor) do tenantów |

### REST API — `peartree/v1/tenants`

Wszystkie endpointy wymagają uwierzytelnienia. Operacje zapisu wymagają uprawnień `manage_options`.

| Metoda | Ścieżka | Opis |
|--------|---------|------|
| `GET` | `/tenants` | Lista wszystkich tenantów (admin) |
| `POST` | `/tenants` | Provisionowanie nowego portalu (admin) |
| `GET` | `/tenants/{id}` | Szczegóły tenanta (admin / owner / vendor) |
| `PUT` | `/tenants/{id}` | Aktualizacja tenanta (admin) |
| `DELETE` | `/tenants/{id}` | Usunięcie tenanta (admin) |
| `POST` | `/tenants/{id}/activate` | Aktywacja portalu (admin) |
| `POST` | `/tenants/{id}/suspend` | Zawieszenie portalu (admin) |
| `POST` | `/tenants/{id}/plan` | Zmiana planu SaaS (admin) |
| `GET` | `/tenants/{id}/vendors` | Lista sprzedawców (admin / owner / vendor) |
| `POST` | `/tenants/{id}/vendors` | Dodanie sprzedawcy (admin / tenant_admin) |
| `DELETE` | `/tenants/{id}/vendors/{user_id}` | Usunięcie sprzedawcy (admin / tenant_admin) |

#### Przykład: provisionowanie portalu

```bash
curl -X POST https://example.com/wp-json/peartree/v1/tenants \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: <nonce>" \
  -d '{
    "name": "Portal ABC",
    "slug": "portal-abc",
    "domain": "abc.example.com",
    "owner_id": 5,
    "plan": "pro",
    "status": "active"
  }'
```

#### Przykład: zmiana planu

```bash
curl -X POST https://example.com/wp-json/peartree/v1/tenants/3/plan \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: <nonce>" \
  -d '{"plan": "business"}'
```

### Statusy tenanta

| Status | Opis |
|--------|------|
| `pending` | Utworzony, oczekuje na aktywację |
| `active` | Portal aktywny i dostępny |
| `suspended` | Zawieszony (dostęp do danych zachowany) |
| `archived` | Zarchiwizowany (soft delete) |

### Role sprzedawców (vendors)

| Rola | Opis |
|------|------|
| `tenant_admin` | Pełny dostęp do zarządzania tenanta (nadawany automatycznie właścicielowi) |
| `vendor` | Standardowy sprzedawca marketplace |
| `moderator` | Moderator treści portalu |

### Panel administracyjny

Po zalogowaniu do WP Admin: **PearTree → Tenants**

Dostępne zakładki:
- **All Tenants** — tabela wszystkich portali z przyciskami lifecycle
- **New Tenant** — formularz provisionowania portalu
- **Edit** — edycja wybranego tenanta (slug, domena, plan, status)
- **Vendors** — przypisywanie i usuwanie sprzedawców

### Hooki WordPress

```php
// Po provisionowaniu portalu
add_action('poradnik_tenant_provisioned', function(Tenant $tenant) { ... });

// Po zmianie statusu
add_action('poradnik_tenant_status_changed', function(int $tenantId, string $status) { ... });

// Po zmianie planu
add_action('poradnik_tenant_plan_changed', function(int $tenantId, string $plan) { ... });

// Po dodaniu/usunięciu sprzedawcy
add_action('poradnik_tenant_vendor_added',   function(int $tenantId, int $userId, string $role) { ... });
add_action('poradnik_tenant_vendor_removed', function(int $tenantId, int $userId) { ... });

// Po usunięciu portalu
add_action('poradnik_tenant_destroyed', function(int $tenantId) { ... });
```

### Deployment & Migracja

Przy pierwszym uruchomieniu po aktualizacji do `v2.0.0` Migrator automatycznie wykona `dbDelta` tworząc tabele `poradnik_tenants` i `poradnik_tenant_vendors`. Migracja jest idempotentna — kolejne uruchomienia nie powodują błędów.

Wersja schematu przechowywana jest w opcji `poradnik_platform_db_version` (`wp_options`).

