# Inventario canónico de componentes

## Fuentes de verdad

| Área | Archivo |
|---|---|
| Fuentes cargadas | `assets/css/nvx-fonts.css` |
| Paleta, escalas y dimensiones | `assets/css/nvx-tokens.css` |
| Tipografía base y controles | `assets/css/nvx-components.css` |
| Layout y medida editorial | `assets/css/nvx-site-layout.css` |
| Cierre de tipografía, color, iconos y numeración | `inc/nvx-visual-system.php` |
| Patrones editoriales | `assets/css/nvx-patterns-editorial.css` |

Las clases históricas permanecen como aliases cuando todavía aparecen en contenido persistido. No son una segunda fuente de valores.

## Tipografía por rol

| Rol | Familia | Escala |
|---|---|---|
| Display | Playfair Display 500 | `clamp(2.8rem, 5vw, 4.2rem)` |
| H1 | Playfair Display 500 | `clamp(2.2rem, 4vw, 3.2rem)` |
| H2 | Playfair Display 500 | `clamp(1.7rem, 3vw, 2.4rem)` |
| H3 | Playfair Display 500 | `1.4rem` |
| Lead / body | Manrope 400 | tokens lead/body; body 17px, lh 1.6 |
| Small / caption | Manrope | 14px / 12px |
| Kicker / navegación / botón | Manrope | tokens de UI |
| Índice secuencial | Manrope 600 | `--nvx-index-number-*` |
| Métricas | Playfair Display | escala propia del componente |

Aliases como `.nvx-brand-title`, `.nvx-brand-body` o `.nvx-brand-btn` se aceptan únicamente porque resuelven al mismo contrato. Bodoni Moda y Cormorant Garamond están excluidas.

## Iconos

| Presentación | Clase / token |
|---|---|
| Base | `.nvx-icon` |
| 16 / 24 / 32 / 40 px | `.nvx-icon--xs|sm|md|lg` |
| Frame | `--nvx-icon-frame` |
| Trazo | `--nvx-icon-stroke` |
| Color | `currentColor` |

Las clases `.nvx-laser-icon`, `.nvx-aes-icon` y `.nvx-endolift-step__icon` son aliases heredados y reciben la misma presentación. Los SVG externos de beneficios fijados a dorado fueron retirados.

## Numeración

| Rol | Componente |
|---|---|
| Secuencia de proceso | `.nvx-index-number` y aliases `__n` |
| Lista semántica | `<ol>` dentro de superficies editoriales |
| Métrica o cantidad | componente de métrica, separado del índice |

Anti-patrones bloqueados:

- número dentro del H3;
- número dentro de kicker;
- serif grande para unos pasos y sans pequeña para otros equivalentes;
- pérdida de marcadores en listas ordenadas.

## Botones y enlaces

- Base canónica: `.nvx-button`.
- Aliases compatibles: `.nvx-btn`, `.nvx-brand-btn`.
- Variantes: `--primary`, `--secondary`, `--light`, `--secondary-on-dark`.
- Todos heredan Manrope, tamaño, radio, color e interacción desde componentes/tokens.

## Cards, FAQ y media

| Área | Base canónica |
|---|---|
| Cards | `.nvx-card`, con `.nvx-brand-card` como alias persistido |
| FAQ | `.nvx-faq` / `.nvx-brand-faq-accordion` |
| Hero media | `.nvx-media--hero` y wrappers hero existentes |
| Body media | `.nvx-media--body` |
| Retrato médico | `.nvx-media--doctor` |

## Estado de limpieza

- Un solo bloque `:root` canónico.
- Una sola combinación tipográfica web: Playfair Display + Manrope.
- Sin activos SVG de beneficios con color fijo.
- Referencias antiguas de Contacto y beneficios se migran en el límite de render.
- El bloque duplicado de beneficios de Home fue retirado.
- Los scripts `test-visual-system-contract.mjs` y `audit-visual-system.mjs` protegen el contrato.

El detalle de clases sigue disponible en `qa/design-system/component-classes.json`, pero los valores visuales válidos se resuelven exclusivamente desde las fuentes de verdad indicadas arriba.
