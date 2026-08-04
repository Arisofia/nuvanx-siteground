# Guía de Acciones Externas Requeridas - Nuvanx SiteGround

## Contexto
Las siguientes correcciones requieren acceso a sistemas externos (navegador real, SiteGround panel, WordPress admin) que no están disponibles desde el entorno de desarrollo local.

---

## 🔴 PRIORIDAD ALTA - SiteGround Panel

### #14 Robot Challenge (202) - CAUSA RAÍZ
**Ubicación:** SiteGround WAF/Anti-bot panel
**Impacto:** Intercepta crawlers en ~30 URLs, afecta Googlebot y enmascara "404" reales

**Acción requerida:**
1. Acceder al panel de SiteGround
2. Navegar a Security > WAF/Anti-bot
3. Revisar configuración de "Robot Challenge"
4. Ajustar para no interceptar Googlebot (user-agent: Googlebot/2.1)
5. O agregar whitelist de IPs de Googlebot si está disponible

**Verificación:**
- Ejecutar `curl -I https://nuvanx.es/` con user-agent de Googlebot
- Confirmar que devuelve 200 en lugar de 202

**Notas:**
- NO es del theme (sin `status_header(202)` en ningún .php)
- Esta es la causa raíz que desbloquea toda auditoría fiable

---

## 🟠 PRIORIDAD MEDIA - WordPress Admin

### #8 exion-body - Migrar post a producción
**Estado actual:** 404 en prod / 200 en staging
**Causa:** post_id: 0 en routes.json (línea 90)
**Seguridad:** ✅ Público por diseño (nvx-btl-clinical-governance.php:19-26, sin quarantine)

**Acción requerida:**
1. Acceder a WordPress admin en producción
2. Buscar post "exion-body" (o crear si no existe)
3. Publicar el post
4. Actualizar routes.json línea 90 con el post_id correcto
5. Deploy a producción

**Comando wp CLI (ejecutar en servidor):**
```bash
wp post list --name=exion-body --field=ID
# Si retorna ID, actualizar routes.json línea 90: "post_id": <ID>
```

---

### #9 emfusion - Migrar post a producción
**Estado actual:** 404 en prod / 200 en staging
**Causa:** post_id: 0 en routes.json (línea 96)
**Seguridad:** ✅ Público por diseño

**Acción requerida:**
1. Acceder a WordPress admin en producción
2. Buscar post "emfusion" (o crear si no existe)
3. Publicar el post
4. Actualizar routes.json línea 96 con el post_id correcto
5. Deploy a producción

**Comando wp CLI (ejecutar en servidor):**
```bash
wp post list --name=emfusion --field=ID
# Si retorna ID, actualizar routes.json línea 96: "post_id": <ID>
```

---

### #10 /tratamientos/ - Verificar post status
**Estado actual:** 404 real en prod
**Causa probable:** Gap de BD (similar a exion-body/emfusion)
**Ubicación código:** nvx-treatments-catalog.php:197-206 (sin fallback)

**Acción requerida:**
1. Ejecutar en servidor producción:
```bash
wp post list --name=tratamientos --field=ID
```
2. Si retorna 0 o vacío: crear y publicar post "tratamientos"
3. Si retorna ID: verificar que esté publicado (status: publish)
4. Si está publicado pero sigue 404: revisar nvx-treatments-catalog.php

**Nota:** Requiere wp CLI en servidor (no disponible en local)

---

## 🟡 PRIORIDAD MEDIA - Navegador Real

### #1-5 Verificaciones VALIDATED (código ya deployado)
**Requisito:** Navegador real con caché SiteGround purgada
**NO usar Playwright** (viewport 1280px indujo diagnóstico erróneo del hero)

**Acciones:**

**#1 Token --nvx-on-dark-88 (contraste)**
- Navegar a sección oscura (.nvx-brand-section--dark)
- Verificar .nvx-brand-body--dense / .nvx-copy--dense
- Confirmar contraste aceptable de texto denso sobre fondo oscuro
- Validar que --nvx-on-dark-88 es legible sobre --nvx-surface-dark

**#2 Fallback --nvx-border-focus (accesibilidad)**
- Navegar a /soluciones-medicas/
- Usar Tab/Shift+Tab para navegar
- Confirmar outline de foco visible (2px, contrastado)
- Validar WCAG 2.4.7 (focus-visible)

**#3 Hero full-bleed restaurado**
- Navegar a /endolift-facial-papada-mandibula/
- Confirmar hero toca ambos bordes horizontales (full-bleed real)
- Verificar altura ~520-680px (min-height restaurado)
- Verificar fondo oscuro (background: var(--nvx-ink))

**#4 por-que-nuvanx centrado**
- Navegar a /por-que-nuvanx/
- Confirmar <article> centrado con gutter, no pegado a izquierda
- Verificar margin-left ≈ margin-right (centrado por nvx-shell)

**#5 Error sintaxis CSS**
- Ejecutar stylelint en nvx-patterns-editorial.css
- Confirmar que línea 854 está limpia (sin errores)

**Purgar caché SiteGround antes de verificar:**
1. Acceder a SiteGround panel
2. Navegar a Speed > Caching
3. Click en "Purge Cache"

---

### #12 /equipo-medico/ wrapper CMS
**Estado:** Wrapper inofensivo, verificar si es problema real
**Ubicación:** post 1575 post_content reutilizado (nvx-page-render-helpers.php:69-71)

**Acción requerida:**
1. Abrir /equipo-medico/ en navegador real
2. Abrir DevTools > Elements
3. Inspeccionar si el wrapper causa problema visual
4. Si NO hay problema visual: no accionar (es inofensivo)
5. SI hay problema: editar post 1575 en WordPress admin

---

### #13 /equipo-medico/ fotos grandes
**Estado:** Falta contenedor .nvx-equipo-staff-grid
**Ubicación CSS:** nvx-patterns-editorial.css:818-881 (reglas de hijos existen)

**Acción requerida:**
1. Abrir /equipo-medico/ en navegador real
2. Abrir DevTools > Elements
3. Inspeccionar si fotos están sin contenedor correcto
4. Si falta .nvx-equipo-staff-grid:
   - Opción A: Editar post 1575 en WordPress admin para agregar contenedor
   - Opción B: Modificar CSS para agregar reglas sin contenedor

---

## 🔵 PRIORIDAD BAJA - Documentación

### #6 post_id en routes.json (líneas 58, 65, 103)
**Estado:** Cosmético - ningún consumidor usa este campo
**Resolución:** Path-first (nvx-page-hygiene.php:341-353)

**Acción:** NO accionar - documentar como "por diseño"

**Explicación técnica:**
- El theme usa resolución path-first (por ruta, no por post_id)
- El campo post_id es legacy/unused
- Actualizar IDs no tiene impacto funcional

---

### #7 Doble .nvx-brand-page en renderers gestionados
**Estado:** Inofensivo - CSS lo soporta
**Ubicación:** nvx-site-layout.css:41

**Acción:** NO accionar - documentar como "por diseño"

**Explicación técnica:**
- CSS soporta nesting de .nvx-brand-page
- El refactor b5bf6ea6 que intentó "arreglarlo" fue revertido por regresión
- Es comportamiento esperado del sistema

---

### #15 Numeración romana I-V en home
**Estado:** Decisión editorial (no técnica)
**Ubicación:** front-page.php:48-68 vs 01,02 (decimal-leading-zero) del resto

**Acción:** Decidir con responsable de marca
- Opción A: Mantener romana I-V (actual)
- Opción B: Cambiar a decimal-leading-zero 01-05 (consistente con resto)

**Referencia:** nvx-components.css:485 (decimal-leading-zero usado en resto del sitio)

---

## Orden de Ejecución Recomendado

1. **#14 Robot Challenge** (SiteGround panel) - desbloquea auditoría fiable
2. **#10 /tratamientos/** (wp CLI en servidor) - distingue gap-BD de config
3. **#8, #9 exion-body/emfusion** (WordPress admin) - gaps confirmados, seguros
4. **#1-5 Verificaciones VALIDATED** (navegador real + caché purgada)
5. **#12, #13 /equipo-medico/** (navegador real + DevTools)
6. **#6, #7, #15** - documentación/decisión editorial

---

## Resumen de Capacidades Locales vs Externas

**Disponible desde entorno local:**
- ✅ Leer/modificar código PHP, CSS, JSON
- ✅ Git commits
- ✅ Deploy a staging2
- ❌ wp CLI (no instalado localmente)
- ❌ Navegador real (requiere interacción manual)
- ❌ SiteGround panel (requiere acceso web)
- ❌ WordPress admin (requiere acceso web)

**Requiere acceso a servidor/panel:**
- wp CLI para verificar post status
- SiteGround panel para Robot Challenge
- WordPress admin para migrar posts
- Navegador real para validaciones visuales
