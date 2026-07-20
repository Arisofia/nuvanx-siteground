# Inventario canónico de componentes

## Fuentes de verdad

| Área | Archivo |
|---|---|
| Paleta, escalas y dimensiones | `assets/css/nvx-tokens.css` |
| Tipografía base y controles | `assets/css/nvx-components.css` |
| Layout y medida editorial | `assets/css/nvx-site-layout.css` |
| Cierre de color, iconos y numeración | `inc/nvx-visual-system.php` |
| Patrones editoriales | `assets/css/nvx-patterns-editorial.css` |

Las clases históricas permanecen como aliases cuando todavía aparecen en contenido persistido. No son una segunda fuente de valores.

## Tipografía por rol

| Rol | Familia | Tokens |
|---|---|---|
| Display / H1 / H2 / H3 | Bodoni Moda | `--nvx-type-display`, `--nvx-type-h1`, `--nvx-type-h2`, `--nvx-type-h3` |
| Lead / body / caption | Manrope | `--nvx-type-lead`, `--nvx-type-body`, `--nvx-type-caption` |
| Kicker / navegación / botón | Manrope | `--nvx-type-kicker`, `--nvx-type-nav`, `--nvx-type-button` |
| Índice secuencial | Manrope | `--nvx-index-number-*` |
| Métricas | Bodoni Moda | escala del componente de métrica |

Aliases como `.nvx-brand-title`, `.nvx-brand-body` o `.nvx-brand-btn` se aceptan únicamente porque el CSS global los vincula al mismo contrato.

## Iconos

| Presentación | Clase / token |
|---|---|
| Base | `.nvx-icon` |
| 16 / 24 / 32 / 40 px | `.nvx-icon--xs|sm|md|lg` |
| Frame | `--nvx-icon-frame` |
| Trazo | `--nvx-icon-stroke` |
| Color | `currentColor` |

Las clases `.nvx-laser-icon`, `.nvx-aes-icon` y `.nvx-endolift-step__icon` son aliases de forma heredados y reciben la misma presentación. Los SVG externos de beneficios fijados a dorado fueron retirados.

## Numeración

| Rol | Componente |
|---|---|
| Secuencia de proceso | `.nvx-index-number` y aliases `__n` |
| Lista semántica | `<ol>` dentro de superficies editoriales |
| Métrica o cantidad | componente de métrica, separado del índice |

Anti-patrones bloqueados:

- número dentro del H3;
- número dentro de kicker;
- serif grande para pasos y sans pequeña para otros pasos equivalentes;
- pérdida de marcadores en listas ordenadas.

## Botones y enlaces

- Base canónica: `.nvx-button`.
- Aliases compatibles: `.nvx-btn`, `.nvx-brand-btn`.
- Variantes: `--primary`, `--secondary`, `--light`, `--secondary-on-dark`.
- Todos heredan fuente, tamaño, radio, color e interacción desde componentes/tokens.

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
- Sin activos SVG de beneficios con color fijo.
- Referencias antiguas de Contacto y beneficios se migran en el límite de render.
- La documentación de Metal Pulido fue sustituida por la paleta cálida vigente.
- Los scripts `test-visual-system-contract.mjs` y `audit-visual-system.mjs` protegen el contrato.

El detalle de clases sigue disponible en `qa/design-system/component-classes.json`, pero los valores visuales válidos se resuelven exclusivamente desde las fuentes de verdad indicadas arriba.
