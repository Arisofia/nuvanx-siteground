# Component: FAQ

### Description

Acordeón nativo con `<details>`. Canónico: `.nvx-faq` + items.

### Structure

```html
<section class="nvx-faq nvx-pattern-faq">
  <div class="nvx-section-intro">…</div>
  <details class="nvx-faq-item">
    <summary>Pregunta</summary>
    <div>
      <p class="nvx-copy">Respuesta…</p>
    </div>
  </details>
</section>
```

**Runtime legacy:** `nvx-brand-faq-*` puede coexistir en markup; migrar a `nvx-faq-item`.

### States

| State | Visual |
|-------|--------|
| Closed | summary + chevron |
| Open | `[open]` rota marker |

### Accessibility

- **Role:** nativo details/summary  
- **Keyboard:** Enter/Space en summary  
- Un summary por details  

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| details/summary | div click + JS sin teclado |
