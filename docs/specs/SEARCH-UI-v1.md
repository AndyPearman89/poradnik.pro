# PORADNIK.PRO — SEARCH UI + ENGINE (TXT v1 PRODUCTION)

> Source: Issue #19  
> Status: Production-ready

---

## 1. CEL

Search ma:
- prowadzić użytkownika do odpowiedzi
- generować wejścia SEO
- kierować do: pytania / specjalisty / poradnika

---

## 2. TYPY WYNIKÓW

Search obsługuje:
- pytania (Q&A)
- specjaliści (Listings)
- poradniki (Articles)
- rankingi (opcjonalnie)

---

## 3. HERO SEARCH (HOMEPAGE)

```
┌──────────────────────────────────────────┐
│  🔍  Wpisz problem…                 [Szukaj] │
└──────────────────────────────────────────┘
trending: kredyt hipoteczny  mechanik kraków  rozwód koszty
```

---

## 4. AUTOCOMPLETE (KLUCZ)

Po wpisaniu — Dropdown:

```
SEKCJA 1: PYTANIA
  ● Tytuł pytania                    [42 odpowiedzi]
  ● Tytuł pytania 2                  [17 odpowiedzi]

SEKCJA 2: SPECJALIŚCI
  ● Nazwa firmy          Kraków
  ● Nazwa firmy 2        Warszawa

SEKCJA 3: PORADNIKI
  ● Tytuł poradnika — snippet…
```

Limity: max 5 wyników na sekcję + szybki load (cache)

---

## 5. INTERAKCJE

| Akcja   | Wynik              |
|---------|--------------------|
| klik    | redirect           |
| enter   | search page        |
| hover   | highlight          |

---

## 6. SEARCH PAGE (`/search?q=`)

Widok:
- tabs: Wszystko / Pytania / Specjaliści / Poradniki
- card layout + paginacja

---

## 7. LOGIKA PRIORYTETU

Ranking wyników:
1. exact match
2. title match
3. popularity (views)
4. premium (listing boost)

---

## 8. LISTING BOOST

Jeśli zapytanie = lokalne (np. „mechanik kraków"):
→ 2–3 specjalistów PREMIUM na górze

---

## 9. EMPTY STATE

```
Nie znaleziono wyników dla "{query}"
[Zadaj pytanie]
```

---

## 10. MOBILE UX

- full width input
- dropdown fullscreen
- sticky search

---

## 11. UI ELEMENTY

| Komponent | Spec                            |
|-----------|---------------------------------|
| INPUT     | rounded, glow focus             |
| DROPDOWN  | dark glass, blur, sekcje        |

---

## 12. API / AJAX (WP)

Endpoint:
```
GET /wp-json/peartree/search?q={query}
```

Response:
```json
{
  "questions": [...],
  "listings":  [...],
  "articles":  [...]
}
```

---

## 13. JS FLOW

1. user wpisuje
2. debounce (300ms)
3. fetch API
4. render dropdown

---

## 14. PERFORMANCE

- debounce
- cache (local + server)
- limit wyników

---

## 15. MONETYZACJA

- sponsorowane wyniki (top)
- listing premium boost
- afiliacja (rankingi)

---

## 16. SEO

- search page index (selected)
- internal linking
- generowanie long-tail

---

## 17. STATUS

Production-ready

**NEXT:** LISTINGS UI → AI SEARCH (GPT layer) → FULL FLOW
