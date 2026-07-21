---
name: nuvanx-theme-factory
description: Diseña artefactos comerciales NUVANX (decks, PDFs, stands y piezas de handoff) a partir del sistema Quiet Luxury y variantes de acento controladas, sin modificar el tema WordPress ni su CSS canónico.
---

# NUVANX Theme Factory

## Propósito

Esta skill gobierna la creación de materiales comerciales y de presentación para NUVANX fuera del runtime de WordPress: decks, PDFs, propuestas, stands, fichas, dossiers y prototipos de campaña.

El sistema web sigue siendo monotemático. **Quiet Luxury es la única identidad visual de producción.** Los tokens del runtime web tienen precedencia absoluta sobre cualquier variante local. Las variantes de acento existen únicamente como capas locales de marketing y no pueden convertirse en tokens globales, hojas de estilo del tema, opciones de WordPress o dependencias del frontend.

## Regla de separación obligatoria

Antes de diseñar, clasifica el artefacto:

- **Runtime web**: precedencia absoluta. Usar exclusivamente los tokens activos del tema. No aplicar variantes.
- **Marketing estático**: se permite una variante de acento controlada, declarada estrictamente a nivel local dentro del propio artefacto, sin afectar el scope global.
- **Prototipo o exploración**: debe mostrar una etiqueta visible `CONCEPTO - NO PRODUCCIÓN` y no puede reutilizarse como CSS del tema.

Nunca edites para una pieza comercial:

- `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css`;
- hojas CSS cargadas por WordPress;
- plantillas PHP del tema;
- configuración de plugins;
- identidad del logotipo NUVANX.

## Tipografía oficial

### Familias

- **Editorial / títulos**: Playfair Display.
- **Funcional / cuerpo / datos / botones**: Manrope.
- Fallback serif: Georgia, Times New Roman, serif.
- Fallback sans: Helvetica Neue, Arial, sans-serif.

No utilizar Bodoni Moda, Cormorant Garamond, Inter, Source Sans, Pinyon Script ni fuentes decorativas adicionales.

### Jerarquía recomendada

- Display: Playfair Display 500, tracking -0.02em, interlineado 1.15.
- H1: Playfair Display 500, tracking -0.02em, interlineado 1.15.
- H2/H3: Playfair Display 500, tracking -0.02em.
- Lead y cuerpo: Manrope 400, interlineado 1.6.
- Kicker, caption y botones: Manrope 500-600, mayúsculas moderadas y tracking controlado.

## Paleta base: Quiet Luxury

Usar como fuente de verdad los tokens activos del tema (`nvx-tokens.css`):

| Rol | Valor |
|---|---|
| Luz (`--nvx-light`) | `#f7f7f5` |
| Superficie base (`--nvx-surface-base`) | `#f1f1ef` |
| Tinta (`--nvx-ink`) | `#111111` |
| Carbón (`--nvx-charcoal`) | `#1c1c1e` |
| Superficie suave (`--nvx-surface-soft`) | `#e5e5e3` |
| Borde suave (`--nvx-border-soft`) | `#cecece` |
| Acento neutro (`--nvx-accent-muted`) | `#525252` |

La sensación debe ser clínica, editorial, precisa y discreta. Evitar brillos metálicos falsos, degradados cromados, efectos 3D, sombras duras y ornamentación cosmética.

## Variantes de acento controladas

Las variantes sustituyen solo el rol local de acento. Son variables estrictamente locales y no exportables al sistema global. No cambian tipografía, espaciado, fondos principales, jerarquía ni componentes.

### Acento Zafiro

- Primario: `#27435F`
- Hover/profundo: `#1D334A`
- Tinte: `#E8EEF3`
- Texto sobre acento: `#FCFBF8`

Uso: presentaciones institucionales, innovación, tecnología, datos clínicos y propuestas B2B.

### Acento Sand Gold (Oro Arena)

- Primario (`--nvx-accent-gold`): `#C1A68D`

Uso: Exclusivo para todos los numerales (romanos y arábigos), iconos de interfaz y detalles de acento. No convertirlo en dorado brillante, no usar efectos de metalizado, ni aplicarlo al logotipo.

## Reglas de aplicación

1. Elegir **una sola variante** por artefacto.
2. Limitar el acento al 8-12% de la superficie visual.
3. Mantener fondos, texto y estructura en Quiet Luxury.
4. Usar el acento en botones, reglas, números, etiquetas o un único bloque destacado.
5. Verificar contraste mínimo WCAG AA (4.5:1 para texto normal, 3:1 para títulos y textos grandes) aplicable a todo tipo de texto (funcional, editorial, etiquetas, etc.).
6. No colorear fotografías clínicas ni alterar tonos de piel para adaptarlos a la variante.
7. No inventar versiones del logotipo. Usar el archivo oficial, sin estirar, biselar, recolorear selectivamente ni aplicar efectos.

## Componentes de marketing

### Decks y PDFs

- Portada con una sola idea y espacio negativo amplio.
- Una jerarquía por página: kicker, título, evidencia y acción.
- Máximo dos pesos tipográficos visibles por página.
- Tablas con líneas finas y sin fondos saturados.
- Gráficos sobrios; el acento identifica la serie principal, no decora.

### Stands y señalética

- Priorizar lectura a distancia y recorridos claros.
- Un mensaje principal por plano.
- Manrope para información funcional; Playfair para la promesa editorial.
- No usar claims médicos no validados ni resultados garantizados.

### Material clínico-comercial

- Mantener textura realista, anatomía consistente y resultados naturales.
- Incluir `según valoración médica` cuando corresponda.
- Diferenciar información clínica, precio orientativo y llamada a la acción.

## Flujo de handoff

Cada entrega debe incluir:

1. Variante aplicada y razón de uso.
2. Tokens locales utilizados.
3. Inventario de fuentes y pesos.
4. Formato, dimensiones, sangrado y resolución.
5. Estado de claims y revisión médica.
6. Lista de archivos editables y exportaciones.
7. Declaración explícita: `No modifica el sistema visual web de producción`.

## Control de calidad

Antes de entregar:

- Comparar con el sistema Quiet Luxury.
- Confirmar Playfair Display + Manrope.
- Confirmar una única variante de acento.
- Revisar contraste, márgenes, alineación y legibilidad.
- Renderizar PDF y revisar todas las páginas como imágenes.
- Verificar que no se haya añadido CSS de marketing al tema WordPress.
