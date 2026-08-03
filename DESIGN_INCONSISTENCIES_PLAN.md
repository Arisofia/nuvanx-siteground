# 🎨 Plan de Corrección de Inconsistencias de Diseño/UX

## 9 INCONSISTENCIAS IDENTIFICADAS

### 1. 🔴 Logo WhatsApp ocupa 25% del espacio en algunas páginas
**Problema**: El logo de WhatsApp parece unirse en páginas que tienen su propio header, ocupando espacio excesivo.

**Investigación requerida**:
- Identificar qué páginas tienen header propio vs header compartido
- Verificar si WhatsApp está integrado en navegación principal o es un elemento adicional
- Revisar CSS específico de páginas con header custom

**Archivos a revisar**:
- `header.php` - header principal
- Archivos de páginas específicas con header custom
- `nvx-content-presentation.php` - CTA presentation (10 matches de whatsapp)

---

### 2. 🔴 Footer demasiado largo/vertical, ocupa demasiado espacio
**Problema**: Footer tiene mucha altura vertical, consumiendo espacio valioso del viewport.

**Análisis actual**:
- **Archivo**: `footer.php` (212 líneas, 3 columnas de details/accordions)
- **CSS**: `nvx-footer.css` usa grid 2fr 4fr 2fr 2fr
- **Estructura**: Logo + 3 columnas de acordeones (Tratamientos, Clínicas, NUVANX) + bottom legal

**Corrección propuesta**:
- Reducir padding vertical: `padding: var(--nvx-space-8) var(--nvx-gutter-inner) 0` → `padding: var(--nvx-space-4) var(--nvx-gutter-inner) 0`
- Optimizar grid para móvil: acordeones collapsed por defecto
- Considerar layout horizontal más compacto en desktop

---

### 3. 🔴 Vista móvil no se desplaza correctamente
**Problema**: Scroll no funciona correctamente en dispositivos móviles.

**Investigación requerida**:
- Verificar CSS `overflow` y `overflow-y` en body y contenedores
- Revisar posición fixed/absolute de elementos que puedan bloquear scroll
- Chequear `touch-action` CSS property
- Investigar JavaScript que pueda interferir con scroll nativo

**Archivos a revisar**:
- `nvx-site-layout.css` - layout general
- `nvx-accessibility-governance.css` - scroll y navigation
- JavaScript de interacción móvil

---

### 4. 🔴 Menú aparece desplegado en móvil y no se puede desplazar
**Problema**: Menú móvil está expandido y bloquea el scroll.

**Investigación requerida**:
- Verificar estado de menú móvil (abierto por defecto vs cerrado)
- Revisar CSS de overlay/ backdrop que pueda bloquear scroll
- Chequear JavaScript de toggle de menú móvil
- Investigar z-index y stacking context

**Archivos a revisar**:
- `nvx-navigation-filters.php` - navegación
- CSS de menú móvil y overlay
- JavaScript de toggle menú

---

### 5. 🔴 Botón del menú de valoración médica sin formato
**Problema**: Botón específico de valoración médica carece de styling consistente.

**Investigación requerida**:
- Identificar qué páginas tienen botón de valoración específico
- Revisar si usa clases de botón estándar o tiene styling custom
- Verificar consistencia con otros botones CTA del sitio

**Archivos a revisar**:
- `nvx-valoracion-managed-page.php` - página de valoración
- `nvx-components.css` - componentes de botones
- Estilos específicos de valoración

---

### 6. 🔴 Todo alineado a la izquierda en la mayoría de páginas
**Problema**: Contenido no está centrado, todo está alineado a la izquierda.

**Investigación requerida**:
- Verificar `text-align` en body y contenedores principales
- Revisar `justify-content` en flex/grid layouts
- Chequear `margin-inline` y `margin-inline-auto`
- Investigar si es intencional (contenido editorial) o error de layout

**Archivos a revisar**:
- `nvx-site-layout.css` - layout general
- `nvx-patterns-editorial.css` - patterns de contenido
- `nvx-base.css` - estilos base

---

### 7. 🔴 Cards tienen diseños diferentes
**Problema**: Las cards no tienen diseño consistente entre páginas.

**Investigación requerida**:
- Identificar todas las implementaciones de cards en el sitio
- Revisar clases CSS de cards existentes
- Verificar si hay cards específicas por página vs sistema de componentes
- Documentar variaciones de diseño (bordes, sombras, padding, etc.)

**Archivos a revisar**:
- `nvx-components.css` - sistema de cards existente
- `nvx-clinics-hub.php` - cards de clínicas
- `nvx-aesthetic-treatment-pages.php` - cards de tratamientos
- Otros archivos con cards específicas

---

### 8. 🔴 No se usan iconos o numerales con diseño en todas las páginas
**Problema**: Sistema de iconos/numerales no está implementado consistentemente.

**Investigación requerida**:
- Verificar si existe sistema de iconos en el tema
- Identificar qué páginas usan iconos vs cuáles no
- Revisar tokens CSS para iconos: `--nvx-icon-xs`, `--nvx-icon-sm`, `--nvx-icon-md`
- Chequear numerales secuenciales existentes

**Archivos a revisar**:
- Tokens CSS existentes para iconos
- Componentes que deberían usar iconos
- Sistema de numeración existente

---

### 9. 🔴 Las imágenes no tienen ninguna guía visual
**Problema**: Imágenes carecen de styling visual consistente (bordes, sombras, overlays, etc.).

**Investigación requerida**:
- Verificar sistema de imágenes existente
- Revisar estilos de imágenes en CSS
- Chequear si hay guía visual: borders, shadows, overlays, filters
- Investigar inconsistencias entre imágenes hero, cards, etc.

**Archivos a revisar**:
- `nvx-components.css` - estilos de imágenes
- `nvx-patterns-editorial.css` - patterns de contenido
- Estilos específicos de imágenes por página

---

## 🎯 PLAN DE ACCIÓN PRIORIZADO

### FASE 1: Layout Crítico (Alta Prioridad)
**Objetivo**: Corregir problemas que afectan funcionalidad básica

1. **#3 Scroll móvil** - Bloquea uso del sitio en móvil
2. **#4 Menú móvil** - Bloquea navegación en móvil
3. **#6 Alineación izquierda** - Afecta UX en desktop

### FASE 2: Componentes Visuales (Media Prioridad)
**Objetivo**: Unificar componentes reutilizables

4. **#5 Botón valoración** - Inconsistencia CTA crítica
5. **#7 Cards diferentes** - Sistema de componentes fragmentado
6. **#1 Logo WhatsApp** - Espacio inconsistente

### FASE 3: Polish de Diseño (Baja Prioridad)
**Objetivo Mejorar estética y coherencia visual

7. **#2 Footer vertical** - Optimización de espacio
8. **#8 Iconos/numerales** - Sistema de design incompleto
9. **#9 Guía visual imágenes** - Mejora estética

---

## 📋 METODOLOGÍA DE CORRECCIÓN

Para cada inconsistencia:

1. **Investigación profunda**
   - Análisis de código actual
   - Identificación de archivos afectados
   - Documentación del estado actual

2. **Propuesta de solución**
   - Cambios CSS específicos
   - Migración a tokens CSS existentes
   - Creación de nuevos componentes si necesario

3. **Implementación**
   - Modificación de archivos CSS/PHP
   - Validación con tests existentes
   - Documentación de cambios

4. **Validación**
   - Screenshots antes/después
   - Testing en desktop y móvil
   - Aprobación del usuario

---

## 🚀 PRÓXIMOS PASOS

**¿Por cuál inconsistencia quieres que empiece?**

**Recomendación**: Empezar por FASE 1 (scroll móvil, menú móvil, alineación) ya que afectan funcionalidad crítica.

O si prefieres, puedo hacer una auditoría más profunda de alguna inconsistencia específica antes de implementar cambios.

---

**Estado**: Plan creado, pendiente ejecución
**Prioridad**: FASE 1 (crítico) → FASE 2 (componentes) → FASE 3 (polish)
