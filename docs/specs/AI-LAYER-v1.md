# PORADNIK.PRO — AI LAYER (TXT v1 PRODUCTION)

> Source: Issue #22  
> Status: Production-ready

---

## 1. CEL

AI ma:
- generować odpowiedzi (Q&A)
- zwiększać SEO (content)
- zwiększać konwersję (lead)
- skracać czas do odpowiedzi

---

## 2. GŁÓWNE MODUŁY AI

| Moduł           | Opis                                        |
|-----------------|---------------------------------------------|
| AI SUMMARY      | krótka odpowiedź na stronie pytania (top)   |
| AI ANSWER       | auto-generowanie odpowiedzi                 |
| AI SEARCH       | rozumienie intencji zapytań                 |
| AI CONTENT      | poradniki + rankingi (SEO machine)          |

---

## 3. AI SUMMARY (NAJWAŻNIEJSZE)

Lokalizacja: top strony `/pytanie/{slug}`

Zawartość:
- 3–5 zdań
- konkretna odpowiedź
- bez lania wody

Cel:
- featured snippet (Google)
- szybka wartość dla usera

---

## 4. AI ANSWER (AUTO ODPOWIEDZI)

Flow:
1. user zadaje pytanie
2. system generuje odpowiedź AI
3. zapis jako odpowiedź
4. oznaczenie: **AI**

Opcja:
- edycja przez admina
- upgrade do „verified answer"

---

## 5. AI SEARCH (INTENT)

Zamiast dosłownego dopasowania:

```
"mechanik kraków"
```

AI rozumie:
- lokalizacja → Kraków
- intencja → szukam mechanika (usługa)

Zwraca: listingi + pytania + poradniki

---

## 6. AI CONTENT (SEO MACHINE)

Automatyczne generowanie:
- poradników
- rankingów
- opisów

Przykład:  
„najlepszy mechanik kraków" →  
→ generuje: ranking + opis + FAQ

---

## 7. AI Q&A GENERATOR

Seeder:
- generuje 1000+ pytań
- generuje odpowiedzi
- publikuje jako content

Efekt: szybkie SEO scale

---

## 8. AI LEAD BOOST

Na stronie:

```
Nie znalazłeś odpowiedzi?
[Zapytaj specjalistę →]  ← prefill wiadomości AI
```

---

## 9. ARCHITEKTURA

Backend: **PearTree Core (AI module)**

Endpointy:
```
POST /api/ai/generate
POST /api/ai/search
```

---

## 10. FLOW (AI)

```
user wchodzi
      ↓
AI summary pokazuje odpowiedź
      ↓
user scroll
      ↓
CTA → lead
```

---

## 11. PROMPTY (BAZA)

**AI summary:**
```
Odpowiedz krótko i konkretnie na pytanie: {pytanie}
```

**AI answer:**
```
Udziel profesjonalnej odpowiedzi jak ekspert w dziedzinie {kategoria}.
Pytanie: {pytanie}
```

---

## 12. MONETYZACJA AI

- premium answers
- AI assistant dla firm
- boost widoczności

---

## 13. UX

- badge: **AI**
- szybki load
- bez „robotycznego" tonu

---

## 14. PERFORMANCE

- cache odpowiedzi
- pre-generate content
- fallback (jeśli AI niedostępne)

---

## 15. FINAL

```
Bez AI → portal
Z AI   → engine
```

---

## 16. STATUS

Production-ready  

✅ FLOW · ✅ SEARCH · ✅ Q&A · ✅ LISTINGS · ✅ AI
