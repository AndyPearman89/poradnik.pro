# platform-core (MU Plugin)

## Agenty Copilot (workspace)

Przewodnik agentów multi-repo: `../.github/agents/README.md`.

## Zakres referencyjny

Backend Poradnik.pro jest utrzymywany zgodnie z zakresem produktu opisanym w
`../PORADNIK_PRO_MASTER_PROMPT_CONTENT_ENGINE_3_0.md`.

Terminologia 3.0:
- `guide` -> `poradnik`
- `review` -> `recenzja`
- `comparison` -> `porownanie`
- dodatkowe encje platformy: `pytanie`, `odpowiedz`, `specjalista`

## Status zmian (2026-03-13)

### Platform alignment / Content Engine 3.0
- Backend stanowi warstwę runtime dla poradnikow, rankingow, recenzji, porownan i Q&A.
- Backend udostepnia wlasne moduly do lead generation, afiliacji, premium visibility i workflow specjalistow.
- Warstwa REST oraz feature flags pozostaja niezalezne od zewnetrznego rdzenia platformowego.

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
- Platforma korzysta z namespace `poradnik/v1`.

### Testy i środowisko
- Zweryfikowano składnię PHP kontrolerów po zmianach.
- Przeprowadzono testy HTTP/REST (autoryzacja i routing).
- Konto testowe `REKLAMAPRO` istnieje w środowisku lokalnym do dalszych testów integracyjnych.
