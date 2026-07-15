# CSS ownership — NUVANX Medical (sin parches)

**Paleta:** Metal Pulido · **Tokens:** `nvx-tokens.css`

## Stack activo (único)

| Archivo | Rol |
|---------|-----|
| `nvx-tokens.css` | color, shell, spacing, type tokens |
| `nvx-base.css` | reset + util scales |
| `nvx-site-layout.css` | shell + section rhythm + single column |
| `nvx-header.css` | chrome header |
| `nvx-footer.css` | chrome footer |
| `nvx-components.css` | H1–H3, texto, media, botones (global) |
| `nvx-forms.css` | solo campos de formulario (si aplica) |
| `nvx-brand-home.css` | **solo** hero vídeo del home |

## Eliminado (no reintroducir)

Cualquier CSS “por página”: brand-system, treatment-*, secondary, sede, posts, pages, gutenberg, fluid, visual-system, typography-alignment.

## Reglas

1. Sin `!important`
2. Sin wrappers legacy (`nvx-hero-wrap` eliminado del template)
3. Sin stubs RETIRED
4. Sin forks tipográficos fuera de `nvx-components.css`
