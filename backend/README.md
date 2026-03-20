# platform-core (MU Plugin)

## Agenty Copilot (workspace)

Przewodnik agentów multi-repo: `../.github/agents/README.md`.

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
