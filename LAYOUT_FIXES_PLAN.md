# Plan de Correcciones de Layout - Fuera del Alcance del PR 368

## Resumen del Estado Actual

### ✅ Completado (Commit 88ab3e1d)
- por-que-nuvanx: Agregado nvx-shell para gutter/centrado
- Causa: Falta nvx-shell en <article> vs inversion que sí lo tenía
- Resultado: margin-inline:auto + padding-inline aplicados

### 📋 Items Pendientes

## 1. Verificación DevTools - Home
**Estado:** Sistema propio nvx-home-* con gutters correctos
- front-page.php usa nvx-home-*__inner (filosofía, team, seo)
- nvx-home-v3.css:79-83 confirma margin: 0 auto + padding-inline
- **Conclusión:** Home está bien estructuralmente - no requiere fix
- **Acción:** Verificar visualmente en DevTools que el layout se ve correcto

## 2. Verificación DevTools - Nosotros
**Estado:** CMS crudo - post 1656
- Depende del post_content en la BD
- Script marcó "no section__inner" pero puede ser falso positivo
- **Acción:** Verificar en DevTools si el CMS trae .nvx-brand-section__inner
- **Fix requerido:** Si falta wrapper, editar post_content del post 1656

## 3. Inconsistencia Editorial - Home Numeración
**Estado:** Mezcla de romanos I-V y decimales 01-05
- front-page.php:48-68: Romanos I-V (aria-hidden, decorativos)
- front-page.php:81-101: Decimales 01-05
- **Pregunta:** ¿I-V es intencional o debe ser 01-05 para consistencia?
- **Acción:** Decisión editorial requerida antes de fix

## 4. CMS - Equipo (post 1575)
**Estado:** Falta nvx-equipo-staff-grid en post_content
- Problema: Post 1575 tiene `<div class="nvx-brand-page nvx-brand-page--equipo">` en CMS
- Helper nvx_page_render_brand_wrapper() reutiliza este wrapper
- CSS nvx-equipo-staff-grid ya está correcto en commit b5bf6ea6
- **Acción:** Editar post_content del post 1575 en BD para:
  1. Quitar nvx-brand-page del <div> exterior
  2. Añadir nvx-equipo-staff-grid como contenedor de las tarjetas

## 5. CMS - Legales (Baja Prioridad)
**Estado:** CMS crudo sin wrapper
- politica-privacidad, aviso-legal, politica-de-cookies, mas-informacion-sobre-las-cookies
- **Prioridad:** Baja (legales aceptables sin gutter)
- **Acción:** Solo si se considera necesario editar post_content

## Estrategia de Implementación

### Opción A: PR Temático Único "Layout & Consistency Visual"
```
Branch: feature/layout-consistency
Commits:
1. Fix equipo CMS (post 1575)
2. Fix nosotros CMS (si requiere)
3. Estandarizar numeración home (si decide editorial)
4. Clean legales (opcional)
```

### Opción B: PRs Separados por Sistema
```
Branch 1: fix/equipo-cms
- Solo post 1575

Branch 2: fix/nosotros-cms
- Solo post 1656

Branch 3: style/home-numbering
- Solo front-page.php (decisión editorial)
```

### Opción C: Acción Manual Directa (Sin PR)
- Edición directa de post_content en staging2/producción vía WP Admin
- Sin cambios de código
- Para: equipo, nosotros, legales

## Recomendación

**Opción C para CMS (equipo, nosotros, legales):**
- Edición manual en WP Admin es más rápido y seguro
- No requiere deploy de código
- Reversible fácilmente

**Opción A para consistencia visual (si aplica):**
- Si se decide estandarizar numeración home, usar PR temático
- Agrupa todos los fixes de layout en un solo PR

## Próximos Pasos

1. **Verificar DevTools:**
   - Home layout (confirmar nvx-home-* funciona)
   - Nosotros layout (confirmar si necesita wrapper)

2. **Decisión editorial:**
   - ¿I-V vs 01-05 en home?

3. **Acción manual CMS:**
   - Post 1575 (equipo)
   - Post 1656 (nosotros - si requiere)
   - Legales (si se decide)

4. **PR temático (opcional):**
   - Solo si se requiere cambio de código (numeración home)
