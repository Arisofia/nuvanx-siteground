# Page Pattern Matrix

Mapeo URL → CSS terminal → patrones → deuda técnica.

## URLs de captura QA

| URL | Tipo | CSS terminal | Patrones |
|-----|------|--------------|----------|
| `/` | Home | `nvx-brand-home` | hero, intro, method, index, authority, clinics, FAQ, CTA |
| `/endolift-facial-papada-mandibula/` | Tratamiento | `nvx-brand-home` + componentes | hero, index, method, FAQ, CTA |
| `/endolaser-corporal-grasa-localizada/` | Tratamiento | `nvx-brand-home` + componentes | hero, index, method, FAQ, CTA |
| `/laser-co2-fraccionado-madrid-textura-cicatrices-poro/` | Tratamiento | componentes | hero, index, method, FAQ, CTA |
| `/equipo-medico/` | Autoridad | componentes | hero, cards, authority |
| `/medicina-estetica-chamberi/` | Clínica | componentes | hero, clinics, CTA |
| `/madrid/valoracion/` | Formulario P0 | forms + cierre canónico | form |
| `/contacto/` | Contacto | forms + cierre canónico | clinics, maps, CTA |

Viewports obligatorios: **1440×900**, **1024×768**, **390×844**.

## Global vs página

| Capacidad | Global | Página |
|-----------|--------|--------|
| Paleta / `:root` | `nvx-tokens.css` | prohibido redefinir |
| `font-family` | Playfair Display + Manrope | prohibido redefinir |
| Escala tipográfica | tokens + `nvx-visual-system.php` | solo elegir rol |
| Botones | `.nvx-button` y aliases | solo placement |
| Iconos | `.nvx-icon` + escala tokenizada | solo elegir forma/rol |
| Numeración | `.nvx-index-number` + aliases | solo contenido |
| Media | `.nvx-media--*` | elegir rol |
| FAQ / CTA | componentes globales | composición |
| Header / footer | global | prohibido redefinir |
| Orden de secciones | no | sí |

## Deuda permitida de compatibilidad

Las clases históricas se mantienen solo cuando todavía aparecen en contenido persistido. Deben resolver al mismo sistema global y no contener:

- fuentes literales;
- escalas particulares;
- color de icono fijo;
- números dentro de kickers o títulos;
- botones o CTA completos redefinidos por página.

## Gates

| Gate | Alcance |
|------|---------|
| Un solo `:root` canónico | global |
| Familias runtime = Playfair Display + Manrope | global |
| Sin Bodoni Moda, Cormorant Garamond, Inter o Source Sans activos | global |
| Sin activos SVG con color fijo | global |
| Escala de iconos 16/24/32/40 + trazo 1.5 | global |
| Secuencias `01`, `02`, `03` separadas del título | HTML + CSS |
| `<ol>` editorial visible | global |
| Sin estilos inline en componentes reutilizables | HTML |
| `scrollWidth - innerWidth = 0` | cada viewport |
