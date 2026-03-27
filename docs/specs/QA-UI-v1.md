# PORADNIK.PRO — Q&A UI (TXT v1 PRODUCTION)

> Source: Issue #17  
> Status: Gotowe do wdrożenia

---

## 1. CEL

Ekran Q&A ma:
- odpowiadać na pytanie (SEO)
- zatrzymać użytkownika (UX)
- generować lead (biznes)

---

## 2. URL

```
/pytanie/{slug}
```

---

## 3. STRUKTURA STRONY

1. HERO (pytanie)
2. AI SUMMARY (top answer)
3. LISTA ODPOWIEDZI
4. CTA (lead + odpowiedź)
5. POWIĄZANE TREŚCI

---

## 4. HERO (PYTANIE)

Elementy:
- H1 (pełne pytanie)
- meta: liczba odpowiedzi / data / kategoria
- breadcrumb

UX: duży font + max width + spacing premium

---

## 5. AI SUMMARY (KLUCZ)

```
┌─────────────────────────────────────────┐
│  [AI]  Najkrótsza odpowiedź             │
│                                         │
│  3–5 zdań konkretnej odpowiedzi         │
│  bez lania wody                         │
└─────────────────────────────────────────┘
```

- badge: AI
- highlight gradient border
- disclaimer (opcjonalny)

Cel: szybka odpowiedź + featured snippet (SEO)

---

## 6. LISTA ODPOWIEDZI

Każda odpowiedź zawiera:
- avatar
- nazwa
- badge: ekspert (gold) / verified (purple) / user (gray)
- treść
- data
- vote: 👍 / 👎

---

## 7. NAJLEPSZA ODPOWIEDŹ

Wyróżnienie:
- border glow
- label: "Najlepsza odpowiedź"

Zasada: top voted lub manual admin

---

## 8. CTA (KLUCZ BIZNESOWY)

Pod odpowiedziami:
1. "Zadaj pytanie"
2. "Zapytaj specjalistę"

Form: imię / email / treść

CTA style: gradient button + sticky mobile

---

## 9. LEAD BOX (PREMIUM)

```
Skontaktuj się ze specjalistą
[ Firma A ]  [ Firma B ]  [ Firma C ]
              [Zapytaj]
```

Sort: PREMIUM+ → PREMIUM

---

## 10. POWIĄZANE TREŚCI

Na dole:
- podobne pytania
- powiązane poradniki
- rankingi

Cel: SEO + time on site

---

## 11. SIDEBAR (DESKTOP)

- reklama
- top specjaliści
- popularne pytania

---

## 12. MOBILE UX

- sticky CTA: Zadaj pytanie
- stacked layout
- duże przyciski

---

## 13. UI ELEMENTY

| Komponent     | Spec                              |
|---------------|-----------------------------------|
| ANSWER CARD   | glass bg, padding 16–20px, r14px  |
| BADGE ekspert | gold                              |
| BADGE verified| purple                            |
| BADGE user    | gray                              |
| BUTTON        | gradient purple → pink            |

---

## 14. MONETYZACJA

| Miejsce       | Format              |
|---------------|---------------------|
| CTA           | lead                |
| listingi      | premium             |
| sidebar       | ads                 |
| ranking linki | afiliacja           |

---

## 15. SEO

- schema: QAPage
- H1 = pytanie
- internal links
- AI summary = snippet

---

## 16. PERFORMANCE

- lazy load
- minimal JS
- server render (WP)

---

## 17. STATUS

Gotowe do wdrożenia.

**NEXT:** SEARCH UI → LISTINGS UI → AI ANSWERS
