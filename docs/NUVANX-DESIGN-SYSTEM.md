# NUVANX Design System — Contrato Visual Canónico

**Versión:** 1.0 (entrega arquitectónica)  
**Alcance:** tema `nuvanx-medical` — home, tratamientos, sedes, equipo, comercial, formularios, journal.  
**Restricción:** no producción hasta aprobación de migración.

---

## 1. Principio rector

La referencia visual define un **sistema editorial global**, no una maqueta de home. Toda página compone los mismos componentes; las hojas `nvx-page-*.css` solo deciden **orden, contenido y modificadores permitidos**.

---

## 2. Paleta

| Token | Hex | Uso |
|-------|-----|-----|
| `--nvx-ink` | `#171717` | Texto principal, contraste máximo |
| `--nvx-charcoal` | `#2B2926` | Fondos oscuros, hero vídeo |
| `--nvx-ivory` | `#F7F1E8` | Fondo cálido secciones |
| `--nvx-sand` | `#E9DDCF` | Separadores, fondos alternos |
| `--nvx-taupe` | `#CBBBA8` | Bordes, reglas |
| `--nvx-champagne` | `#B89A5B` | Acento metal, links activos |
| `--nvx-white` | `#FFFFFF` | Superficie principal |

**Prohibido:** redefinir estos tokens fuera de `nvx-tokens.css`.  
**Eliminar en migración:** paleta paralela grafito `#111`, metal-light/mid/deep de `nvx-brand-home.css`.

---

## 3. Tipografía

### Familias oficiales

| Rol | Familia | Token |
|-----|---------|-------|
| Display / headings | **Bodoni Moda** | `--nvx-serif` |
| Body / UI | **Manrope** | `--nvx-sans` |
| Acento script (uso puntual) | Pinyon Script | `--nvx-script` |

**Prohibido:** Playfair Display, Inter, Source Sans 3, Cormorant en sistema activo.

### Escala cerrada

| Token | Uso | Rango |
|-------|-----|-------|
| `--nvx-type-display` | Hero principal | clamp 2.5rem → 4.75rem |
| `--nvx-type-h1` | Título página | clamp 2.25rem → 4.5rem |
| `--nvx-type-h2` | Título sección | clamp 1.75rem → 2.75rem |
| `--nvx-type-h3` | Subtítulo | clamp 1.25rem → 1.75rem |
| `--nvx-type-body-large` | Lead | 1.0625rem |
| `--nvx-type-body` | Cuerpo | 0.9375rem |
| `--nvx-type-small` | Meta / legal | 0.8125rem |
| `--nvx-type-kicker` | Eyebrow | 0.625rem |
| `--nvx-type-button` | Botones | 0.6875rem |

### Clases tipográficas

```html
<p class="nvx-eyebrow">Dirección Médica</p>
<h1 class="nvx-display">…</h1>
<h2 class="nvx-heading">…</h2>
<p class="nvx-lead">…</p>
<p class="nvx-copy">…</p>
```

---

## 4. Shell y grid

| Token | Valor |
|-------|-------|
| `--nvx-shell` | `min(1480px, calc(100vw - 64px))` |
| `--nvx-readable` | `min(760px, 100%)` |
| `--nvx-section-y` | `clamp(48px, 7vw, 96px)` |

Grid hero canónico (desktop): **38fr / 62fr** (copy / media).  
Grid índice: columnas fluidas con gap `--space-6`.

---

## 5. Espaciado

Escala única: `--space-1` (4px) … `--space-8` (64px).  
No introducir márgenes mágicos por página; usar tokens o utilidades de pattern.

---

## 6. Numeración

Los índices `01`, `02`, `03`… usan **solo**:

```html
<div class="nvx-index-item">
  <span class="nvx-index-item__number" aria-hidden="true">01</span>
  <p class="nvx-index-item__eyebrow">…</p>
  <h3 class="nvx-index-item__title">…</h3>
  <p class="nvx-index-item__body">…</p>
  <a class="nvx-index-item__link nvx-text-link" href="…">…</a>
</div>
```

**Prohibido:** `.nvx-brand-card__kicker` para dígitos.

---

## 7. Roles de imagen

Toda imagen declara rol + forma opcional:

```html
<img class="nvx-media nvx-media--doctor nvx-shape--organic-b" src="…" alt="…">
```

| Clase rol | Uso |
|-----------|-----|
| `nvx-media--hero` | Vídeo/imagen hero |
| `nvx-media--editorial` | Imagen narrativa intro |
| `nvx-media--treatment` | Hero tratamiento |
| `nvx-media--doctor` | Retrato médico |
| `nvx-media--clinic` | Fachada / interior |
| `nvx-media--panorama` | Banda clínicas |
| `nvx-media--contain` | Objeto contenido sin recorte |

| Clase forma | Uso |
|-------------|-----|
| `nvx-shape--organic-a` | Elipse suave panorámica |
| `nvx-shape--organic-b` | Retrato vertical |
| `nvx-shape--organic-c` | Feature editorial |
| `nvx-shape--none` | Recto / sin máscara |

**Prohibido:** `nth-child` para asignar máscara. **Prohibido:** dimensiones arbitrarias por página sin rol.

---

## 8. Botones y enlaces

```html
<a class="nvx-button nvx-button--primary" href="…">Reservar valoración</a>
<a class="nvx-button nvx-button--secondary" href="…">Ver tratamientos</a>
<a class="nvx-text-link" href="…">Leer más</a>
```

Una sola definición en `nvx-components.css`. Radio pill: `--nvx-radius-button`.

---

## 9. Patrones de sección

| Pattern | Función |
|---------|---------|
| `.nvx-pattern-hero` | Hero vídeo/imagen + CTAs |
| `.nvx-pattern-intro` | Bloque editorial inicial |
| `.nvx-pattern-method` | Índice método / pilares |
| `.nvx-pattern-authority` | Dirección, credenciales, feature image |
| `.nvx-pattern-clinics` | Sedes / panoramas |
| `.nvx-pattern-cta` | Banda conversión |
| `.nvx-pattern-faq` | Acordeón FAQ |

Contenedor índice genérico:

```html
<section class="nvx-index nvx-pattern-method">
  <div class="nvx-index__intro">…</div>
  <div class="nvx-index__items">…</div>
</section>
```

---

## 10. Header / Footer

Sin cambio de contrato: `#nvx-header`, `#nvx-footer` permanecen en hojas dedicadas.  
CTA header usa tokens champagne; no redefinir en page CSS.

---

## 11. FAQ y CTA

FAQ: estructura `<details class="nvx-faq-item">` unificada (migrar desde `nvx-brand-faq-*`).  
CTA: fondo `--nvx-charcoal` o `--nvx-ivory` según modifier; tipografía display para titular.

---

## 12. Responsive

Breakpoints de referencia: 980px, 768px, 480px (tokens `--nvx-bp-*`).  
Reglas responsive viven en componentes/patterns, no en page CSS salvo reordenación de grid.

---

## 13. Arquitectura de archivos (target)

```
assets/css/
  nvx-tokens.css          ← único :root
  nvx-base.css            ← reset, sin tokens
  nvx-components.css      ← tipografía, botones, index, media, faq, cta
  nvx-patterns.css        ← pattern-* layouts
  nvx-site-layout.css
  nvx-header.css
  nvx-footer.css
  nvx-pages.css           ← shell genérico WP
  pages/
    nvx-page-home.css     ← solo composición home
    nvx-page-treatment.css
    nvx-page-sede.css
    nvx-page-form.css
  journal/
    nvx-posts.css
```

**Retirar tras migración:** `nvx-brand-home.css`, `nvx-brand-system.css`, `nvx-brand-treatment-core.css`, `nvx-fluid-organic-2026.css` (lógica absorbida), `nvx-visual-system.css`, `nvx-typography-alignment.css`.

---

## 14. Compatibilidad transitoria

Durante migración se permiten alias CSS:

```css
.nvx-brand-kicker { @apply-equivalent .nvx-eyebrow; } /* fase bridge, luego eliminar */
```

Duración máxima bridge: 1 release staging. No deploy producción con bridge activo.