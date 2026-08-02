# Global Document Governance Checklist: Editorial & Visual QA

Este documento define el estándar de gobernanza manual para la interfaz y diseño editorial de NUVANX Medical. Estas comprobaciones deben realizarse en el entorno Staging2 antes de cualquier pase a producción, y complementan a las aserciones automatizadas de Playwright (estructura DOM, iconos, wrappers).

## 1. Spacing y Layout (Márgenes y Whitespace)

- [ ] **Márgenes del Hero:** Verificar que el bloque `.nvx-brand-hero` mantiene un espaciado coherente y no presenta saltos excesivos (whitespace) respecto a la barra de navegación (header) o al primer bloque de contenido subsecuente.
- [ ] **Alineación del Contenedor:** Comprobar que el contenido principal respeta los límites de `.nvx-brand-section__inner` y nunca aparece "pegado" a los bordes de la pantalla, tanto en escritorio como en dispositivos móviles.
- [ ] **Full-bleed coherente:** Las secciones que deban ocupar todo el ancho (fondos de color, vídeos) extienden su color/fondo de lado a lado sin romper el layout.

## 2. Iconos (Uso Semántico y Sprites)

- [ ] **Consistencia de SVG:** Validar que los íconos (ubicación, teléfono, reloj, etc.) utilizan el sistema de sprites global y se invocan correctamente mediante `<svg><use href="#icon-nombre"></use></svg>`.
- [ ] **Ausencia de Iconos Rotos:** Asegurar que ningún ícono se carga roto o en blanco, y que no se están utilizando incrustaciones de SVG de terceros con estilos ad-hoc que rompan los tokens de color.

## 3. Tipografía, Jerarquía y Colores (Design Tokens)

- [ ] **Jerarquía Tipográfica (H1-H6):** 
  - Los encabezados de primer nivel (`H1`) utilizan invariablemente *Playfair Display* con los pesos (weight) correctos.
  - Los textos de párrafo (`p`) y UI utilizan *Manrope* (legibilidad técnica).
- [ ] **Tokens de Color:** 
  - Comprobar que los fondos oscuros (ej: `.nvx-theme-dark`) contrastan adecuadamente con textos blancos/claros.
  - No hay textos "gris claro" sobre fondos blancos que violen el contraste mínimo (WCAG).
  - Los CTAs (`.nvx-brand-btn`) usan los colores primarios y secundarios de marca definidos en `nvx-tokens.css` y reaccionan a los estados `:hover` y `:focus`.

## 4. Legibilidad y Responsive

- [ ] **Altura de línea (Line-height):** Verificar que los párrafos extensos (ej. descripciones médicas) tienen una altura de línea holgada (1.5 - 1.6) que facilita la lectura.
- [ ] **Flujo Móvil:**
  - Los bloques de texto no desbordan el _viewport_ horizontal (no hay scroll horizontal).
  - El *Hero* en móviles acomoda su texto correctamente antes de las imágenes para maximizar la conversión inicial sobre el pliegue (above the fold).

---

> [!NOTE]  
> Cualquier fallo detectado durante este QA manual debe escalarse como *bug* de UI, asegurando que las correcciones se realicen actualizando las clases base de `nvx-components.css` o `nvx-patterns-editorial.css`, y **NUNCA** introduciendo estilos *inline* (`style="..."`) en los módulos PHP.
