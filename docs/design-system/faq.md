# Component: FAQ

### Description

Acordeón nativo con `<details>`. Canónico: `.nvx-faq` + items.

### Structure

```html
<section class="nvx-faq" aria-labelledby="faq-title">
  <h2 id="faq-title">Preguntas frecuentes</h2>
  <details class="nvx-faq-item">
    <summary><span>Pregunta…</span></summary>
    <div class="nvx-brand-faq-content">
      <p>Respuesta…</p>
    </div>
  </details>
</section>
```

**Runtime:** estilos en `nvx-components.css` unifican:

- `.nvx-faq`, `.nvx-brand-faq-accordion`, `.nvx-home-faq-editorial`, `.nvx-brand-faq-item`
- `details/summary` bare en `.nvx-brand-section`, `.nvx-brand-grid`, `.entry-content` y `.nvx-page__content` (p. ej. FAQ de sedes CMS)

Todos los menús de preguntas frecuentes deben verse y comportarse igual (serif summary, chevron, open state).

### States

| State | Visual |
|-------|--------|
| Closed | serif summary + chevron platino (45°) |
| Open | fondo pearl suave; chevron 225°; respuesta en measure body |

### Accessibility

- **Role:** nativo details/summary  
- **Keyboard:** Enter/Space en summary  
- Un summary por details  

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| details/summary | div click + JS sin teclado |
