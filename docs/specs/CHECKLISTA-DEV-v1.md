# PORADNIK.PRO — CHECKLISTA DEV (TXT v1 EXECUTION)

> Source: Issue #23  
> Status: Execution checklist — MVP launch

---

## 0. START

- [ ] domena poradnik.pro ustawiona
- [ ] serwer (VPS) działa
- [ ] SSL aktywny

---

## 1. WORDPRESS

- [ ] instalacja WordPress
- [ ] ustawienia:
  - permalinki → `/%postname%/`
  - język PL
  - timezone
- [ ] usunięcie zbędnych pluginów
- [ ] instalacja:
  - GeneratePress (parent)
  - Poradnik Theme (child)

---

## 2. PEARTREE CORE

- [ ] instalacja pluginu PearTree Core
- [ ] aktywacja modułów:
  - Listings
  - Leads
  - Q&A
  - Ranking
- [ ] sprawdzenie CPT:
  - `question`
  - `listing`
  - `article`
  - `ranking`
  - `lead`

---

## 3. THEME (FRONTEND)

- [ ] wrzucenie plików theme
- [ ] aktywacja child theme
- [ ] podpięcie: CSS / JS
- [ ] header: logo SVG + menu
- [ ] mobile nav działa

---

## 4. HOMEPAGE

- [ ] ustawienie `front-page.php`
- [ ] sekcje: hero / search / Q&A / listings / rankings / articles

---

## 5. SEARCH

- [ ] input działa
- [ ] JS debounce (300ms)
- [ ] endpoint API działa (`/wp-json/peartree/search`)
- [ ] autocomplete: pytania / listingi / poradniki
- [ ] klik → redirect działa

---

## 6. Q&A

- [ ] `archive-question` działa
- [ ] `single-question` działa
- [ ] elementy: H1 pytanie / AI summary / lista odpowiedzi / CTA

---

## 7. LISTINGS

- [ ] `archive-listing` działa
- [ ] `single-listing` działa
- [ ] sortowanie: PREMIUM+ → PREMIUM → FREE
- [ ] karta: nazwa / ocena / badge

---

## 8. LEAD ENGINE

- [ ] formularz działa
- [ ] walidacja
- [ ] zapis do DB
- [ ] email: do firmy + do admina
- [ ] test wysyłki OK

---

## 9. AI

- [ ] API podpięte (OpenAI)
- [ ] AI summary generuje
- [ ] zapis w DB (cache)
- [ ] AI answer działa

---

## 10. CONTENT (SEED)

- [ ] min 50 pytań
- [ ] min 20 listingów
- [ ] min 10 poradników
- [ ] sprawdzenie: slug / SEO title

---

## 11. SEO

- [ ] meta title
- [ ] meta description
- [ ] schema: QAPage / Article
- [ ] `sitemap.xml`
- [ ] Google Search Console podpięte

---

## 12. MONETYZACJA

- [ ] pakiety: FREE / PREMIUM / PREMIUM+
- [ ] badge działa
- [ ] sortowanie działa

---

## 13. PERFORMANCE

- [ ] lazy load
- [ ] cache (plugin / serwer)
- [ ] minifikacja CSS/JS

---

## 14. TESTY

- [ ] search działa
- [ ] Q&A działa
- [ ] listing działa
- [ ] formularz działa
- [ ] email działa
- [ ] mobile OK

---

## 15. START

- [ ] sitemap wysłana
- [ ] pierwsze strony zaindeksowane
- [ ] test ruchu

---

## 16. PO STARCIE

- [ ] zbieranie danych
- [ ] poprawa UX
- [ ] dodawanie contentu

---

## 17. FINAL

Jeśli wszystko odhaczone:

→ masz działający system  
→ możesz generować leady

---

## 🚀 NEXT (PO MVP)

- [ ] AI search (lepszy intent)
- [ ] dashboard usera
- [ ] panel specjalisty
- [ ] automatyczny content
