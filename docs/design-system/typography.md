# Component: Typography

### Description

Jerarquía tipográfica editorial Metal Pulido. Display serif (Bodoni), UI/body sans (Manrope).

### Variants (clases)

| Clase | Uso | Tokens |
|-------|-----|--------|
| `.nvx-eyebrow` / `.nvx-kicker` / `.nvx-brand-kicker` | Label uppercase | platinum, track 0.2em, kicker size |
| `.nvx-display` | Hero principal | type-display, lh-display |
| `.nvx-heading` / `.nvx-brand-title` | Título sección | type-h2 |
| `.nvx-lead` | Lead / subtítulo | measure-lead, muted, weight 300 |
| `.nvx-copy` / `.nvx-brand-body` | Cuerpo | measure 62ch, lh 1.7 |
| `.nvx-brand-dropcap` | Inicial editorial | serif 3.4em |

### Section intro (pattern)

`.nvx-section-intro`, `.nvx-brand-section__intro`, `.nvx-index__intro` → **centrados**, max-width readable.

Cuerpo en columna: `.nvx-prose` / `.nvx-editorial-column` → **izquierda**, measure.

### States

| State | Visual |
|-------|--------|
| Default | ink / body / platinum kicker |
| On dark (hero video) | white / white-muted (home hero overrides) |

### Accessibility

- Headings en orden lógico `h1` → `h2` → `h3`
- `text-wrap: balance` en display/títulos
- Contraste body sobre paper ≥ AA con `#3C4048` / paper

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| Usar clases canónicas | Hardcodear Playfair/Inter/Cormorant |
| Measure 62ch en body | Full-bleed de párrafos largos |
| Kicker platinum | Kicker con dígitos 01–99 (usar index number) |

### Code Example

```html
<p class="nvx-eyebrow">Dirección médica</p>
<h2 class="nvx-heading">Criterio clínico, entorno sereno.</h2>
<p class="nvx-lead">Texto de apoyo más corto.</p>
<p class="nvx-copy">Párrafo de lectura…</p>
```
