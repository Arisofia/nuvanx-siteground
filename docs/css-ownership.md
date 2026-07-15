# CSS ownership — NUVANX Medical

**Paleta:** Metal Pulido · **Tokens:** `nvx-tokens.css`  
**Docs:** [design-system/patterns.md](./design-system/patterns.md)

| Archivo | Ownership | No debe |
|---------|-----------|---------|
| `nvx-tokens.css` | color, shell, spacing, type scale tokens | — |
| `nvx-base.css` | reset, util scales, Gutenberg hero-wrap | paleta, footer chrome |
| `nvx-components.css` | button, type classes, card, index, media, shape, faq | layout de página |
| `nvx-site-layout.css` | shell + section-y global | colores |
| `nvx-header.css` | `#nvx-header` | — |
| `nvx-footer.css` | `.nvx-footer`, `.nvx-cta-banner` | — |
| `nvx-pages.css` | shells genéricos de página | — |
| `nvx-brand-home.css` | home composition + hero video | redefinir botones/tokens |
| `nvx-brand-treatment-*.css` | tratamientos | `:root` de paleta |
| `nvx-brand-system.css` | brand hubs / equipo | `:root` de paleta |
| `nvx-forms.css` | formularios | — |
| `nvx-posts.css` | blog | — |
| `nvx-sede-page.css` | sedes | — |
| `nvx-secondary-pages.css` / `gutenberg-pages` | interiores | — |

## Eliminado (no reintroducir)

- `nvx-fluid-organic-2026.css`
- `nvx-visual-system.css`
- `nvx-typography-alignment.css`
- `template-parts/footer/site-footer.php`
- `template-parts/header/site-header.php`

## Stack de enqueue

```
tokens → base → components → site-layout → header → footer → pages → [page]
```
