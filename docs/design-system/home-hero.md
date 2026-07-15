# Component: Home Hero (vídeo full-bleed)

### Description

Stage de vídeo full-width con copy bottom-left y overlay de legibilidad.

### Structure

```html
<section class="nvx-home-hero-stage">
  <div class="nvx-home-video-feature">
    <div class="nvx-home-video-frame">
      <video id="nvx-home-hero-video" class="nvx-home-hero-video" autoplay muted loop playsinline poster="…"></video>
    </div>
  </div>
  <div class="nvx-brand-hero">
    <div class="nvx-home-hero-content">
      <h1 class="nvx-brand-hero__title">…</h1>
      <div class="nvx-home-hero-ctas">…</div>
    </div>
  </div>
</section>
```

### Tokens / reglas

| Prop | Valor |
|------|--------|
| Altura stage | `clamp(720px, 92svh, 980px)` |
| Vídeo | absolute cover, solo dentro del stage |
| Overlay | gradient dark 12–48% (legibilidad) |
| Copy | z-index 2, white |
| CTAs | light + secondary-on-dark |

### Ownership

`nvx-brand-home.css` + neutralización `body.home .nvx-hero-wrap` en base.  
Scope HTML: `#nvx-home-main.nvx-home` (no `.nvx-editorial-home`).

### JS

`nvx-brand-system.js` — autoplay muted del `#nvx-home-hero-video`.
