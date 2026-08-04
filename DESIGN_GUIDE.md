# 🎨 Guía de Diseño Unificado NUVANX

**Última actualización:** 2026-08-04  
**Estado:** Sistema de diseño con contrato técnico enforceable via CI

## 📋 Principios Fundamentales

### 1. Sistema de Estructura de Página

#### Páginas de Tratamiento (Sistema 13-Puntos Oficial)
Las páginas de tratamiento usan el sistema `nvx-13-point-renderer.php` implementado en `inc/nvx-13-point-renderer.php`:

**13 Elementos del Modelo GEO/SEO:**
1. **Hero Section** - Kicker, H1, Lead, CTA (`nvx_render_matrix_hero`)
2. **Diagnosis** - El valor del diagnóstico médico (`nvx_render_matrix_sections_primary`)
3. **Mechanism** - Mecanismo de acción (`nvx_render_matrix_sections_primary`)
4. **Indications** - Qué tratamos (lista UL) (`nvx_render_matrix_sections_secondary`)
5. **Precautions** - Cuándo no tratar (lista UL) (`nvx_render_matrix_sections_secondary`)
6. **Process** - Proceso en clínica (lista OL) (`nvx_render_matrix_sections_secondary`)
7. **Evolution** - Evolución y seguridad (`nvx_render_matrix_evolution_section`)
8. **Risks** - Riesgos que deben explicarse (`nvx_render_matrix_evolution_section`)
9. **Combinations** - Combinaciones posibles (`nvx_render_matrix_evolution_section`)
10. **FAQs** - Preguntas frecuentes (acordeón) (`nvx_render_matrix_faqs_section`)
11. **Contacto/NAP** - Datos de contacto estandarizados
12. **Footer Estándar** - Pie de página consistente
13. **Meta Info** - Información adicional (horarios, ubicación)

**Función principal**: `nvx_render_13_point_matrix($data)` en `inc/nvx-13-point-renderer.php`

#### Páginas Generales (Contacto, Sedes, Valoración)
Usan `nvx-brand-hero` con estructura estándar:
- Header Global
- Hero con Banner
- Kicker, H1, Lead, CTAs
- Secciones de contenido
- Cards uniformes
- Footer estándar

---

## 🎯 Patrón de Hero Unificado

### Estructura Base (para páginas generales)
```php
<section class="nvx-brand-hero" aria-labelledby="nvx-page-hero-title">
  <div class="nvx-brand-hero__inner">
    <div class="nvx-brand-hero__copy">
      <p class="nvx-brand-kicker">KICKER EN MAYÚSCULAS</p>
      <h1 id="nvx-page-hero-title" class="nvx-brand-hero__title">
        Título Principal de la Página
      </h1>
      <p class="nvx-brand-hero__lead">
        Descripción introductoria del contenido
      </p>
      <div class="nvx-brand-actions">
        <a class="nvx-brand-btn nvx-brand-btn--primary" href="#">
          CTA Primario
        </a>
        <a class="nvx-brand-btn nvx-brand-btn--secondary" href="#">
          CTA Secundario
        </a>
      </div>
      <p class="nvx-brand-meta">Información adicional relevante</p>
    </div>
  </div>
</section>
```

### Clases CSS Obligatorias
- `nvx-brand-hero` - Container principal del hero
- `nvx-brand-hero__inner` - Wrapper interno
- `nvx-brand-hero__copy` - Contenedor de texto
- `nvx-brand-kicker` - Kicker (usar tokens CSS)
- `nvx-brand-hero__title` - H1 (usar tokens CSS)
- `nvx-brand-hero__lead` - Lead paragraph (usar tokens CSS)
- `nvx-brand-actions` - Contenedor de botones
- `nvx-brand-btn--primary` - Botón primario
- `nvx-brand-btn--secondary` - Botón secundario
- `nvx-brand-meta` - Meta información

---

## 🎨 Sistema de Colores Unificado

### Tokens CSS Obligatorios
- `--nvx-ink` - Texto principal (usar siempre, no colores hardcoded)
- `--nvx-light` - Texto sobre fondos oscuros
- `--nvx-accent-muted` - Acentos secundarios
- `--nvx-color-paper` - Fondo de páginas
- `--nvx-surface-base` - Superficies de cards
- `--nvx-color-line` - Bordes y separadores

### Reglas
- **NUNCA** usar colores hexadecimales hardcoded (ej: `#f7f7f5`)
- **SIEMPRE** usar tokens CSS del sistema
- **Consistencia**: Mismo color para mismo tipo de elemento en todas las páginas

---

## 📝 Sistema de Tipografía Unificado

### Tokens CSS Obligatorios
- `--nvx-type-h1` - H1 principal
- `--nvx-type-h2` - H2 secciones
- `--nvx-type-h3` - H3 subsections
- `--nvx-type-body` - Texto del cuerpo
- `--nvx-type-lead` - Lead paragraphs
- `--nvx-type-kicker` - Kicker/títulos superiores
- `--nvx-serif` - Fuente para headings
- `--nvx-sans` - Fuente para cuerpo

### Reglas
- **NUNCA** usar tamaños hardcoded (ej: `font-size: 32px`)
- **SIEMPRE** usar tokens CSS del sistema
- **Consistencia**: Mismo estilo para mismo nivel de heading

---

## 🖼️ Sistema de Iconos Unificado

### Dimensiones Estándar
```css
svg, .nvx-icon {
  width: var(--nvx-icon-sm);
  height: var(--nvx-icon-sm);
  display: inline-block;
  vertical-align: middle;
}
```

### Tokens CSS para Iconos
- `--nvx-icon-xs` - Iconos muy pequeños (12px)
- `--nvx-icon-sm` - Iconos pequeños (16px) - DEFAULT
- `--nvx-icon-md` - Iconos medianos (24px)
- `--nvx-icon-lg` - Iconos grandes (32px)

### Reglas
- **SIEMPRE** usar tokens CSS para dimensiones
- **NUNCA** usar width/height hardcoded
- **Consistencia**: Mismo tamaño para iconos de mismo tipo

---

## 📦 Sistema de Cards Unificado

### Clases CSS Obligatorias
- `nvx-brand-card` - Container de card
- `nvx-brand-card__title` - Título de card
- `nvx-brand-card__kicker` - Kicker de card
- `nvx-brand-card__body` - Cuerpo de card
- `nvx-brand-card__number` - Número secuencial (para cards numeradas)

### Reglas
- **Consistencia**: Mismo diseño de bordes, sombras, padding
- **Tokens**: Usar `--nvx-radius-image`, `--nvx-space-*` para espaciado
- **Imágenes**: Usar `nvx-media` con tokens CSS

---

## 📐 Sistema de Alineación Unificado

### Reglas de Layout
- **Contenido principal**: Usar `max-width: var(--nvx-measure)` para limitar ancho
- **Alineación**: `text-align: left` por defecto, `text-align: center` solo cuando intencional
- **Gutters**: Usar `--nvx-gutter` para márgenes laterales
- **Padding**: Usar `--nvx-space-*` tokens para espaciado vertical

### Grid System
- **12-column grid**: Usar `nvx-brand-grid` con spans estándar
- **Responsive**: Stack en móvil, grid en desktop
- **Consistencia**: Mismo grid system en todas las páginas

---

## 🔧 Implementación por Template

### Templates Existentes
1. **page-sede.php** - Usa `nvx-brand-hero` con banner ✅
2. **page-contacto.php** - Usa `nvx-brand-hero` con banner ✅
3. **page-soluciones-medicas.php** - Usa `nvx-brand-hero` con banner ✅
4. **page-landing-valoracion.php** - Usa `nvx-brand-hero` con banner ✅

### Páginas de Tratamiento
- Usan sistema `nvx-13-point-renderer.php` automáticamente
- Catálogos JSON en `inc/data/` definen contenido
- No modifican templates directamente

---

## 🚫 Errores Comunes a Evitar

### ❌ NO HACER
- Usar colores hexadecimales hardcoded (detectado por CI)
- Usar tamaños de fuente hardcoded (detectado por CI)
- Usar dimensiones de iconos hardcoded
- Crear estilos específicos por página cuando existe componente global
- Ignorar tokens CSS del sistema de diseño
- Usar diferentes patrones de hero para páginas similares
- Usar estilos inline con propiedades de layout (detectado por CI)

### ✅ SI HACER
- Usar siempre tokens CSS del sistema
- Reutilizar componentes existentes antes de crear nuevos
- Mantener consistencia visual entre páginas similares
- Validar que las 13 secciones estén presentes en tratamientos
- Usar clases de componentes globales
- Seguir el patrón de `nvx-brand-hero` para páginas generales
- Ejecutar lints locales antes de commit
- Respetar el contrato de layout (header/footer wrappers)

---

## 🔧 Contracto Técnico Enforceable

### Linting Automático
El sistema de diseño está protegido por scripts de linting que se ejecutan en CI:

```bash
# Lint CSS para colores hardcoded
node scripts/lint/no-hardcoded-colors.mjs

# Lint CSS para tamaños de fuente hardcoded  
node scripts/lint/no-hardcoded-fontsize.mjs

# Lint PHP para estilos inline peligrosos
node scripts/lint/no-inline-layout-styles.mjs
```

### Contrato de Layout
- **header.php**: Siempre abre `<main id="nvx-main" class="nvx-main" role="main" tabindex="-1">` y `<div class="nvx-brand-page">`
- **footer.php**: Siempre cierra `</div><!-- .nvx-brand-page -->` y `</main>`
- **Templates**: Confían en el wrapper global del header, no duplican wrappers
- Excepciones permitidas con marcadores: `/* nvx-allow-font-px */` y `// nvx-allow-inline-style`

### Integración CI
Los lints se ejecutan en:
- `ci-quality.yml` - Quality checks en PRs y pushes
- `deploy-staging2.yml` - Antes de despliegue a staging2
- `deploy.yml` - Antes de despliegue a producción

Esto asegura que el sistema de diseño se cumpla automáticamente antes de cualquier despliegue.

---

## 📋 Checklist de Validación

Para cada página nueva o modificada:

- [ ] Usa `nvx-brand-hero` con banner (para páginas generales)
- [ ] Páginas de tratamiento usan sistema 13-puntos
- [ ] Usa tokens CSS para colores
- [ ] Usa tokens CSS para tipografía
- [ ] Usa tokens CSS para iconos
- [ ] Usa sistema de cards unificado
- [ ] Tiene sección de FAQ/preguntas frecuentes (tratamientos)
- [ ] Tiene datos de contacto/NAP
- [ ] Usa grid system consistente
- [ ] Alineación del contenido es consistente
- [ ] No tiene estilos hardcoded
- [ ] No tiene valores de hardcoded en CSS
- [ ] Footer es consistente con resto del sitio

---

## 🎯 Próximos Pasos de Implementación

1. **Revisar páginas específicas** mencionadas por el usuario:
   - https://staging2.nuvanx.com/well-aging-48-cambios-hormonales-piel/
   - https://staging2.nuvanx.com/tratamientos/
2. **Corregir fotografías del equipo** - Diferentes tamaños, diseño no estándar
3. **Revisar headers** - Diseños diferentes con fotos pequeñas o enormes
4. **Verificar fondos** - Fondos que no se ven correctamente
5. **Validar despliegue** - Asegurar que cambios lleguen de GitHub a SiteGround

---

## 📚 Referencias

- `nvx-13-point-renderer.php` - Sistema oficial de 13 puntos para tratamientos
- `nvx-tokens.css` - Sistema de tokens CSS
- `nvx-components.css` - Componentes globales
- `nvx-header.css` - Header y navegación
- `nvx-footer.css` - Footer estandar
- `nvx-base.css` - Estilos base

---

Esta guía es la referencia definitiva para el diseño de NUVANX. Cualquier modificación debe seguir estos principios para mantener consistencia en todo el sitio.
