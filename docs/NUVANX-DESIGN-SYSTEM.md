# NUVANX Design System — Contrato Visual Canónico

**Versión:** 2.0 — **Paleta de Plata Pulida** (oficial)  
**Alcance:** tema `nuvanx-medical` — home, tratamientos, sedes, equipo, comercial, formularios, journal.  
**Regla de autoridad:** `nvx-tokens.css` es la **única** fuente de paleta, shell y espaciado.  
**Prohibido:** redefinir `:root` en `brand-*`, `fluid`, `treatment-*` o page CSS.

---

## 1. Principio rector

Sistema editorial global de **quiet luxury médico**. Toda página compone los mismos componentes; las hojas de página solo ordenan secciones y modificadores.

**Diseño de texto:** no monobloque alineado a la izquierda.  
- Intros de sección → **centrados** (kicker + título + lead).  
- Cuerpo de lectura → **izquierda** con measure `62ch`.  
- Kickers → platino, tracking amplio.  
- Display → Bodoni, `text-wrap: balance`.

---

## 2. Paleta — Plata Pulida

| Token | Hex | Uso |
|-------|-----|-----|
| `--nvx-ink` | `#14161A` | Texto máximo contraste |
| `--nvx-charcoal` | `#2A2D33` | Fondos oscuros / hero |
| `--nvx-pearl` | `#F6F7F8` | Fondo claro principal |
| `--nvx-mist` | `#E8EAED` | Alternancia / soft |
| `--nvx-silver` | `#C4C8CE` | Bordes |
| `--nvx-platinum` | `#9BA3AD` | Acento metal (oficial) |
| `--nvx-white` | `#FFFFFF` | Superficie |

**Aliases de compat:** `--nvx-ivory`→pearl, `--nvx-sand`→mist, `--nvx-taupe`→silver, `--nvx-champagne`→platinum.

**Metal:** `--nvx-metal-light/mid/deep` = `#E4E7EB` / `#B0B6BE` / `#7A828C` (plata, no oro).

**Prohibido:** acento verde-gris `#82958f`, champagne dorado `#B89A5B` como acento activo, redefinir paleta fuera de tokens.

---

## 3. Tipografía

| Rol | Familia |
|-----|---------|
| Display | **Bodoni Moda** |
| Body / UI | **Manrope** |
| Script puntual | Pinyon Script |

### Clases

```html
<p class="nvx-eyebrow">…</p>
<h1 class="nvx-display">…</h1>
<h2 class="nvx-heading">…</h2>
<p class="nvx-lead">…</p>
<p class="nvx-copy">…</p>
```

Aliases: `nvx-brand-kicker`, `nvx-brand-title`, `nvx-brand-body`.

---

## 4. Shell y espaciado (contrato de lujo)

| Token | Valor |
|-------|--------|
| `--nvx-gutter` | `clamp(48px, 6vw, 120px)` |
| `--nvx-gutter-inner` | `clamp(24px, 4vw, 48px)` |
| `--nvx-shell` | `min(1480px, calc(100vw - var(--nvx-gutter)))` |
| `--nvx-section-y` / pad-section | `clamp(80px, 9vw, 140px)` |
| `--nvx-gap-wide` | `clamp(32px, 4vw, 56px)` |
| `--nvx-pad-card` | `clamp(32px, 3vw, 48px)` |
| `--nvx-readable` / measure | `720px` / `62ch` |

Header, footer y sections usan **el mismo** `--nvx-shell` + gutter-inner.

---

## 5. Arquitectura de archivos

```
nvx-tokens.css          ← único :root (Plata Pulida + spacing + shell)
nvx-base.css            ← reset / tipografía base (sin redefinir paleta)
nvx-components.css      ← type, botones, cards, index, media, faq
nvx-site-layout.css     ← shell + section rhythm global
nvx-fluid-organic-2026  ← shapes/composición; aliases a tokens (no hex)
nvx-header / footer     ← chrome; shell = tokens
nvx-brand-* / pages     ← composición de página SOLAMENTE
```

---

## 6. Prohibiciones

1. `!important` en CSS del tema.  
2. `:root` paralelo en brand/treatment/fluid con shell o colores.  
3. Márgenes hardcodeados por página.  
4. Fonts no canónicas.  
5. `nth-child` para máscaras de imagen.  
6. Acento dorado o cool-green en runtime.

---

*v2.0 — sustitución del stack warm/champagne por Plata Pulida + shell unificado.*
