# Patterns & CSS ownership

## Patrones de sección

| Pattern | Función |
|---------|---------|
| `.nvx-pattern-hero` | Hero + CTAs |
| `.nvx-pattern-intro` | Editorial inicial |
| `.nvx-pattern-method` | Pilares / método |
| `.nvx-pattern-authority` | Dirección / credenciales |
| `.nvx-pattern-clinics` | Sedes |
| `.nvx-pattern-cta` | Conversión |
| `.nvx-pattern-faq` | FAQ |

## Ownership de archivos

| Archivo | Dueño de |
|---------|----------|
| `nvx-tokens.css` | único `:root` color/shell/spacing |
| `nvx-base.css` | reset, type base, util scales, hero-wrap Gutenberg |
| `nvx-components.css` | button, type, card, index, media, shape, faq |
| `nvx-site-layout.css` | shell + section rhythm global |
| `nvx-header.css` | chrome header |
| `nvx-footer.css` | chrome footer + cta banner |
| `nvx-pages.css` | shells de página genéricos |
| `nvx-brand-home.css` | composición home |
| `nvx-brand-treatment-*.css` | tratamientos |
| `nvx-brand-system.css` | equipo / hubs brand |
| `nvx-forms.css` / `posts` / `sede-page` | contextos |

## Eliminado (no volver a encolar)

- `nvx-fluid-organic-2026.css`
- `nvx-visual-system.css`
- `nvx-typography-alignment.css`

## Page types

| Tipo | CSS terminal |
|------|----------------|
| Home | brand-home |
| Tratamiento | treatment-core + addon |
| Brand | brand-system |
| Sede | sede-page |
| Forms | forms |
| Blog | posts |
