# Page Pattern Matrix

Mapeo URL → CSS cargado → patrones de sección → deuda técnica.

## URLs de captura QA (antes/después)

| URL | Tipo | CSS terminal | Patrones actuales | Patrones target |
|-----|------|--------------|-------------------|-----------------|
| `/` | Home | `nvx-brand-home` | hero-stage, v3-intro, metodo-pilares, tratamientos-editorial, image-feature, clinicas-panorama, direccion, faq-editorial, cta-final-band | hero, intro, method, index, authority, clinics, authority, faq, cta |
| `/endolift-facial-papada-mandibula/` | Tratamiento + addon | `nvx-brand-treatment-endolift` | brand-hero--laser, brand-grid, brand-faq, brand-section--cta | hero, index, method, faq, cta |
| `/endolaser-corporal-grasa-localizada/` | Tratamiento + addon | `nvx-brand-treatment-endolaser` | (mismo core) | idem |
| `/laser-co2-fraccionado-madrid-textura-cicatrices-poro/` | Tratamiento + addon | `nvx-brand-treatment-co2` | (mismo core) + Source Sans 3 residual | idem |
| `/equipo-medico/` | Brand system | `nvx-brand-system` | brand-hero, brand-grid, brand-card--team | hero, index, authority |
| `/medicina-estetica-chamberi/` | Brand system | `nvx-brand-system` | brand-hero, brand-grid, clinic cards | hero, clinics, cta |
| `/madrid/valoracion/` | Form P0 | `nvx-forms` | secondary-pages shell + HubSpot | pattern-form (nuevo) |
| `/contacto/` | Form | `nvx-forms` | idem | pattern-form |

Viewports obligatorios: **1440×900**, **1024×768**, **390×844**.

---

## Matriz Global vs Página

| Capacidad | Global (`nvx-components` + tokens) | Solo composición página |
|-----------|--------------------------------------|-------------------------|
| Paleta / `:root` | ✅ `nvx-tokens.css` | ❌ prohibido |
| `font-family` | ✅ Bodoni + Manrope | ❌ prohibido |
| `.nvx-button` | ✅ | ❌ solo placement |
| `.nvx-index-item__number` | ✅ | ❌ |
| `.nvx-media--*` + `.nvx-shape--*` | ✅ | ❌ solo elección de rol |
| `.nvx-pattern-*` layout | ✅ definición | ✅ instanciación orden secciones |
| FAQ estructura | ✅ | ❌ |
| CTA estructura | ✅ | ❌ |
| Header / footer | ✅ `nvx-header/footer` | ❌ |
| Grid hero 38/62 | ❌ | ✅ `nvx-page-home` modifier |
| Orden secciones home | ❌ | ✅ |
| Addon tratamiento CO2 | ❌ | ✅ mínimo (`nvx-page-treatment--co2`) |

---

## Overrides detectados por página (deuda)

| Página | Archivo | Override prohibido en target |
|--------|---------|------------------------------|
| Home | `nvx-brand-home.css` | `:root` completo, Playfair/Inter, botones, FAQ, CTA, media masks, kicker números |
| Tratamientos | `nvx-brand-treatment-core.css` | Duplica brand-system: hero, cards, FAQ, CTA, grid |
| Equipo/sedes | `nvx-brand-system.css` | Segunda copia de componentes + Playfair |
| Formularios | `nvx-forms.css` | OK — aislado |
| Sedes template | `nvx-sede-page.css` | Parcial duplicación layout |

---

## Gates CI propuestos (por URL de captura)

| Gate | Alcance |
|------|---------|
| `:root` tokens canónicos = 1 archivo | global |
| `font-family` ∉ {Bodoni Moda, Manrope, Pinyon Script} | global |
| `.nvx-brand-card__kicker` sin dígitos `01`–`99` | HTML + CSS |
| `!important` count = 0 en CSS activo | global |
| Marcadores legacy ausentes | global |
| Page CSS no redefine `.nvx-button`, `.nvx-heading`, etc. | por archivo page |
| Toda `<img>` en `#nvx-home-main` tiene clase `nvx-media--*` | HTML |
| `scrollWidth - innerWidth = 0` | por viewport captura |