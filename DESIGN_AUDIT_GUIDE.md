# 🎨 Guía de Auditoría de Diseño/UX Manual

## Problema Identificado
Headers diferentes, espaciados distintos, márgenes diferentes, diseños distintos entre páginas.

## 📋 MÉTODO DE VALIDACIÓN PROPUESTO

### Opción 1: Auditoría Visual Manual (MÁS RÁPIDA)

#### 1. Abre estas URLs en pestañas del navegador:
- https://staging2.nuvanx.com/ (Home)
- https://staging2.nuvanx.com/contacto/ (Contacto)
- https://staging2.nuvanx.com/blog/ (Blog)
- https://staging2.nuvanx.com/tratamientos/ (Tratamientos)
- https://staging2.nuvanx.com/soluciones-medicas/ (Soluciones)
- https://staging2.nuvanx.com/clinicas/ (Clínicas)
- https://staging2.nuvanx.com/madrid/valoracion/ (Valoración)
- https://staging2.nuvanx.com/equipo-medico/ (Equipo)
- https://staging2.nuvanx.com/nosotros/ (Nosotros)

#### 2. Checklist de Validación:

**HEADER (🔍 Compara entre pestañas)**
- [ ] Altura del header: ¿Es 80px en todas las páginas?
- [ ] Logo: ¿Mismo tamaño y posición?
- [ ] Navegación: ¿Misma estructura y espaciado?
- [ ] CTA button: ¿Mismo estilo y posición?

**ESPACIADO (🔍 Compara secciones)**
- [ ] Padding entre secciones: ¿Usa `--nvx-space-*` tokens?
- [ ] Margen del contenido: ¿Es consistente con `--nvx-margin-*`?
- [ ] Gutter lateral: ¿Usa `--nvx-gutter`?

**TIPOGRAFÍA (🔍 Compara textos)**
- [ ] H1: ¿Usa `--nvx-type-h1` token?
- [ ] H2: ¿Usa `--nvx-type-h2` token?
- [ ] Body text: ¿Usa `--nvx-type-body` token?
- [ ] Line height: ¿Consistente con tokens?

**COLORES (🔍 Compara paleta)**
- [ ] Texto principal: ¿Usa `--nvx-ink`?
- [ ] Fondo: ¿Usa `--nvx-color-paper`?
- [ ] Acentos: ¿Usa `--nvx-accent-gold`?

#### 3. Documenta las inconsistencias encontradas:

```
EJEMPLO DE FORMATO:

## Inconsistencia #1: Header Height
- Páginas afectadas: Home (90px), Contacto (80px), Blog (72px)
- Esperado: 80px (token: --nvx-header-height)
- Prioridad: Alta

## Inconsistencia #2: Section Padding
- Páginas afectadas: Home (64px), Contacto (48px), Blog (32px)
- Esperado: 64px (token: --nvx-pad-section-tight)
- Prioridad: Media
```

### Opción 2: Auditoría con DevTools (MÁS PRECISA)

#### 1. Abre DevTools (F12) en cada página
#### 2. Usa la consola para validar tokens:

```javascript
// Copia y pega en la consola de cada página:

// 1. Verificar tokens CSS disponibles
const root = document.documentElement;
const computed = getComputedStyle(root);
console.log('Tokens disponibles:', {
  nvxHeaderHeight: computed.getPropertyValue('--nvx-header-height'),
  nvxSpace2: computed.getPropertyValue('--nvx-space-2'),
  nvxTypeH1: computed.getPropertyValue('--nvx-type-h1'),
  nvxInk: computed.getPropertyValue('--nvx-ink')
});

// 2. Medir header real
const header = document.querySelector('header, .nvx-header, [class*="header"]');
if (header) {
  const rect = header.getBoundingClientRect();
  console.log('Header real:', Math.round(rect.height) + 'px');
}

// 3. Medir padding de secciones
const sections = document.querySelectorAll('section, [class*="section"]');
sections.forEach((section, i) => {
  if (i < 3) { // Primeras 3 secciones
    const styles = window.getComputedStyle(section);
    console.log(`Section ${i}:`, {
      paddingTop: styles.paddingTop,
      paddingBottom: styles.paddingBottom
    });
  }
});
```

#### 3. Compara los resultados entre páginas

### Opción 3: Auditoría con Screenshots (MÁS VISUAL)

#### 1. Usa herramientas de screenshot:
- **Chrome DevTools**: Right-click → Capture screenshot
- **Extensions**: Full Page Screen Capture
- **Automated**: Playwright (ya configurado en el proyecto)

#### 2. Crea una matriz de comparación:

| Página | Header Height | Section Padding | H1 Size | Background |
|--------|---------------|-----------------|---------|------------|
| Home    | 90px ⚠️      | 64px           | 32px    | #f7f7f5     |
| Contacto| 80px ✅      | 48px ⚠️        | 28px ⚠️| #f1f1ef ⚠️  |
| Blog    | 72px ⚠️      | 32px ⚠️        | 24px ⚠️| #f7f7f5 ✅  |

---

## 🎯 SOLUCIÓN SISTEMÁTICA (Largo Plazo)

### 1. Design Tokens Enforcement
Ya tienes un excelente sistema de tokens en `nvx-tokens.css`. El problema es que no se están usando consistentemente.

**Solución**: 
- Buscar valores hardcoded en CSS (ej: `padding: 32px` → `padding: var(--nvx-space-4)`)
- Crear regla de Stylelint para forzar uso de tokens

### 2. Component Library
Los archivos CSS específicos por página (`nvx-brand-home.css`, `nvx-soluciones-medicas.css`) causan inconsistencia.

**Solución**:
- Consolidar componentes reutilizables en `nvx-components.css`
- Eliminar estilos específicos por página cuando sea posible
- Usar clases de componentes en lugar de overrides específicos

### 3. Visual Regression Testing
Automatizar la detección de cambios visuales.

**Solución**:
- Integrar Playwright visual testing
- Comparar screenshots entre commits
- Alertar cuando el diseño cambie sin aprobación

---

## 🚀 PASOS INMEDIATOS RECOMENDADOS

1. **Haz la auditoría manual** (Opción 1) - 15 minutos
2. **Documenta las inconsistencias** encontradas
3. **Prioriza las 3-5 más críticas** por impacto en UX
4. **Comparte el reporte** con el equipo de diseño/development

---

## ❓ ¿QUÉ NECESITAS QUE HAGA YO?

Puedo ayudarte con:
1. **Ejecutar auditoría automatizada** con Playwright
2. **Analizar archivos CSS** para encontrar hardcoded values
3. **Crear script de migración** a tokens CSS
4. **Configurar visual regression testing** en CI
5. **Revisar código específico** donde veas inconsistencias

¿Cuál prefieres que prioricemos?