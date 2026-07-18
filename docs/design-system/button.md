# Component: Button

## Ownership

`nvx-components.css` is the only source for conversion-button visuals. The aliases `.nvx-button`, `.nvx-btn` and `.nvx-brand-btn` are equivalent and must share the same base contract.

Page, hero, treatment, home and footer styles may control only layout around buttons, such as `width`, `gap`, `margin-top` or alignment. They must not redefine background, border, radius, typography, text color, height, padding, hover or focus states.

## Canonical base

| Property | Value |
|---|---|
| Minimum height | `var(--nvx-control-size)` = 48 px |
| Horizontal padding | `var(--nvx-space-3)` = 24 px |
| Shape | `var(--nvx-radius-button)` = pill |
| Border | `var(--nvx-border-hairline)` |
| Typeface | Manrope via `var(--nvx-sans)` |
| Size | `var(--nvx-type-button)` = 0.75 rem |
| Weight | 600 |
| Tracking | `var(--nvx-track-button)` = 0.14 em |
| Case | Uppercase |
| Alignment | Centered |

HubSpot submit controls inherit this exact base. Their container may make them full width, but it must not create a second visual system.

## Variants

| Variant | Classes | Background | Text | Border | Use |
|---|---|---|---|---|---|
| Primary | `--primary` | Ink | Light | Ink | Main action on a light surface |
| Secondary | `--secondary` | Transparent | Ink | Ink | Secondary action on a light surface |
| Light | `--light` | Light | Ink | Light | Main action on a dark surface |
| Secondary on dark | `--secondary-on-dark` | Transparent | Light | Light translucent | Secondary action on a dark surface |

Known dark hero and conversion-band containers map historical `--primary` and `--secondary` markup to the same Light / Secondary-on-dark visual contract. This compatibility is centralized in `nvx-components.css`; no page-specific button colors are allowed.

## States

| State | Behavior |
|---|---|
| Hover | One-pixel elevation and canonical shadow; colors follow the variant |
| Focus-visible | Same visual state plus a two-pixel visible focus outline |
| Disabled / `aria-disabled` | 45% opacity, no pointer interaction, no elevation |
| Reduced motion | Transitions and elevation movement are removed |

Primary hover uses charcoal. Secondary inverts to ink with light text. Light uses the soft neutral surface. Secondary-on-dark inverts to a light fill with ink text.

## Exceptions that are not conversion buttons

The header CTA uses the canonical primary variant and only adjusts compact navigation sizing in `nvx-header.css`. Icon controls such as the hamburger and close buttons, Journal pagination, topic chips, card text links and accordion summaries are separate interaction components; they must use canonical tokens but are not converted into CTA pills.

## Examples

```html
<a class="nvx-button nvx-button--primary" href="/madrid/valoracion/">
  Reservar valoración gratuita
</a>

<a class="nvx-button nvx-button--secondary" href="/tratamientos/">
  Ver tratamientos
</a>

<a class="nvx-button nvx-button--light" href="/madrid/valoracion/">
  Reservar valoración gratuita
</a>

<a class="nvx-button nvx-button--secondary-on-dark" href="https://wa.me/34669319836">
  Contactar por WhatsApp
</a>
```
