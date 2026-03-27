# PORADNIK.PRO — LISTINGS UI + LEAD ENGINE (TXT v1 PRODUCTION)

> Source: Issue #20  
> Status: Production-ready

---

## 1. CEL

Listings mają:
- konwertować użytkownika → lead
- sprzedawać pakiety PREMIUM
- być widoczne w search + SEO

---

## 2. URL

```
/specjalista/{slug}
/specjalisci/{kategoria}/{miasto}
```

---

## 3. LISTA (ARCHIVE)

Każda karta zawiera:
- nazwa firmy
- lokalizacja
- ocena (⭐)
- liczba opinii
- badge: PREMIUM+ / PREMIUM / VERIFIED

CTA: Zapytaj / Zobacz profil

---

## 4. SORTOWANIE (KLUCZ MONETYZACJI)

Kolejność:
1. PREMIUM+
2. PREMIUM
3. VERIFIED
4. FREE

Dodatkowo:
- boost za dopasowanie do zapytania
- boost za lokalizację

---

## 5. CARD (UI)

```
┌─────────────────────────────────────────┐
│ [LOGO]  Nazwa Firmy          [PREMIUM+] │
│         Kraków · ⭐ 4.8 (127 opinii)    │
│         Krótki opis firmy…             │
│                                         │
│  [Zapytaj]         [Zobacz profil]      │
└─────────────────────────────────────────┘
```

UX: hover lift + gradient border (premium)

---

## 6. FILTRY

- lokalizacja
- kategoria
- ocena
- cena (opcjonalnie)

---

## 7. SINGLE LISTING (STRONA PROFILU)

Sekcje:
1. HERO
2. OPIS
3. OPINIE
4. FORMULARZ (LEAD)
5. PODOBNE

---

## 8. HERO (PROFIL)

```
[LOGO]  Nazwa Firmy
        Lokalizacja · ⭐ ocena  [PREMIUM]

[Zapytaj]    [Zadzwoń]  ← tylko premium
```

---

## 9. FORMULARZ LEAD (KLUCZ)

```
Imię:        [________________]
Email:       [________________]
Telefon:     [________________] (opcjonalnie)
Wiadomość:   [________________]
             [________________]

             [Wyślij zapytanie]
```

UX: prosty + szybki + mobile-first

---

## 10. LEAD FLOW

1. user wysyła formularz
2. zapis do DB (CPT lead)
3. email do firmy
4. email do admina
5. status: new

---

## 11. FREE vs PREMIUM

| Feature         | FREE | PREMIUM | PREMIUM+ |
|-----------------|------|---------|----------|
| Telefon widoczny| ✗    | ✓       | ✓        |
| Wyższa pozycja  | ✗    | ✓       | TOP      |
| Badge           | ✗    | ✓       | ✓        |
| Highlight       | ✗    | ✗       | ✓        |

---

## 12. LEAD TRACKING

- liczba leadów
- konwersja
- status: new / contacted / closed

---

## 13. MOBILE UX

- sticky CTA: Zapytaj
- click-to-call
- prosty formularz

---

## 14. MONETYZACJA

- abonament PREMIUM
- lead pay-per-lead (opcjonalnie)
- wyróżnienia

---

## 15. SEO

- strony miasto + branża
- schema: LocalBusiness
- opinie

---

## 16. PERFORMANCE

- lazy load
- minimal JS
- cache listingów

---

## 17. STATUS

Production-ready

**CO TERAZ:** SEARCH ✅ · Q&A ✅ · LISTINGS ✅ → **FULL FLOW**
