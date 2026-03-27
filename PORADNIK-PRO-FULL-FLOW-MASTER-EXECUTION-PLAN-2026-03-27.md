# PORADNIK.PRO - FULL FLOW MASTER EXECUTION PLAN

Data: 2026-03-27
Status: EXECUTION PLAN v1 (expanded)
Powiazane issue: #21 (FULL FLOW)

## 1. Cel biznesowy i operacyjny

Docelowy przeplyw:

ruch -> odpowiedz -> zaufanie -> kontakt -> lead -> przychod

Cel operacyjny:
- domknac stabilny i mierzalny flow od wejscia SEO/Search do konwersji lead/affiliate,
- utrzymac gotowosc produkcyjna (go-live safety),
- uruchomic stale raportowanie KPI i szybkie petle optymalizacji.

## 2. North Star i metryki sterujace

North Star (weekly):
- liczba kwalifikowanych konwersji tygodniowo (lead + affiliate confirmed + ad qualified click).

KPI glowny lejek:
- sessions (SEO, direct, paid, social),
- CTR do sekcji konwersyjnych (CTA CTR),
- listing view rate,
- lead submit rate,
- affiliate click rate,
- revenue per 1000 sessions,
- conversion latency (czas od first touch do konwersji).

KPI jakosci i ryzyka:
- REST error rate,
- 5xx rate,
- median TTFB,
- stale route availability dla AI/SEO,
- rollback readiness score.

## 3. Mapa FULL FLOW (delivery view)

Wejscie:
- SEO landing,
- Search,
- brand/direct,
- ads/social.

Srodek:
- Q&A,
- poradniki,
- rankingi,
- porownania,
- recenzje.

Konwersja:
- CTA (contextual + sticky + sidebar),
- listing specjalisty,
- formularz lead,
- affiliate outbound,
- ad premium path.

Monetyzacja:
- pay-per-lead,
- afiliacja,
- ads,
- premium/sponsored.

## 4. Scenariusze docelowe (from issue #21) i implementacja

S1 SEO -> Q&A -> Lead:
- wejscie: /pytanie/*
- must-have UX: AI summary, odpowiedzi, mocne CTA,
- rezultat: wejscie do listingu lub formularza.

S2 Search -> Listing:
- wejscie: zapytanie intencyjne (np. specjalista + miasto),
- must-have UX: autocomplete + filtry + social proof,
- rezultat: klik do profilu i lead.

S3 Ranking -> Affiliate:
- wejscie: intencja zakupowa,
- must-have UX: tabela porownawcza + wyroznione oferty,
- rezultat: outbound affiliate + tracking.

S4 Poradnik -> Q&A -> Lead:
- wejscie: poradnik how-to/problem,
- must-have UX: sekcja powiazanych pytan + CTA,
- rezultat: przejscie do Q&A i kontakt.

## 5. Program realizacji (12 tygodni)

### Faza A (Tydzien 1-2): Hardening i brakujace P1
Cel:
- zero blockerow go-live, komplet testow smoke/E2E, stabilnosc API.

Zakres:
- domkniecie smoke AI Assistant + AI Image,
- smoke Content Engine builder,
- finalny runbook + rollback rehearsal,
- gate produkcyjny z warunkiem aktywnych AI routes.

Exit criteria:
- PRODUCTION_GATE=PASS,
- GATE_AI_SKIPPED_ROUTES=0,
- brak krytycznych regresji 24h.

### Faza B (Tydzien 3-4): Conversion Surface
Cel:
- podniesc CTR i listing view rate.

Zakres:
- standaryzacja CTA komponentow (Q&A, poradnik, ranking, recenzja),
- sticky CTA i sidebar CTA,
- porzadek w internal linking (poradnik -> ranking -> recenzja -> pytanie -> specjalista),
- optymalizacja search/autocomplete flow.

Exit criteria:
- +20% CTA CTR vs baseline,
- +15% listing view rate vs baseline.

### Faza C (Tydzien 5-6): Lead Quality i Routing
Cel:
- zwiekszyc jakosc i domkniecie leadow.

Zakres:
- walidacja formularzy i quality scoring leadow,
- routing leadow per branza/miasto,
- deduplikacja i podstawowe anty-spam,
- SLA dashboard dla lead response.

Exit criteria:
- +15% lead submit quality score,
- -25% duplicate/spam leads.

### Faza D (Tydzien 7-8): Revenue Optimization
Cel:
- zwiekszyc przychod na jednostke ruchu.

Zakres:
- tuning ranking cards i affiliate CTA,
- placement i frequency cap dla ad slots,
- review blocks pod intent zakupowy,
- sponsored workflow KPI.

Exit criteria:
- +12% revenue per 1000 sessions,
- stabilny ad/affiliate tracking bez utraty eventow.

### Faza E (Tydzien 9-10): Scale Engine
Cel:
- uruchomic przewidywalna skale tresci.

Zakres:
- batch generation (poradnik, recenzja, porownanie, pytanie, odpowiedz),
- quality guardrails (banned claims, quality checks),
- publikacja etapowa z monitoringiem indeksacji,
- harmonogram klastrow contentowych.

Exit criteria:
- stabilna publikacja batch bez spadku quality threshold,
- wzrost ruchu organicznego dla nowej puli landingow.

### Faza F (Tydzien 11-12): Localization + Stabilization
Cel:
- przygotowac skalowanie jezykowe i operacyjna stabilizacje.

Zakres:
- groundwork hreflang i URL mapping,
- schema localization,
- SLA monitoringu i on-call,
- finalny przeglad ryzyk i playbookow incidentowych.

Exit criteria:
- gotowy minimalny framework multilingual,
- zero otwartych ryzyk krytycznych.

## 6. Epiki i backlog wykonawczy (na maksa)

### EPIC-01: Acquisition Entry Quality
Cel:
- zwiekszyc dopasowanie wejscia do intencji usera.

Tasks:
- A1: mapowanie top landingow SEO -> intent matrix,
- A2: standaryzacja hero/search blocks,
- A3: mapowanie query -> page type,
- A4: fallback routing dla weak intent.

Acceptance:
- kazdy top landing ma przypisany intent,
- brak orphan query path dla top 100 zapytan.

### EPIC-02: Trust Layer (Q&A + Content)
Cel:
- zwiekszyc zaufanie i glebokosc sesji.

Tasks:
- T1: AI summary z jasnym disclosure,
- T2: answer quality panels,
- T3: powiazane pytania i poradniki,
- T4: social proof snippets.

Acceptance:
- Q&A pages maja komplet trust blocks,
- wzrost avg engaged time i scroll depth.

### EPIC-03: CTA System
Cel:
- zwiekszyc przejscia do warstwy konwersji.

Tasks:
- C1: komponent CTA library (primary/secondary/contextual),
- C2: sticky CTA for mobile/desktop,
- C3: CTA logic by page type,
- C4: A/B experiment flags.

Acceptance:
- CTA coverage 100% dla kluczowych typow stron,
- raport CTR per placement dziennie.

### EPIC-04: Listing + Lead Engine
Cel:
- podniesc lead submit i lead quality.

Tasks:
- L1: listing cards z proof i priorytetem intencji,
- L2: formularz lead z progressive fields,
- L3: anti-spam i dedupe,
- L4: lead routing matrix (branza/miasto),
- L5: lead delivery observability.

Acceptance:
- formularz ma walidacje i traceability,
- lead status lifecycle mierzalny end-to-end.

### EPIC-05: Affiliate + Ranking Revenue
Cel:
- wzrost affiliate i ranking conversion.

Tasks:
- R1: ranking table UX hardening,
- R2: top offers pinning,
- R3: outbound attribution,
- R4: CTA copy testing,
- R5: quality kontrola recenzji pod intent zakupowy.

Acceptance:
- eventy affiliate i ranking sa kompletne,
- wzrost affiliate click-through i downstream revenue.

### EPIC-06: Ads + Sponsored Monetization
Cel:
- stabilna monetyzacja paid placements.

Tasks:
- M1: slot inventory governance,
- M2: frequency caps i placement rules,
- M3: sponsored workflow KPI,
- M4: billing/payments checkpoint.

Acceptance:
- brak utraty eventow ad impression/click,
- sponsored flow bez blockerow publikacji.

### EPIC-07: AI + Programmatic Scale
Cel:
- skala publikacji przy zachowaniu quality.

Tasks:
- P1: prompt templates versioning,
- P2: quality guardrails,
- P3: batch scheduler,
- P4: publication gates,
- P5: rollback content batch.

Acceptance:
- kazdy batch ma raport quality,
- istnieje procedura rollback dla batchy.

### EPIC-08: Measurement + Ops Excellence
Cel:
- szybka, codzienna sterowalnosc decyzji.

Tasks:
- O1: KPI dashboards dzienny/tygodniowy,
- O2: anomaly alerts,
- O3: release gate automations,
- O4: runbook i incident playbook,
- O5: post-release reviews.

Acceptance:
- dashboard aktualizuje sie codziennie,
- alerty dzialaja na uzgodnionych progach.

## 7. RACI (operacyjny)

Rola i odpowiedzialnosc:
- Product Lead: priorytety, KPI targety, decyzje scope,
- Engineering Lead: architektura, code quality, release quality,
- QA Lead: smoke/E2E, regression sign-off,
- DevOps: deploy gates, monitoring, rollback readiness,
- Content Ops: batch quality, editorial gates,
- Revenue Ops: affiliate/ads/sponsored optimization.

## 8. Ryzyka krytyczne i mitigacje

R1: route drift miedzy namespace API
- Mitigacja: dual-namespace checks w gate + contract tests.

R2: spadek quality przy skali contentu
- Mitigacja: quality threshold + blocked publish + sampling QA.

R3: brak wiarygodnego pomiaru revenue
- Mitigacja: event reconciliation i codzienny report.

R4: regresje po deploy
- Mitigacja: canary smoke + rollback trigger matrix.

R5: przeciazenie infra przy batchach
- Mitigacja: queue, rate limits, batch windows.

## 9. Definition of Done (systemowe)

FULL FLOW uznajemy za dowieziony, gdy:
- wszystkie 4 scenariusze #21 przechodza E2E,
- KPI glowny rosnie przez min. 2 kolejne tygodnie,
- brak otwartych incydentow krytycznych,
- runbook i rollback sa praktycznie przetestowane,
- gate produkcyjny przechodzi stale po kazdym releasie.

## 10. Plan raportowania

Raport dzienny:
- traffic, CTR, listing views, lead submits,
- affiliate/ad events completeness,
- bledy API i 5xx,
- top 5 anomalii.

Raport tygodniowy:
- trend KPI,
- analiza scenariuszy S1-S4,
- decyzje: kontynuowac / zatrzymac / pivot,
- backlog na kolejny tydzien.

## 11. Najblizsze 14 dni (konkret)

Day 1-2:
- final smoke AI + builder,
- gate produkcyjny z require AI routes,
- aktualizacja runbook.

Day 3-5:
- CTA system rollout (Q&A, poradnik, ranking),
- listing UX patch,
- dashboard CTR dzienny.

Day 6-8:
- lead form quality + routing,
- anti-spam + dedupe,
- E2E lead lifecycle.

Day 9-11:
- ranking/affiliate optimization,
- ad slot governance,
- sponsored KPI checks.

Day 12-14:
- weekly review,
- pruning backlog,
- freeze + release decision.

## 12. Powiazanie z aktualnymi dokumentami repo

Ten plan rozszerza i operacjonalizuje:
- IMPLEMENTATION CHECKLIST EXECUTION (P1/P2/P3),
- SPRINT 12 PLAN,
- GO-LIVE CHECKLIST,
- GOLIVE RISK MATRIX,
- PERFORMANCE BASELINE,
- MEASUREMENT BASELINE.

W praktyce:
- checklisty pozostaja zrodlem statusu,
- ten dokument jest nadrzednym planem wykonawczym FULL FLOW.

