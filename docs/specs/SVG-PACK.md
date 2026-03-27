# PORADNIK.PRO — SVG PACK (MIX BEST)

> Source: Issue #18  
> Status: Wdrożenie — assets/branding/

---

## 1. ZESTAW FINALNY (FILES)

```
theme/assets/branding/
├── logo-main.svg        # default, header, landing, SEO
├── logo-dark.svg        # dark bg — biały tekst
├── logo-light.svg       # light bg — ciemny tekst
├── logo-icon.svg        # sam znak P+? (favicon, mobile nav, loading)
├── logo-app.svg         # rounded square, PWA/iOS/Android
├── logo-trust.svg       # badge/shield, PREMIUM certyfikat
├── favicon.ico
├── apple-touch-icon.png
├── site.webmanifest
└── og-image.png         # 1200×630
```

---

## 2. DEFINICJA WERSJI

### LOGO MAIN (default)

Styl:
- Minimal PRO + lekki glow
- napis: **Poradnik.pro**
- gradient: purple → pink

Użycie: header / landing / SEO

---

### LOGO ICON

Styl:
- sam znak (P + ?)
- bez napisu

Użycie: favicon / mobile nav / loading

---

### APP ICON

Styl:
- rounded square
- gradient tło
- grubszy znak

Użycie: PWA / iOS / Android

---

### TRUST (monetyzacja)

Styl:
- badge / shield
- checkmark
- bardziej „solidny"

Użycie: profile firm / PREMIUM / certyfikat

---

### DARK / LIGHT

- dark → biały tekst
- light → ciemny tekst

---

## 3. CSS (BRAND EFFECT)

```css
.logo svg {
  filter: drop-shadow(0 0 10px rgba(124, 58, 237, 0.4));
}

.logo:hover svg {
  transform: scale(1.03);
  transition: transform 0.2s ease;
}
```

---

## 4. WEB MANIFEST

Plik: `theme/site.webmanifest`

```json
{
  "name": "Poradnik.pro",
  "short_name": "Poradnik",
  "icons": [
    {
      "src": "/assets/branding/logo-app.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/assets/branding/logo-app.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ],
  "theme_color": "#0b0f1a",
  "background_color": "#0b0f1a",
  "display": "standalone",
  "start_url": "/"
}
```

---

## 5. OG IMAGE

- rozmiar: 1200×630
- styl: bold gradient
- duży znak + napis

---

## 6. WDROŻENIE (CHECKLIST)

- [ ] wrzuć SVG do `/theme/assets/branding/`
- [ ] podmień header — `logo-main.svg`
- [ ] dodaj `favicon.ico` + `apple-touch-icon.png`
- [ ] podlinkuj `site.webmanifest` w `<head>`
- [ ] dodaj `og:image` do meta
- [ ] sprawdź mobile
