# CSS — un solo diseño

**Paleta:** Metal Pulido (`nvx-tokens.css`)

## Stack (todo el sitio)

| Archivo | Rol |
|---------|-----|
| `nvx-tokens.css` | color, shell, spacing, type tokens |
| `nvx-base.css` | reset + body únicamente |
| `nvx-site-layout.css` | shell, ritmo de sección, **body text measure + body images** |
| `nvx-header.css` | header |
| `nvx-footer.css` | footer |
| `nvx-components.css` | H1–H3, texto, botones, media roles, formularios |
| `nvx-patterns-editorial.css` | heroes, hubs (Endolift/Láser/ME) — **layout via tokens**, no px huérfanos |

Body content rules (margins, figures): see `docs/design-system/body-content.md`.

## Capas condicionales (siguen en el stack activo)

| Archivo | Cuándo | Qué |
|---------|--------|-----|
| `nvx-brand-home.css` | solo home | hero **vídeo** full-bleed |
| `nvx-posts.css` | contexto blog/journal | archive + single Journal |
| `nvx-mobile-hero-hierarchy.css` | global | jerarquía tipográfica hero en móvil |
| `nvx-medical-review.css` | review médico aprobado | sello/revisión |
| `nvx-hero-blackout.css` | flag `NVX_HERO_BLACKOUT` (**default on**) | heads en negro sin fotos; el **vídeo** de home se mantiene |

Blog, contacto, valoración, sedes, tratamientos, gracias, 404: **mismo stack base**; capas condicionales arriba.

## Eliminado (no reintroducir)

brand-system, treatment-*, secondary, sede, pages, gutenberg, forms (ahora en components), fluid, visual-system, typography-alignment, wrappers `nvx-hero-wrap`, stubs RETIRED, adaptaciones Divi (`.et_pb_*`) en CSS del tema, `!important`, paletas oro/cool-green.

Alias de plantilla eliminado: `template-parts/content/nvx-blog-index.php` (usar `nvx-blog-archive.php`).
