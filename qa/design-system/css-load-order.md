# CSS Load Order — nuvanx-medical

Fuente: `functions.php` → `nvx_theme_scripts()` + módulos condicionales (rama actual).

## Stack global (todas las páginas)

| Orden | Handle | Archivo | Dependencia |
|------:|--------|---------|-------------|
| 1 | `nvx-fonts` | `nvx-fonts.css` | — |
| 2 | `nvx-tokens` | `nvx-tokens.css` | `nvx-fonts` |
| 3 | `nvx-base` | `nvx-base.css` | `nvx-tokens` |
| 4 | `nvx-layout` | `nvx-site-layout.css` | `nvx-base` |
| 5 | `nvx-components` | `nvx-components.css` | `nvx-layout` |
| 6 | `nvx-patterns` | `nvx-patterns-editorial.css` | `nvx-components` |
| 7 | `nvx-header` | `nvx-header.css` | `nvx-patterns` |
| 8 | `nvx-footer` | `nvx-footer.css` | `nvx-header` |

## Capas condicionales

| Condición | Handle | Archivo |
|-----------|--------|---------|
| Front page | `nvx-home` | `nvx-brand-home.css` |
| Blog / archivo / búsqueda / single post | `nvx-posts` | `nvx-posts.css` |
| Revisión médica visible | `nvx-medical-review` | `nvx-medical-review.css` |
| Heroes (mobile hierarchy) | `nvx-mobile-hero-hierarchy` | `nvx-mobile-hero-hierarchy.css` |

## Inventario en disco (`assets/css/`)

12 hojas canónicas activas. No hay CSS huérfano en el directorio del tema.

`style.css` en la raíz del tema es solo metadata de WordPress (no se encola como hoja de diseño).

## Notas de higiene

- Tokens no usados se eliminan de `nvx-tokens.css`.
- El Journal vive en `nvx-posts.css`; no reintroducir estilos de blog en `nvx-patterns-editorial.css`.
- Utilidades de layout (`.nvx-span-*`, `.nvx-grid-12`) se conservan por contrato con contenido CMS.
