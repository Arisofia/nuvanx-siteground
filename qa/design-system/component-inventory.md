# Component Inventory — estado actual vs sistema objetivo

## Leyenda

| Estado | Significado |
|--------|-------------|
| **GLOBAL** | Debe vivir en capa de componentes reutilizable |
| **PATTERN** | Composición de sección (solo layout/orden) |
| **PAGE** | Permitido solo como composición en `nvx-page-*.css` |
| **LEGACY** | Duplicado / a eliminar en migración |
| **TARGET** | Clase canónica del nuevo sistema (aún no implementada) |

---

## Tipografía

| Clase actual | Archivo(s) | Estado | Target |
|--------------|------------|--------|--------|
| `.nvx-brand-kicker` | brand-home, brand-system, treatment-core | LEGACY | `.nvx-eyebrow` |
| `.nvx-brand-title` | brand-* | LEGACY | `.nvx-display` / `.nvx-heading` |
| `.nvx-brand-subtitle` | brand-* | LEGACY | `.nvx-heading` (nivel h3) |
| `.nvx-brand-body` | brand-* | LEGACY | `.nvx-copy` |
| `.nvx-brand-hero__lead` | brand-* | LEGACY | `.nvx-lead` |
| `.nvx-home-editorial__lead` | brand-home | PAGE | `.nvx-lead` + modifier pattern |
| `--nvx-type-*` | nvx-tokens | GLOBAL | Mantener en `nvx-tokens.css` |
| `--nvx-font-serif` (Playfair) | brand-home, brand-system | LEGACY | `--nvx-serif` (Bodoni Moda) |

---

## Botones y enlaces

| Clase actual | Archivo(s) | Estado | Target |
|--------------|------------|--------|--------|
| `.nvx-brand-btn` | brand-home, brand-system, treatment | LEGACY | `.nvx-button` |
| `.nvx-brand-btn--primary` | brand-* | LEGACY | `.nvx-button--primary` |
| `.nvx-brand-btn--secondary` | brand-* | LEGACY | `.nvx-button--secondary` |
| `.nvx-brand-inline-link` | brand-* | LEGACY | `.nvx-text-link` |
| `.nvx-brand-card__link` | brand-* (×4 archivos) | LEGACY | `.nvx-text-link` |
| `.nvx-brand-card__cta` | brand-* (×4 archivos) | LEGACY | `.nvx-text-link--cta` |

---

## Cards e índice numerado

| Clase actual | Uso HTML | Problema | Target |
|--------------|----------|----------|--------|
| `.nvx-brand-card` | Tratamientos, método, sedes | Mezcla número + eyebrow + CTA | `.nvx-index-item` |
| `.nvx-brand-card__kicker` | `01` `02` `03`… y labels | **Anti-patrón**: número en kicker | `.nvx-index-item__number` |
| `.nvx-brand-card__title` | Títulos ítem | OK semántico | `.nvx-index-item__title` |
| `.nvx-brand-card__body` | Cuerpo | OK | `.nvx-index-item__body` |
| `.nvx-home-pilar-item` | Método home | Duplica index | `.nvx-index-item` dentro `.nvx-pattern-method` |
| `.nvx-home-tratamiento-item` | Catálogo home | Duplica index | `.nvx-index-item` |

**Instancias kicker con dígitos** (post_content home): `01`–`06` (tratamientos), `01`–`03` (método).

---

## Media e imágenes

| Clase actual | Archivo | Rol implícito | Target |
|--------------|---------|---------------|--------|
| `.nvx-home-hero-video` | brand-home | hero video | `.nvx-media--hero` |
| `.nvx-home-video-feature` | brand-home | hero video container | `.nvx-pattern-hero` child |
| `.nvx-home-image-feature__img` | brand-home | editorial feature | `.nvx-media--editorial` |
| `.nvx-home-direccion-media img` | brand-home | doctor portrait | `.nvx-media--doctor` + `.nvx-shape--organic-b` |
| `.nvx-home-clinica-panorama__media` | brand-home | clinic exterior | `.nvx-media--clinic` + `.nvx-shape--organic-a` |
| `.nvx-brand-hero__media` | brand-system, treatment | treatment hero | `.nvx-media--treatment` |
| `nth-child(2n)` en tratamientos | brand-home | **Anti-patrón**: máscara por posición | clase shape explícita |

Reglas `clip-path` / `mask-image` detectadas en `nvx-brand-home.css` (líneas ~809, ~1044, ~1255) y `nvx-fluid-organic-2026.css`.

---

## FAQ

| Clase actual | Archivo | Target |
|--------------|---------|--------|
| `.nvx-brand-faq-accordion` | brand-* | componente global FAQ |
| `.nvx-brand-faq-item` | brand-* | item FAQ |
| `.nvx-brand-faq-content` | brand-* | panel respuesta |
| `.nvx-home-faq-editorial` | brand-home | **PAGE** → solo `.nvx-pattern-faq` wrapper |

---

## CTA

| Clase actual | Archivo | Target |
|--------------|---------|--------|
| `.nvx-brand-section--cta` | brand-* | `.nvx-pattern-cta` |
| `.nvx-home-cta-final-band` | brand-home | `.nvx-pattern-cta` modifier |
| `.nvx-brand-cta` | brand-system | `.nvx-pattern-cta` |

---

## Header / Footer / Shell

| Clase | Archivo | Estado |
|-------|---------|--------|
| `#nvx-header`, `.nvx-nav__*` | nvx-header.css | GLOBAL — mantener |
| `.nvx-footer__*` | nvx-footer.css | GLOBAL — mantener |
| `--nvx-shell` | tokens, base, site-layout, **brand-home** | Colisión — unificar en tokens |
| `.nvx-v3-shell` | brand-home | LEGACY → `.nvx-shell` token |

---

## Patrones de página (home actual → objetivo)

| Sección home | Clases wrapper actuales | Pattern target |
|--------------|----------------------|----------------|
| Hero vídeo | `.nvx-home-hero-stage`, `.nvx-brand-hero` | `.nvx-pattern-hero` |
| Intro editorial | `.nvx-v3-intro`, `.nvx-home-editorial` | `.nvx-pattern-intro` |
| Método | `.nvx-home-metodo-pilares` | `.nvx-pattern-method` |
| Tratamientos | `.nvx-home-tratamientos-editorial` | `.nvx-index` |
| Imagen feature | `.nvx-home-image-feature` | `.nvx-pattern-authority` |
| Clínicas | `.nvx-home-clinicas-panorama` | `.nvx-pattern-clinics` |
| Dirección | `.nvx-v3-direccion` | `.nvx-pattern-authority` |
| FAQ | `.nvx-home-faq-editorial` | `.nvx-pattern-faq` |
| CTA final | `.nvx-home-cta-final-band` | `.nvx-pattern-cta` |

---

## Conteo por prefijo (clases en CSS fuente)

| Prefijo | Clases únicas |
|---------|--------------:|
| `nvx-brand-*` | 52 |
| `nvx-home-*` | 42 |
| `nvx-fluid-*` | 18 |
| `nvx-editorial-*` | 6 |
| `nvx-header-*` / `#nvx-header` | ~30 |
| `nvx-footer-*` | ~15 |

Ver detalle machine-readable: `qa/design-system/component-classes.json`.