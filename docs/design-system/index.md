# Component: Index (pilares / tratamientos)

### Description

Grid de ítems numerados `01`, `02`… para método o catálogo.

### Structure

```html
<section class="nvx-index nvx-pattern-method">
  <div class="nvx-index__intro">
    <p class="nvx-eyebrow">Método</p>
    <h2 class="nvx-heading">…</h2>
  </div>
  <div class="nvx-index__items" style="--nvx-index-columns: 3">
    <div class="nvx-index-item">
      <a class="nvx-index-item__link" href="…">
        <span class="nvx-index-item__number" aria-hidden="true">01</span>
        <p class="nvx-index-item__eyebrow">…</p>
        <h3 class="nvx-index-item__title">…</h3>
        <p class="nvx-index-item__body">…</p>
      </a>
    </div>
  </div>
</section>
```

### Tokens

- columns: `--nvx-index-columns` (default 3)
- gap: grid via components
- number: serif display-like

### Accessibility

- Número con `aria-hidden="true"` si es decorativo  
- Enlace con nombre accesible (título dentro del link)

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| `nvx-index-item__number` | `.nvx-brand-card__kicker` con `01` |
