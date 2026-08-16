# NUVANX Design System & Architecture Guide — Canonical 13-Point System

> **Official Design Architecture Document** · Medical Aesthetics & Laser Technology (Madrid)  
> Single Source of Truth for Visual Identity, CSS Tokens, Components, and Accessibility Governance.

---

## 1. Aesthetic Philosophy: The 13-Point System

The NUVANX visual identity embodies **Quiet Luxury** — an aesthetic of restraint, precision, and high clinical authority. Every element communicates medical rigor and transparency (*"En NUVANX, si no hay indicación, no se trata"*).

### 1. Function-Driven Restraint
Design elements exist to inform, reassure, and guide the patient. Decorative fluff, flashy gimmicks, and aggressive marketing tropes are strictly prohibited.

### 2. Single Validated Typographic Hierarchy
A dual-font pairing curated for editorial authority:
- **Serif (Display & Headings)**: `Playfair Display`, Georgia, serif — Used for H1, H2, and Display numbers.
- **Sans (Body & Functional UI)**: `Manrope`, Helvetica Neue, sans-serif — Used for body copy, buttons, forms, navigation, and badges.

### 3. Cool Neutral Palette
- **Light Surfaces**: `--nvx-light` (`#f7f7f5`), `--nvx-surface-base` (`#f1f1ef`), `--nvx-surface-soft` (`#e5e5e3`).
- **Dark Surfaces**: `--nvx-ink` (`#111111`), `--nvx-charcoal` (`#1c1c1e`), `--nvx-surface-dark` (`#070707`).
- **Subtle Linework**: `--nvx-color-line` (`rgba(17, 17, 17, 0.16)`), `--nvx-border-soft` (`#cecece`).
- **Warm Accent**: `--nvx-accent-gold` (`#C1A68D`), `--nvx-accent-gold-text` (`#735942`).

### 4. Strict Contrast & WCAG 2.2 Compliance
- Body text (#111111 on #f7f7f5 / #f1f1ef) provides $\approx 16.7:1$ contrast (exceeding WCAG AAA).
- Dark surface text (#f7f7f5 on #111111 / #1c1c1e) provides $\approx 17.6:1$ contrast.
- Hero media overlays and protective text-shadows ensure $\ge 4.5:1$ contrast across all image and video backdrops.

### 5. 8px Base Spatial Grid
All margins, paddings, gaps, and dimensions adhere to multiples of 8px (`--nvx-space-1: 8px` through `--nvx-space-12: 96px`), with fluid clamped layout gutters and section padding.

### 6. 48px Minimum Touch Targets
Every clickable element (buttons, hamburger, drawer links, dropdown links, footer links) is guaranteed a minimum touch target of $48\text{px}$ (`--nvx-control-size: 48px`) via `nvx-accessibility-governance.css`.

### 7. Canonical Button Contract
All conversion actions use a single pill-shaped geometry (`--nvx-radius-pill: 999px`) with uppercase tracking (`--nvx-track-button: 0.14em`), font size `--nvx-type-button` ($13\text{px}$), and tactile hover elevation (`translateY(-2px)` and shadow).

### 8. Tokenized Motion Architecture
Transitions and micro-interactions use standardized token durations (`--nvx-duration-instant` through `--nvx-duration-slower`) and easing curves (`--nvx-ease-standard`).

### 9. Accessible Focus Governance
Keyboard navigation is fully supported with custom `:focus-visible` outlines (`2px solid var(--button-focus)` with `3px` offset) and immediate adherence to `@media (prefers-reduced-motion: reduce)`.

### 10. Resilient Form & Lifecycle Governance
Conversion forms (HubSpot v2.js embed and native stages) incorporate progressive pulse skeletons, dynamic accessible iframe titles, and automated fallbacks to direct WhatsApp and phone channels if scripts are blocked or timeout.

### 11. Component Hierarchy & Scalability
Pragmatic size modifiers (`--sm`, `--md` [default], `--lg`) and explicit state machines (`default`, `hover`, `focus-visible`, `disabled`, `is-loading` / `aria-busy`).

### 12. Standardized Feedback Patterns
Structured feedback via `.nvx-toast` and accessible dialogs with `role="status"` and `aria-live="polite"`.

### 13. Forbidden Cliché Tropes
Strict adherence to anti-patterns:
- ❌ No purple-on-dark backgrounds.
- ❌ No colored border glows or neon outlines.
- ❌ No untracked oversized typography.
- ❌ No icon-stuffed bento boxes.
- ❌ No headline biscuit pills with pulsing dots.
- ❌ No gradient text fills.

---

## 2. Design Tokens Reference

All design tokens are defined in `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css`.

### 2.1 Colors

| Token | Value | Purpose |
|---|---|---|
| `--nvx-light` | `#f7f7f5` | Main off-white surface / dark-surface text |
| `--nvx-surface-base` | `#f1f1ef` | Base page surface / paper background |
| `--nvx-surface-soft` | `#e5e5e3` | Subtle card & secondary button hover surface |
| `--nvx-ink` | `#111111` | Primary ink color, text & primary buttons |
| `--nvx-charcoal` | `#1c1c1e` | Secondary dark background & hover ink |
| `--nvx-border-soft` | `#cecece` | Hairline border on light cards |
| `--nvx-color-line` | `rgba(17, 17, 17, 0.16)` | Subtle dividing lines |
| `--nvx-accent-muted` | `#4a4a4a` | Subtitle text, kickers, secondary copy |
| `--nvx-accent-success` | `#25d366` | WhatsApp brand & positive validation |
| `--nvx-accent-error` | `#c53030` | Form error states & error toasts |
| `--nvx-accent-warning` | `#d69e2e` | Warnings & advisory notices |
| `--nvx-accent-info` | `#4a5568` | Neutral feedback alerts |
| `--nvx-accent-gold` | `#C1A68D` | Brand gold accent |
| `--nvx-accent-gold-text` | `#735942` | Readable gold text on light backgrounds |

### 2.2 Spacing & Layout

| Token | Computed | Usage |
|---|---|---|
| `--nvx-space-1` | `8px` | Micro spacing, icon gaps |
| `--nvx-space-2` | `16px` | Inline button padding, item gaps |
| `--nvx-space-3` | `24px` | Card internal padding, block margins |
| `--nvx-space-4` | `32px` | Grid gaps, medium container margins |
| `--nvx-space-5` | `40px` | Large section item spacing |
| `--nvx-space-6` | `48px` | Major section gap, touch target |
| `--nvx-space-7` | `56px` | Large button height, spacious padding |
| `--nvx-space-8` | `64px` | Section margins, grid separation |
| `--nvx-space-9` | `72px` | Mobile header height |
| `--nvx-space-10` | `80px` | Desktop header height, major breaks |
| `--nvx-space-12` | `96px` | Maximum section padding |
| `--nvx-shell` | `min(1240px, calc(100vw - var(--nvx-gutter)))` | Canonical responsive content container |

### 2.3 Typography Scale

| Token | Size | Line Height | Tracking | Usage |
|---|---|---|---|---|
| `--nvx-type-display` | `clamp(2.8rem, 5vw, 4.2rem)` | `1.15` | `-0.02em` | Large display numbers & Hero H1 |
| `--nvx-type-h1` | `clamp(2.2rem, 4vw, 3.2rem)` | `1.15` | `-0.02em` | Page H1 titles (Playfair Display) |
| `--nvx-type-h2` | `clamp(1.7rem, 3vw, 2.4rem)` | `1.15` | `-0.02em` | Section H2 headings |
| `--nvx-type-h3` | `1.4rem` | `1.15` | `-0.02em` | Sub-section & Card H3 |
| `--nvx-type-lead` | `clamp(1.0625rem, 1.35vw, 1.25rem)` | `1.6` | `normal` | Hero lead paragraphs |
| `--nvx-type-body` | `1.0625rem` ($17\text{px}$) | `1.6` | `normal` | Standard editorial copy |
| `--nvx-type-small` | `0.875rem` ($14\text{px}$) | `1.5` | `normal` | Descriptions, meta copy |
| `--nvx-type-button` | `0.8125rem` ($13\text{px}$) | `1.0` | `0.14em` | Conversion buttons & actions |
| `--nvx-type-nav` | `0.8125rem` ($13\text{px}$) | `1.0` | `0.08em` | Desktop navigation menu |
| `--nvx-type-nav-compact` | `0.75rem` ($12\text{px}$) | `1.0` | `0.08em` | Header CTA and compact nav |
| `--nvx-type-kicker` | `0.75rem` ($12\text{px}$) | `1.2` | `0.20em` | Section eyebrow labels |
| `--nvx-type-caption` | `0.75rem` ($12\text{px}$) | `1.5` | `0.04em` | Captions, footnotes, badges |

### 2.4 Motion & Transitions

| Token | Value | Purpose |
|---|---|---|
| `--nvx-duration-instant` | `80ms` | Immediate state changes, micro-clicks |
| `--nvx-duration-fast` | `160ms` | Button hover, focus outline, link color |
| `--nvx-duration-normal` | `240ms` | Toasts, dropdowns, drawer reveal |
| `--nvx-duration-slow` | `320ms` | Modal open/close, card expansion |
| `--nvx-duration-slower` | `480ms` | Page transitions, hero fades |
| `--nvx-ease-standard` | `cubic-bezier(0.2, 0, 0, 1)` | Standard decelerated curve (natural) |
| `--nvx-transition-fast` | `160ms var(--nvx-ease-standard)` | Quick UI transitions |
| `--nvx-transition-normal` | `240ms var(--nvx-ease-standard)` | Standard dialog and toast transitions |

---

## 3. Component Architecture & Patterns

### 3.1 Button Component (`.nvx-brand-btn` / `.nvx-btn`)

Canonical conversion button. Consolidates all button aliases (`.nvx-brand-btn`, `.nvx-btn`, and backwards-compatible `.nvx-button`).

```html
<!-- Primary Button (Dark on Light) -->
<a href="/madrid/valoracion/" class="nvx-brand-btn nvx-btn--primary">
  <span>Solicitar valoración médica</span>
</a>

<!-- Secondary Button (Outline) -->
<a href="https://wa.me/34689317399" class="nvx-brand-btn nvx-btn--secondary" target="_blank" rel="noopener noreferrer">
  <svg class="icon-whatsapp" ...></svg>
  <span>Contactar por WhatsApp</span>
</a>

<!-- Light Button (On Dark Backgrounds) -->
<a href="/madrid/valoracion/" class="nvx-brand-btn nvx-btn--light">
  <span>Iniciar valoración</span>
</a>

<!-- Secondary on Dark Surface -->
<a href="https://wa.me/34689317399" class="nvx-brand-btn nvx-btn--secondary-on-dark" target="_blank" rel="noopener noreferrer">
  <span>Contactar por WhatsApp</span>
</a>

<!-- Small / Large Modifiers -->
<button class="nvx-brand-btn nvx-btn--primary nvx-btn--sm">Acción compacta</button>
<button class="nvx-brand-btn nvx-btn--primary nvx-btn--lg">Acción destacada</button>

<!-- Loading State -->
<button class="nvx-brand-btn nvx-btn--primary is-loading" aria-busy="true" disabled>
  <span class="nvx-spinner" aria-hidden="true"></span>
  <span>Procesando solicitud...</span>
</button>
```

### 3.2 Form & Input Controls

```html
<!-- Form Group with Validation States -->
<div class="nvx-form-group">
  <label for="nvx-email" class="nvx-label">Correo electrónico</label>
  <input type="email" id="nvx-email" class="nvx-input nvx-input--md is-error" aria-invalid="true" aria-describedby="nvx-email-error" value="correo-invalido">
  <span id="nvx-email-error" class="nvx-form-error" role="alert">Introduce una dirección de correo válida.</span>
</div>
```

### 3.3 Toast / Notification Pattern

```html
<div class="nvx-toast nvx-toast--success" role="status" aria-live="polite">
  <span class="nvx-toast__icon" aria-hidden="true">✓</span>
  <p class="nvx-toast__message">Solicitud enviada correctamente. Nos pondremos en contacto contigo en menos de 24h.</p>
  <button type="button" class="nvx-toast__close" aria-label="Cerrar notificación">&times;</button>
</div>
```

---

## 4. Accessibility Governance Contract

1. **Focus Rings**: `:focus-visible` must always display `outline: 2px solid var(--button-focus)` with offset $3\text{px}$.
2. **Keyboard Navigation**: All interactive elements must be accessible via Tab/Shift+Tab and triggered via Enter/Space.
3. **Reduced Motion**: All animated elements (drawers, spinners, hovers) must reset to instant or non-transform states when `prefers-reduced-motion: reduce` is detected.
4. **Target Sizing**: All click targets must maintain $\ge 48\text{px}$ in height and width.

---

## 5. Automated Quality Gates

To ensure strict design system compliance before merging or releasing, run the quality gates:

```bash
# Verify no hardcoded color values escape into CSS
node scripts/lint/no-hardcoded-colors.mjs --strict

# Verify no hardcoded pixel font sizes
node scripts/lint/no-hardcoded-fontsize.mjs

# Verify no dangerous inline styles in PHP templates
node scripts/lint/no-inline-layout-styles.mjs

# Full static lint test suite
npm run lint
```
