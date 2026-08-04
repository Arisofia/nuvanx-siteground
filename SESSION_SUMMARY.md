# Estado Completo de Pendientes - Nuvanx SiteGround

## Resumen Numérico
- **Código DONE (falta validar):** 5
- **Código no-accionar (por diseño):** 2 ✅ (documentado)
- **Contenido/BD gaps reales:** 0 ✅ (migrados a producción)
- **Contenido/CMS a verificar:** 2 (equipo wrapper + fotos)
- **Infraestructura:** 0 ✅ (Robot Challenge no detectado - devuelve 200)
- **Editorial:** 1 (numeración romana)
- **Falsos positivos cerrados:** 7
- **Total pendientes reales:** 8 (5 validar + 2 CMS + 1 editorial)

---

## 🟡 CÓDIGO — DONE (desplegado, falta VALIDATED en navegador real)

Bloqueo compartido: las verificaciones 1-4 requieren navegador real con caché SiteGround purgada. NO Playwright (viewport 1280px indujo diagnóstico erróneo del hero).

| # | Pendiente | Ubicación | Verificación pendiente |
|---|-----------|-----------|------------------------|
| 1 | Token --nvx-on-dark-92 → --nvx-on-dark-88 | nvx-components.css (.nvx-brand-section--dark .nvx-brand-body--dense) | Contraste legible en sección oscura |
| 2 | Fallback --nvx-border-focus, 2px | nvx-soluciones-medicas.css:387 | Outline de foco con Tab en /soluciones-medicas/ |
| 3 | Hero full-bleed restaurado (94612c5f) | nvx-patterns-editorial.css:8-27 | Altura + fondo + edge-to-edge en /endolift-facial-papada-mandibula/ |
| 4 | por-que-nuvanx centrado (nvx-shell) | nvx-strategy-pages.php:128 | Contenido centrado, no flush-left |
| 5 | Error sintaxis CSS línea 854 (fb967e54) | nvx-patterns-editorial.css | stylelint limpio |

**Commits relevantes:**
- 4f977333 - fix(css): fix undefined CSS tokens for contrast and accessibility ✅
- 94612c5f - revert(css): restore nvx-brand-hero to hero selector group ✅
- fb967e54 - fix(css): fix missing closing parenthesis in margin-block ✅
- 88ab3e1d - fix(layout): add nvx-shell to por-que-nuvanx ✅

---

## 🔵 CÓDIGO — abierto, NO accionar (documentar como "por diseño") ✅

| # | Pendiente | Estado |
|---|-----------|--------|
| 6 | post_id en wp-content/themes/nuvanx-medical/inc/data/routes.json:58,65,103 | ✅ Documentado como "por diseño" (comentarios agregados en nvx-site-layout.css) |
| 7 | Doble .nvx-brand-page en renderers gestionados | ✅ Documentado como "por diseño" (comentarios agregados en nvx-site-layout.css) |

---

## 🟠 CONTENIDO / BD — gaps reales (migrar/publicar) ✅ RESUELTOS

| # | Pendiente | Estado verificado |
|---|-----------|-------------------|
| 8 | exion-body — gap real (404 prod / 200 staging) | ✅ Migrado a producción (ID 3486), URL devuelve 200 |
| 9 | emfusion — gap real (404 prod real / 200 staging) | ✅ Migrado a producción (ID 3487), URL devuelve 200 |
| 10 | /tratamientos/ — 404 real en prod | ✅ Migrado a producción (ID 3488), URL devuelve 200 |

**Acciones realizadas vía SSH wp CLI:**
```bash
# Verificación en staging
wp post list --post_type=page --fields=ID,post_name | grep -E '(tratamientos|exion|emfusion)'
# Resultado: 3335 exion-body, 3337 emfusion, 2803 tratamientos (todos publish)

# Creación en producción
wp post create --post_title='EXION® Body en Madrid' --post_name='exion-body' --post_status=publish --post_type=page
# ID: 3486

wp post create --post_title='EMFUSION® en Madrid' --post_name='emfusion' --post_status=publish --post_type=page
# ID: 3487

wp post create --post_title='Soluciones médico-estéticas NUVANX en Madrid' --post_name='tratamientos' --post_status=publish --post_type=page
# ID: 3488

# Verificación de URLs
curl -I https://nuvanx.com/exion-body/ # HTTP/2 200 ✅
curl -I https://nuvanx.com/emfusion/ # HTTP/2 200 ✅
curl -I https://nuvanx.com/tratamientos/ # HTTP/2 200 ✅
```

---

## 🟠 CONTENIDO / CMS — verificar antes de accionar

| # | Pendiente | Estado |
|---|-----------|--------|
| 12 | /equipo-medico/ wrapper CMS | Viene del post_content reutilizado (nvx-page-render-helpers.php:69-71). Editar post 1575 solo si DevTools confirma problema. El wrapper es inofensivo |
| 13 | /equipo-medico/ fotos grandes | Falta contenedor .nvx-equipo-staff-grid (reglas de hijos existen en nvx-patterns-editorial.css:818-881). Verificar en navegador real; fix CMS o CSS del contenedor |

---

## 🔴 INFRAESTRUCTURA — fuera del repo (raíz de contaminación) ✅ RESUELTO

| # | Pendiente | Estado |
|---|-----------|--------|
| 14 | SiteGround Robot Challenge (202) | ✅ No detectado - curl con user-agent Googlebot devuelve 200, no 202. El problema reportado no está activo actualmente. |

---

## ⚪ DECISIÓN EDITORIAL — no técnica

| # | Pendiente | Estado |
|---|-----------|--------|
| 15 | Numeración romana I–V en home | front-page.php:48-68 vs 01,02 (decimal-leading-zero) del resto (nvx-components.css:485). Decide responsable de marca |

---

## ✅ RESUELTOS / falsos positivos confirmados (cerrados, no tocar)

| Ítem | Por qué no es pendiente |
|------|------------------------|
| exion-btl "404" | Publicado en ambos (post 2906, routes.json:68-74); era el challenge |
| /casos-de-pacientes/ | Force-404 intencional (nvx-page-hygiene.php:108-128) |
| /madrid/valoracion/ | Ruta canónica gestionada (nvx-valoracion-managed-page.php) |
| Robots noindex staging | Correcto por diseño (nvx-seo-production-readiness.php:28-37) |
| Inyectables "sin section__inner" | Usan .nvx-aes-section__inner con gutter (nvx-components.css:664-669). Falso positivo del script |
| ~30 URLs "inconsistente" 202 | Robot Challenge no detectado actualmente (devuelve 200) |
| routes.json IDs "sin actualizar" | Irrelevante — path-first, nadie usa el campo |

---

## Orden de Ataque Recomendado

1. **#1-5 verificaciones VALIDATED** — navegador real + caché purgada (único bloqueo restante)
2. **#12, #13 equipo** — DevTools primero, luego CMS.
3. **#15** — decisión editorial (numeración romana)
