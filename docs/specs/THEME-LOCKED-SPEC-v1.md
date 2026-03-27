# PORADNIK.PRO THEME — LOCKED SPEC v1

> Source: Issue #15  
> Status: Locked – basis for all UI implementation

---

## 1. ZAŁOŻENIA PRODUKTOWE

Typ platformy:
- hybrid: content SEO + Q&A + marketplace specjalistów + afiliacja

Cel UI:
- maksymalna konwersja → zapytanie (lead)
- szybkie indeksowanie SEO
- UX jak aplikacja (nie blog)

---

## 2. DESIGN SYSTEM

### Kolory

| Token              | Wartość     |
|--------------------|-------------|
| `--bg`             | `#0b0f1a`   |
| `--bg-section`     | `#111827`   |
| `--primary`        | `#7c3aed`   |
| `--secondary`      | `#ec4899`   |
| `--text`           | `#f3f4f6`   |
| `--text-muted`     | `#9ca3af`   |

**Gradient brand:**  
`linear-gradient(135deg, #7c3aed, #ec4899)`

---

### Typografia

| Element | Rozmiar      |
|---------|-------------|
| `H1`    | 42–56px     |
| `H2`    | 28–36px     |
| `body`  | 16–18px     |

Font: **Inter** / **Poppins**

---

### UI Style

- glassmorphism (blur + transparent)
- rounded: 12–16px
- glow: subtle purple shadow
- hover: lift + shadow

---

## 3. STRUKTURA HOMEPAGE

### 3.1 HEADER

Elementy:
- logo (lewo)
- menu: Pytania / Rankingi / Specjaliści / Poradniki
- CTA: Dodaj pytanie / Zaloguj

Sticky + blur background

### 3.2 HERO

- H1: *Znajdź odpowiedź. Porównaj. Wybierz najlepszego specjalistę.*
- sub: tysiące poradników + eksperci
- input search (centralny)
- autocomplete: pytania / poradniki / specjaliści
- trending: kredyt hipoteczny / mechanik kraków / pozew rozwodowy
- CTA: Szukaj / Zadaj pytanie

### 3.3 BLOK: OSTATNIE PYTANIA (Q&A ENGINE)

- tytuł pytania
- liczba odpowiedzi
- badge specjalisty
- najlepsza odpowiedź (preview)
- CTA: zobacz odpowiedzi / odpowiedz jako ekspert

### 3.4 BLOK: SPECJALIŚCI

- karta: nazwa firmy / lokalizacja / ocena / badge (PREMIUM / VERIFIED)
- CTA: zapytaj / zobacz profil
- Sortowanie: PREMIUM+ → PREMIUM → FREE

### 3.5 BLOK: RANKINGI

- TOP 10 produktów/usług
- tabela + oceny + CTA (afiliacja)

### 3.6 BLOK: PORADNIKI

- artykuły evergreen (long-tail SEO)

### 3.7 FOOTER

- kategorie / miasta / dla firm / kontakt / SEO linki

---

## 4. STRONY SYSTEMOWE

| URL                    | Elementy kluczowe                                     |
|------------------------|-------------------------------------------------------|
| `/pytanie/{slug}`      | H1 pytanie, AI summary, odpowiedzi, CTA               |
| `/ranking/{slug}`      | tabela TOP, oceny, CTA afiliacyjne                    |
| `/poradnik/{slug}`     | treść, box CTA (znajdź specjalistę), powiązane pytania|
| `/specjalista/{slug}`  | profil, opinie, formularz kontaktowy (lead)           |

---

## 5. KOMPONENTY UI

### BUTTON
- gradient purple/pink
- radius: 12px
- shadow: glow

### CARD
- background: `rgba(255,255,255,0.04)`
- blur
- border subtle

### INPUT SEARCH
- duży, rounded
- z ikoną
- live results dropdown

---

## 6. MOBILE UX

Bottom nav:
- Szukaj / Pytania / Rankingi / Konto

Sticky CTA: **Zadaj pytanie**

---

## 7. MONETYZACJA (UI)

| Miejsce   | Format                      |
|-----------|-----------------------------|
| HERO      | sponsorowane wyniki         |
| LISTY     | wyróżnione firmy            |
| RANKING   | afiliacja                   |
| Q&A       | kontakt do specjalisty      |
| SIDEBAR   | adsense                     |

---

## 8. INTEGRACJA Z PEARTREE CORE

Moduły:
- Listings (specjaliści)
- Leads (formularze)
- Q&A Engine
- Ranking Engine
- Affiliate Engine

---

## 9. ROADMAP FRONTENDU

| Wersja | Zakres                                          |
|--------|-------------------------------------------------|
| V1     | homepage, pytania, poradniki, specjaliści       |
| V2     | rankingi, afiliacja                             |
| V3     | dashboard usera, panel specjalisty              |
| V4     | AI answers, realtime chat                       |
