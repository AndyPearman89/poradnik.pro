# PORADNIK.PRO THEME — UI PRO SPEC v2

> Source: Issue #16  
> Status: Ready for implementation

---

## 1. CEL UI PRO

Zwiększenie:
- konwersji (lead)
- czasu na stronie
- liczby zapytań (Q&A)
- CTR w rankingach i listingach

---

## 2. HERO (UPGRADE)

```
H1: Znajdź odpowiedź. Porównaj. Wybierz najlepszego specjalistę.
[input duży, centralny]     [Szukaj]
                [Zadaj pytanie]
trending: #kredyt #mechanik #rozwód
```

Funkcje:
- autocomplete: pytania / poradniki / specjaliści
- live suggestions (top 5)
- trending tags pod inputem

UX:
- focus = glow (purple)
- input = rounded + glass
- mobile = full width + sticky

---

## 3. SEARCH (CORE ENGINE UI)

Dropdown sekcje:
1. **Pytania** — tytuł + badge + krótki preview
2. **Specjaliści** — tytuł + badge + krótki preview
3. **Poradniki** — tytuł + badge + krótki preview

Interakcje:
- hover highlight
- enter = redirect
- klik = redirect

---

## 4. Q&A LIST (UPGRADE)

Card pytania:
- tytuł (H3)
- liczba odpowiedzi
- najlepsza odpowiedź (2 linie)
- badge: ekspert / verified

CTA: Odpowiedz / Zobacz odpowiedzi

UX: hover lift + gradient border subtle

---

## 5. SINGLE QUESTION (KLUCZOWY WIDOK)

Sekcje:
1. pytanie (H1)
2. AI summary (top box)
3. lista odpowiedzi
4. CTA: dodaj odpowiedź / zapytaj specjalistę

Odpowiedź karta:
- avatar + nazwa + badge (ekspert/user) + treść + vote (up/down)

Najlepsza odpowiedź: border + glow wyróżnienie

---

## 6. LISTINGS (SPECJALIŚCI)

Card:
- nazwa + lokalizacja + ocena
- badge: PREMIUM+ / PREMIUM / VERIFIED
- CTA: Zapytaj / Zobacz profil

Sort: PREMIUM+ → PREMIUM → FREE

UX: sticky CTA mobile + highlight premium

---

## 7. RANKINGS (AFILIACJA)

Tabela TOP 10:
- kolumny: pozycja / nazwa / ocena / CTA
- CTA: Sprawdź / Przejdź
- UX: highlight top 3 + sticky header

---

## 8. PORADNIKI

Layout:
- H1 + treść
- box CTA: Znajdź specjalistę
- Na dole: powiązane pytania + powiązane rankingi

---

## 9. MOBILE UX

Bottom nav: Szukaj / Pytania / Rankingi / Konto

Sticky button: **Zadaj pytanie**

---

## 10. MICRO UX

- skeleton loading
- hover animations
- smooth transitions
- loading states

---

## 11. UI ELEMENTY

| Komponent | Spec                                        |
|-----------|---------------------------------------------|
| BUTTON    | gradient purple→pink, radius 12–14px        |
| CARD      | glass background, blur, border subtle       |
| INPUT     | rounded, glow focus                         |

---

## 12. MONETYZACJA UI

| Miejsce    | Format                    |
|------------|---------------------------|
| HERO       | sponsorowane sugestie     |
| LISTINGS   | pinned premium            |
| RANKINGS   | afiliacja                 |
| Q&A        | kontakt                   |
| SIDEBAR    | ads                       |

---

## 13. PERFORMANCE

- minimal JS
- lazy load
- brak ciężkich bibliotek

---

## 14. STATUS

Gotowe do implementacji.
