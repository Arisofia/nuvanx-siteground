# Primera entrega — Reconstrucción sistémica NUVANX

**Estado:** entrega arquitectónica para revisión. **Sin cambios en staging/producción.**

SHA auditoría: rama `fix/ticket-43-v4-1-reference-alignment` (working tree).

---

## Árbol de archivos propuesto

```
wp-content/themes/nuvanx-medical/
├── functions.php                          [MOD] nuevo enqueue, gates
├── assets/css/
│   ├── nvx-tokens.css                     [MOD] única fuente :root
│   ├── nvx-base.css                       [MOD] eliminar :root
│   ├── nvx-components.css                 [MOD] + componentes globales
│   ├── nvx-patterns.css                   [NEW] pattern-* layouts
│   ├── nvx-site-layout.css                [MOD] eliminar :root
│   ├── nvx-header.css                     [KEEP]
│   ├── nvx-footer.css                     [KEEP]
│   ├── nvx-pages.css                      [KEEP]
│   ├── nvx-gutenberg-pages.css            [KEEP]
│   ├── nvx-secondary-pages.css            [MOD] reducir duplicados
│   ├── nvx-forms.css                      [KEEP]
│   ├── nvx-posts.css                      [KEEP]
│   ├── pages/
│   │   ├── nvx-page-home.css              [NEW] composición home
│   │   ├── nvx-page-treatment.css         [NEW] estructura tratamiento
│   │   ├── nvx-page-sede.css              [NEW] migrar desde nvx-sede-page
│   │   └── nvx-page-form.css              [NEW] opcional split forms
│   └── [DEPRECATE → eliminar post-QA]
│       ├── nvx-brand-home.css
│       ├── nvx-brand-system.css
│       ├── nvx-brand-treatment-core.css
│       ├── nvx-brand-treatment-*.css
│       ├── nvx-fluid-organic-2026.css
│       ├── nvx-visual-system.css
│       ├── nvx-typography-alignment.css
│       └── nvx-responsive.css
├── assets/js/nvx-brand-system.js          [MOD] selectores nuevos
├── deploy/ticket-43/post_content_*.html   [MOD] clases index/media (sin copy)
├── qa/
│   ├── design-system/                     [DONE] auditoría
│   ├── capture-design-system.mjs          [NEW] screenshots before/after
│   └── gates/
│       └── design-system-gates.mjs        [NEW] CI gates §9
└── docs/NUVANX-DESIGN-SYSTEM.md           [DONE]
```

---

## Mapa de componentes (actual → target)

```
TIPOGRAFÍA
  nvx-brand-kicker      → nvx-eyebrow
  nvx-brand-title       → nvx-display / nvx-heading
  nvx-brand-hero__lead  → nvx-lead
  nvx-brand-body        → nvx-copy

ACCIONES
  nvx-brand-btn[*]      → nvx-button[*]
  nvx-brand-inline-link → nvx-text-link
  nvx-brand-card__link  → nvx-index-item__link

ÍNDICE
  nvx-brand-card        → nvx-index-item
  nvx-brand-card__kicker (01..06) → nvx-index-item__number
  nvx-home-pilar-item   → nvx-index-item (pattern-method)
  nvx-home-tratamiento-item → nvx-index-item (pattern index)

MEDIA
  nvx-home-hero-video   → nvx-media--hero
  nvx-home-image-feature → nvx-media--editorial + shape
  nvx-home-direccion-media → nvx-media--doctor + shape
  nvx-home-clinica-panorama → nvx-media--panorama + shape

PATTERNS
  nvx-home-hero-stage   → nvx-pattern-hero
  nvx-v3-intro          → nvx-pattern-intro
  nvx-home-metodo-pilares → nvx-pattern-method
  nvx-v3-direccion      → nvx-pattern-authority
  nvx-home-clinicas-panorama → nvx-pattern-clinics
  nvx-home-faq-editorial → nvx-pattern-faq
  nvx-home-cta-final-band → nvx-pattern-cta
```

---

## Tabla Global vs Página

| Artefacto | Global | Page CSS | HTML copy |
|-----------|:------:|:--------:|:---------:|
| Tokens `:root` | ✅ | ❌ | — |
| Tipografía | ✅ | ❌ | — |
| Botones | ✅ | ❌ | — |
| Index / números | ✅ | ❌ | ✅ estructura |
| Media roles | ✅ | ❌ | ✅ class en img |
| FAQ / CTA | ✅ | ❌ | ✅ contenido |
| Pattern layout | ✅ | instancia | ✅ orden secciones |
| Hero grid 38/62 | modifier global | ✅ home | — |
| Addon CO2 | — | ✅ mínimo | — |

---

## Plan de migración (fases)

### Fase 0 — Aprobación (actual)

- [x] Auditoría CSS
- [x] Contrato visual
- [ ] **STOP — revisión arquitectónica**

### Fase 1 — Fundación (staging only)

1. Consolidar `nvx-tokens.css` (absorber tokens brand + fluid + base).
2. Crear componentes globales en `nvx-components.css` + `nvx-patterns.css`.
3. Implementar `design-system-gates.mjs` en CI.
4. Captura baseline QA (9 URLs × 3 viewports).

### Fase 2 — HTML bridge (sin copy)

1. Migrar clases en `post_content_v3-production-copy.html`: kicker→number, media roles.
2. Alias CSS temporales `.nvx-brand-*` → nuevos componentes.

### Fase 3 — Home

1. Extraer composición a `pages/nvx-page-home.css` (~200 líneas).
2. Eliminar `nvx-brand-home.css`.
3. Captura after + diff visual.

### Fase 4 — Tratamientos

1. Reemplazar `nvx-brand-treatment-core.css` por `nvx-page-treatment.css`.
2. Reducir addons a modifiers mínimos.
3. Unificar hero/index/faq/cta con home.

### Fase 5 — Brand pages + journal + forms

1. Migrar `nvx-brand-system.css` consumidores a patterns.
2. Retirar `nvx-visual-system`, `nvx-typography-alignment`, `nvx-fluid-organic-2026`.
3. QA completo + aprobación visual.

**No merge a producción hasta sign-off explícito.**

---

## Estimación de líneas eliminadas

| Archivo | Líneas actuales | Target | Δ eliminadas |
|---------|----------------:|-------:|-------------:|
| `nvx-brand-home.css` | 1 581 | ~220 | **~1 360** |
| `nvx-brand-system.css` | 1 119 | 0 (retirado) | **~1 119** |
| `nvx-brand-treatment-core.css` | 706 | ~120 | **~586** |
| `nvx-fluid-organic-2026.css` | 586 | 0 | **~586** |
| `nvx-visual-system.css` | 444 | 0 | **~444** |
| `nvx-typography-alignment.css` | 169 | 0 | **~169** |
| `nvx-base.css` (:root duplicado) | ~200 tokens | 0 | **~200** |
| `nvx-site-layout.css` (:root) | ~30 | 0 | **~30** |
| Addons tratamiento | 179 | ~60 | **~119** |
| **Subtotal eliminado** | | | **~4 613** |
| **Nuevo** (`nvx-patterns`, `nvx-page-*`, gates) | | ~1 100 | |
| **Reducción neta estimada** | | | **~3 500 líneas** |

Total fuente actual: **10 671 líneas** → objetivo **~7 100 líneas** (−33%).

---

## Lista exacta de archivos que cambiarán

### Crear

- `assets/css/nvx-patterns.css`
- `assets/css/pages/nvx-page-home.css`
- `assets/css/pages/nvx-page-treatment.css`
- `assets/css/pages/nvx-page-sede.css`
- `qa/capture-design-system.mjs`
- `qa/gates/design-system-gates.mjs`
- `.github/workflows/design-system-qa.yml`

### Modificar

- `wp-content/themes/nuvanx-medical/functions.php`
- `assets/css/nvx-tokens.css`
- `assets/css/nvx-base.css`
- `assets/css/nvx-components.css`
- `assets/css/nvx-site-layout.css`
- `assets/css/nvx-secondary-pages.css`
- `assets/css/nvx-header.css` (solo si tokens cambian)
- `assets/js/nvx-brand-system.js`
- `deploy/ticket-43/post_content_v3-production-copy.html` (clases, no copy)
- Todas las páginas tratamiento/equipo/sedes en DB staging (clases)

### Eliminar (post-migración QA verde)

- `assets/css/nvx-brand-home.css` + `.min.css`
- `assets/css/nvx-brand-system.css` + `.min.css`
- `assets/css/nvx-brand-treatment-core.css` + `.min.css`
- `assets/css/nvx-brand-treatment-endolift.css` + `.min.css`
- `assets/css/nvx-brand-treatment-endolaser.css` + `.min.css`
- `assets/css/nvx-brand-treatment-co2.css` + `.min.css`
- `assets/css/nvx-fluid-organic-2026.css` + `.min.css`
- `assets/css/nvx-visual-system.css` + `.min.css`
- `assets/css/nvx-typography-alignment.css` + `.min.css`
- `assets/css/nvx-responsive.css`
- `assets/css/nvx-sede-page.css` (migrado a pages/)

### No tocar en esta iniciativa

- Producción (`nuvanx.com`)
- Copy / textos visibles
- Purga CDN
- Nuevas capas V4.2 en `nvx-brand-home.css`

---

## Artefactos generados

| Archivo | Descripción |
|---------|-------------|
| `qa/design-system/css-load-order.md` | Orden de carga y cadenas por página |
| `qa/design-system/token-collisions.json` | 43 colisiones token |
| `qa/design-system/duplicate-selectors.json` | 270 selectores duplicados |
| `qa/design-system/component-inventory.md` | Inventario componentes |
| `qa/design-system/page-pattern-matrix.md` | Matriz URL / pattern |
| `qa/design-system/audit-summary.json` | Métricas machine-readable |
| `qa/design-system/component-classes.json` | Clases por prefijo |
| `docs/NUVANX-DESIGN-SYSTEM.md` | Contrato visual canónico |
| `scripts/design-system/audit-css.mjs` | Script re-ejecutable |

Re-ejecutar auditoría:

```bash
node scripts/design-system/audit-css.mjs
```