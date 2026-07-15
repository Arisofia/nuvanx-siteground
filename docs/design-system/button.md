# Component: Button

### Description

CTA pill del sistema. Definición única en `nvx-components.css`. Aliases: `.nvx-brand-btn`, `.nvx-btn`.

### Variants

| Variant | Clases | Use when |
|---------|--------|----------|
| Primary | `--primary` / `--dark` | Acción principal (ink fill) |
| Secondary | `--secondary` | Secundaria (borde ink) |
| Light | `--light` | Sobre fondo oscuro (fill white) |
| Secondary on dark | `--secondary-on-dark` | Ghost sobre vídeo/hero |

### Props / properties (CSS)

| Property | Value |
|----------|--------|
| min-height | 48px |
| padding | 13px 28px |
| radius | `--nvx-radius-button` (999px) |
| font | Manrope, type-button, weight 600 |
| tracking | 0.1em |
| text | uppercase |

### States

| State | Visual | Behavior |
|-------|--------|----------|
| Default | según variant | — |
| Hover | charcoal (primary) / invert (secondary); `translateY(-1px)` | micro-elevación |
| Focus-visible | mismo hover | teclado |
| Disabled | (no estilo global) | no usar sin opacity + aria-disabled |

### Accessibility

- **Role:** link o button nativo  
- **Keyboard:** Enter/Space en `<button>`; Enter en `<a>`  
- **Focus:** `:focus-visible` con cambio de color (no quitar outline del browser sin reemplazo)

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| `nvx-button nvx-button--primary` | Redefinir botones en page CSS |
| Light/secondary-on-dark en hero vídeo | Primary ink sobre vídeo oscuro sin contraste |

### Code Example

```html
<a class="nvx-button nvx-button--primary" href="/madrid/valoracion/">Reservar valoración</a>
<a class="nvx-button nvx-button--secondary" href="/tratamientos/">Ver tratamientos</a>
```

**Header CTA (1.9.3+):** estilo propio en `nvx-header.css` (pill ink, 44px) — no redefinir desde pages.
