# CSS — un solo diseño

**Paleta:** Metal Pulido (`nvx-tokens.css`)

## Stack (todo el sitio)

| Archivo | Rol |
|---------|-----|
| `nvx-tokens.css` | color, shell, spacing, type tokens |
| `nvx-base.css` | reset + body únicamente |
| `nvx-site-layout.css` | shell, ritmo de sección, una columna |
| `nvx-header.css` | header |
| `nvx-footer.css` | footer |
| `nvx-components.css` | H1–H3, texto, botones, media, formularios |

## Única excepción

| Archivo | Cuándo | Qué |
|---------|--------|-----|
| `nvx-brand-home.css` | solo home | hero **vídeo** full-bleed |

Blog, contacto, valoración, sedes, tratamientos, gracias, 404: **mismo stack**, sin CSS propio.

## Eliminado (no reintroducir)

brand-system, treatment-*, secondary, sede, posts, pages, gutenberg, forms (ahora en components), fluid, visual-system, typography-alignment, wrappers `nvx-hero-wrap`, stubs RETIRED, `!important`, paletas oro/cool-green.
