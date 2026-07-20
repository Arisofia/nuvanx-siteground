# Componente: tipografía

## Combinación oficial única

- **Display, H1, H2 y H3:** Playfair Display.
- **Body, UI, navegación, botones, captions, kickers e índices:** Manrope.

No existen opciones 2 o 3 en runtime. Bodoni Moda y Cormorant Garamond no forman parte del sistema activo.

## Escala

| Clase / elemento | Uso | Tamaño |
|---|---|---:|
| `.nvx-display` / hero title | Hero principal | `clamp(2.8rem, 5vw, 4.2rem)` |
| `.nvx-h1` / `h1` | Título de página | `clamp(2.2rem, 4vw, 3.2rem)` |
| `.nvx-h2` / `h2` | Título de sección | `clamp(1.7rem, 3vw, 2.4rem)` |
| `.nvx-h3` / `h3` | Título de componente | `1.4rem` |
| `.nvx-body` / body | Lectura | `1.0625rem` |
| `.nvx-small` | UI secundaria | `0.875rem` |
| `.nvx-caption` | Caption uppercase | `0.75rem` |

## Contratos

### Encabezados

```css
font-family: var(--nvx-serif);
font-weight: 500;
letter-spacing: -0.02em;
line-height: 1.15;
```

### Body y UI

```css
font-family: var(--nvx-sans);
font-weight: 400;
line-height: 1.6;
color: var(--nvx-text-body);
```

### Caption

```css
font-size: var(--nvx-type-caption);
letter-spacing: var(--nvx-track-caption);
text-transform: uppercase;
```

## Roles compatibles

| Clases persistidas | Rol canónico |
|---|---|
| `.nvx-brand-hero__title` | display |
| `.nvx-heading` | H1 |
| `.nvx-brand-title` | H2 |
| `.nvx-brand-subtitle` | H3 |
| `.nvx-copy`, `.nvx-brand-body` | body |
| `.nvx-lead`, `.nvx-brand-hero__lead` | lead |
| `.nvx-eyebrow`, `.nvx-brand-kicker` | kicker |

Estas clases no definen una fuente alternativa: heredan de los dos tokens canónicos.

## Accesibilidad

- Mantener orden lógico `h1` → `h2` → `h3`.
- No usar tamaño visual para alterar la semántica del encabezado.
- Evitar párrafos largos en mayúsculas.
- Conservar medida de lectura y contraste del sistema.

## Prohibido

- Escribir `font-family: "Playfair Display"` en una página; usar `var(--nvx-serif)`.
- Escribir `font-family: "Manrope"` fuera de tokens/carga de fuentes; usar `var(--nvx-sans)`.
- Reintroducir Bodoni Moda, Cormorant Garamond, Inter o Source Sans.
- Crear escalas particulares en PHP o CSS de página.
