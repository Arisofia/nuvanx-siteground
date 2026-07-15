# CSS Load Order — nuvanx-medical

Auditoría generada desde `functions.php` → `nvx_theme_scripts()` (rama actual).

## Stack global (todas las páginas)

| Orden | Handle | Archivo | Dependencia |
|------:|--------|---------|-------------|
| 0 | `nvx-fonts` | Google Fonts CDN | — |
| 1 | `nvx-tokens` | `nvx-tokens.css` | `nvx-fonts` |
| 2 | `nvx-base` | `nvx-base.css` | `nvx-tokens` |
| 3 | `nvx-components` | `nvx-components.css` | `nvx-base` |
| 4 | `nvx-site-layout` | `nvx-site-layout.css` | `nvx-components` |
| 5 | `nvx-fluid-organic-2026` | `nvx-fluid-organic-2026.css` | `nvx-site-layout` |
| 6 | `nvx-header` | `nvx-header.css` | `nvx-fluid-organic-2026` |
| 7 | `nvx-footer` | `nvx-footer.css` | `nvx-header` |
| 8 | `nvx-pages` | `nvx-pages.css` | `nvx-footer` |

## Capas condicionales (antes de visual-system)

| Condición | Handle | Archivo |
|-----------|--------|---------|
| Página genérica (`is_generic_page`) | `nvx-gutenberg-pages` | `nvx-gutenberg-pages.css` |
| P0 / genérica / formulario / sede | `nvx-secondary-pages` | `nvx-secondary-pages.css` |

## Capa visual transversal

| Orden | Handle | Archivo | Notas |
|------:|--------|---------|-------|
| +1 | `nvx-visual-system` | `nvx-visual-system.css` | Siempre |
| +2 | `nvx-typography-alignment` | `nvx-typography-alignment.css` | Siempre |

## Addons por contexto (después de typography-alignment)

| Contexto | Handles | Archivos |
|----------|---------|----------|
| Formulario (contacto / valoración / HubSpot) | `nvx-forms` | `nvx-forms.css` |
| Blog / archivo / búsqueda / single post | `nvx-posts` | `nvx-posts.css` |
| Sede (`page-sede` o marcador `nvx-sede-page`) | `nvx-sede-page` | `nvx-sede-page.css` |
| **Home** (`front_page` / post 9) | `nvx-brand-home` + JS | `nvx-brand-home.css`, `nvx-brand-system.js` |
| **Tratamiento** (IDs 1241, 1200, 2017) | `nvx-brand-treatment-core` + addon | `nvx-brand-treatment-core.css` + `endolift` / `endolaser` / `co2` |
| Resto páginas marca (equipo, sedes comerciales, etc.) | `nvx-brand-system` + JS | `nvx-brand-system.css`, `nvx-brand-system.js` |

## Cadenas efectivas por tipo de página

### Home (`/`)

```
nvx-fonts → tokens → base → components → site-layout → fluid-organic → header → footer → pages → visual-system → typography-alignment → brand-home
```

**22 archivos CSS fuente** en tema; home carga **13 hojas** (+ minificadas en producción si aplica).

### Tratamiento (`/endolift-facial-papada-mandibula/`)

```
… → typography-alignment → brand-treatment-core → brand-treatment-endolift
```

### Equipo / sede comercial (`/equipo-medico/`, `/medicina-estetica-chamberi/`)

```
… → typography-alignment → brand-system
```

### Formulario (`/contacto/`, `/madrid/valoracion/`)

```
… → pages → secondary-pages → visual-system → typography-alignment → forms
```

## Archivos CSS en disco NO encolados por `functions.php`

| Archivo | Estado |
|---------|--------|
| `nvx-responsive.css` | Huérfano (11 líneas) |
| `style.css` | Solo metadatos tema WordPress |

## Hallazgos críticos del orden actual

1. **Siete archivos declaran `:root`** con tokens canónicos (`nvx-tokens`, `nvx-base`, `nvx-site-layout`, `nvx-fluid-organic-2026`, `nvx-brand-home`, `nvx-brand-system`, `nvx-brand-treatment-core`). El último `:root` ganador depende del contexto de página.
2. **`nvx-brand-home.css` carga después de 12 hojas** pero redefine paleta, tipografía (Playfair/Inter), shell, radios y componentes globales — anula el sistema Phase 3.
3. **`nvx-visual-system` + `nvx-typography-alignment`** llegan *antes* de brand-home/treatment, pero brand-* los pisa visualmente.
4. **Fuentes CDN** cargan Bodoni Moda + Manrope + Pinyon Script, pero brand-home/brand-system usan **Playfair Display + Inter** vía tokens locales.

## Métricas (fuentes sin `.min.css`)

| Métrica | Valor |
|---------|------:|
| Líneas CSS fuente totales | 10 671 |
| Selectores duplicados cross-file | 270 |
| Colisiones de tokens `:root` | 43 |
| Archivos con bloque `:root` | 7 |