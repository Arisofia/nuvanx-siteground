# Component: Card

### Description

Superficie editorial plana (sin glass SaaS). Padding generoso Metal Pulido.

### Variants

| Variant | Clases | Use when |
|---------|--------|----------|
| Default | `.nvx-card` | Contenedor genérico |
| Secondary / ivory | `.nvx-card--secondary`, `--ivory` | Alias superficie |
| Brand card | `.nvx-brand-card__*` | Cards de listados brand |

### Structure

```html
<article class="nvx-card">
  <div class="nvx-card__media">…</div>
  <div class="nvx-card__content">
    <p class="nvx-brand-card__kicker">…</p>
    <h3 class="nvx-brand-card__title">…</h3>
    <p class="nvx-brand-card__body">…</p>
    <a class="nvx-brand-card__cta" href="…">Leer más</a>
  </div>
</article>
```

### Tokens

- padding: `--nvx-pad-card`
- border: `--nvx-color-line`
- radius: `--nvx-radius-card` (0)
- gap content: `--nvx-gap-tight`

### States

| State | Visual |
|-------|--------|
| Default | surface + line |
| Hover | border se mantiene sutil (sin elevación fuerte) |

### Accessibility

- Si es clicable, preferir un solo `<a>` envolvente o CTA con nombre accesible  
- Imágenes con `alt` significativo  

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| Usar pad-card token | Padding mágico 12px |
| Kicker para labels | Kicker con `01` (usar index number) |
