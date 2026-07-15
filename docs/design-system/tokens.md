# Tokens — Metal Pulido

**Archivo:** `assets/css/nvx-tokens.css`  
**Regla:** única fuente de paleta, shell y espaciado. Prohibido redefinir fuera.

## Color

| Token | Hex | Uso |
|-------|-----|-----|
| `--nvx-ink` | `#14161A` | Texto máximo contraste |
| `--nvx-charcoal` | `#2A2D33` | Fondos oscuros, hero |
| `--nvx-pearl` | `#F6F7F8` | Fondo claro |
| `--nvx-mist` | `#E8EAED` | Alternancia |
| `--nvx-silver` | `#C4C8CE` | Bordes |
| `--nvx-platinum` | `#9BA3AD` | Acento metal |
| `--nvx-white` | `#FFFFFF` | Superficie |
| `--nvx-metal-light` | `#E4E7EB` | Metal high |
| `--nvx-metal-mid` | `#B0B6BE` | Metal mid |
| `--nvx-metal-deep` | `#7A828C` | Metal deep |
| `--nvx-text-body` | `#3C4048` | Cuerpo |
| `--nvx-text-muted` | `#6E747C` | Secundario |
| `--nvx-color-paper` | `#F2F3F5` | Paper UI |
| `--nvx-color-ink` | `#12141A` | Ink semántico |
| `--nvx-color-line` | `rgba(20,22,26,.12)` | Líneas |

### Aliases (compat)

| Alias | Resuelve a |
|-------|------------|
| `--nvx-ivory` | pearl |
| `--nvx-sand` | mist |
| `--nvx-taupe` | silver |
| `--nvx-champagne` | platinum |
| `--nvx-accent` | platinum |

**Prohibido:** `#B89A5B` (oro), `#82958F` (cool-green) como acento activo.

## Spacing

| Token | Valor |
|-------|--------|
| `--space-1` … `--space-8` | 4 → 64px |
| `--nvx-gap-tight` | `clamp(12px, 1.5vw, 18px)` |
| `--nvx-gap-base` | `clamp(20px, 2.4vw, 32px)` |
| `--nvx-gap-wide` | `clamp(32px, 4vw, 56px)` |
| `--nvx-gap-section` | `clamp(48px, 6vw, 96px)` |
| `--nvx-pad-card` | `clamp(32px, 3vw, 48px)` |
| `--nvx-pad-section` / `--nvx-section-y` | `clamp(80px, 9vw, 140px)` |
| `--nvx-gutter` | `clamp(48px, 6vw, 120px)` |
| `--nvx-gutter-inner` | `clamp(24px, 4vw, 48px)` |

## Shell

| Token | Valor |
|-------|--------|
| `--nvx-shell` | `min(1480px, calc(100vw - var(--nvx-gutter)))` |
| `--nvx-readable` | `min(720px, 100%)` |
| `--nvx-measure` | `62ch` |
| `--nvx-measure-lead` | `48ch` |

## Typography tokens

| Token | Valor |
|-------|--------|
| `--nvx-serif` | Bodoni Moda |
| `--nvx-sans` | Manrope |
| `--nvx-type-display` | `clamp(2.75rem, 5.2vw, 5.5rem)` |
| `--nvx-type-h1` … `--nvx-type-h3` | scale cerrada |
| `--nvx-type-body` | `1rem` |
| `--nvx-type-kicker` | `0.6875rem` |
| `--nvx-lh-body` | `1.7` |
| `--nvx-track-kicker` | `0.2em` |

## Radii & media

| Token | Valor |
|-------|--------|
| `--nvx-radius-button` | `999px` (pill) |
| `--nvx-radius-card` / image | `0` (editorial recto) |
| `--nvx-media-ratio-*` | hero 16/9, doctor 5/6, clinic 19/12, … |

## Motion

| Token | Valor |
|-------|--------|
| `--nvx-ease` | `0.28s cubic-bezier(0.2, 0.7, 0.2, 1)` |

## Gradientes

- `--nvx-gradient-page` / `--nvx-gradient-hero` — white → pearl  
- `--nvx-gradient-section-warm` — pearl → mist  
- `--nvx-gradient-section-dark` — charcoal → ink  
- `--nvx-gradient-metal` — metal-light → metal-deep  
