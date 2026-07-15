# NUVANX Design System — Contrato Visual Canónico

**Versión:** 2.1 — **Metal Pulido** (oficial)  
**Documentación completa:** [`docs/design-system/`](./design-system/README.md)  
**Alcance:** tema `nuvanx-medical`  
**Autoridad de tokens:** `assets/css/nvx-tokens.css`  

---

## 1. Principio rector

Sistema editorial global de **quiet luxury médico**. Toda página compone los mismos componentes; las hojas de página solo ordenan secciones y modificadores.

**Diseño de texto**
- Intros de sección → **centrados** (kicker + título + lead).  
- Cuerpo → **izquierda** con measure `62ch`.  
- Kickers → platino, tracking amplio.  
- Display → Bodoni, `text-wrap: balance`.

---

## 2. Paleta — Metal Pulido

| Token | Hex | Uso |
|-------|-----|-----|
| `--nvx-ink` | `#14161A` | Texto máximo contraste |
| `--nvx-charcoal` | `#2A2D33` | Fondos oscuros / hero |
| `--nvx-pearl` | `#F6F7F8` | Fondo claro |
| `--nvx-mist` | `#E8EAED` | Alternancia |
| `--nvx-silver` | `#C4C8CE` | Bordes |
| `--nvx-platinum` | `#9BA3AD` | Acento metal |
| `--nvx-white` | `#FFFFFF` | Superficie |

**Metal:** `--nvx-metal-light/mid/deep` = `#E4E7EB` / `#B0B6BE` / `#7A828C`.  
**Aliases:** ivory→pearl, sand→mist, taupe→silver, champagne→platinum.

**Prohibido:** redefinir paleta fuera de tokens; oro `#B89A5B`; cool-green `#82958F`.

---

## 3. Tipografía

| Rol | Familia |
|-----|---------|
| Display | **Bodoni Moda** |
| Body / UI | **Manrope** |
| Script | Pinyon Script (puntual) |

Clases: `nvx-eyebrow`, `nvx-display`, `nvx-heading`, `nvx-lead`, `nvx-copy`  
→ Ver [typography.md](./design-system/typography.md)

---

## 4. Shell y espaciado

| Token | Valor |
|-------|--------|
| `--nvx-gutter` | `clamp(48px, 6vw, 120px)` |
| `--nvx-shell` | `min(1480px, calc(100vw - gutter))` |
| `--nvx-section-y` | `clamp(80px, 9vw, 140px)` |
| `--nvx-pad-card` | `clamp(32px, 3vw, 48px)` |
| `--nvx-gap-wide` | `clamp(32px, 4vw, 56px)` |

Header/footer usan el **mismo** shell.  
→ [shell-layout.md](./design-system/shell-layout.md)

---

## 5. Componentes (índice)

| Componente | Doc |
|------------|-----|
| Tokens | [tokens.md](./design-system/tokens.md) |
| Button | [button.md](./design-system/button.md) |
| Card | [card.md](./design-system/card.md) |
| Media / Shape | [media.md](./design-system/media.md) |
| Index | [index.md](./design-system/index.md) |
| FAQ | [faq.md](./design-system/faq.md) |
| Header | [header.md](./design-system/header.md) |
| Footer | [footer.md](./design-system/footer.md) |
| Home hero | [home-hero.md](./design-system/home-hero.md) |
| Patterns | [patterns.md](./design-system/patterns.md) |
| Audit | [AUDIT.md](./design-system/AUDIT.md) |

---

## 6. Arquitectura CSS

```
nvx-tokens.css          ← único :root color/shell/spacing
nvx-base.css            ← reset + util scales (sin paleta)
nvx-components.css      ← button, type, card, index, media, faq
nvx-site-layout.css     ← shell + section rhythm
nvx-header.css / footer.css
nvx-pages.css + page CSS (composición)
```

**Eliminado del stack:** fluid-organic, visual-system, typography-alignment.

---

## 7. Prohibiciones

1. `!important` en CSS del tema.  
2. `:root` de color en brand/treatment/page.  
3. Márgenes mágicos por página.  
4. Fonts no canónicas.  
5. `nth-child` para shapes.  
6. Acento oro o cool-green.  

---

*v2.1 — Metal Pulido documentado al completo en `docs/design-system/`.*
