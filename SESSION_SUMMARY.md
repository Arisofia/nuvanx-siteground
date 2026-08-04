# Estado Completo de Pendientes - Nuvanx SiteGround

## Resumen Numérico
- **Código DONE (falta validar):** 5
- **Código no-accionar (por diseño):** 2
- **Contenido/BD gaps reales:** 3 (exion-body, emfusion, tratamientos-probable)
- **Contenido/CMS a verificar:** 2 (equipo wrapper + fotos)
- **Infraestructura:** 1 (Robot Challenge — raíz)
- **Editorial:** 1 (numeración romana)
- **Falsos positivos cerrados:** 7
- **Total pendientes reales:** 14

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

## 🔵 CÓDIGO — abierto, NO accionar (documentar como "por diseño")

| # | Pendiente | Estado |
|---|-----------|--------|
| 6 | post_id en wp-content/themes/nuvanx-medical/inc/data/routes.json:58,65,103 | Cosmético — ningún consumidor lo usa (resolución path-first, nvx-page-hygiene.php:341-353). No accionar |
| 7 | Doble .nvx-brand-page en renderers gestionados | Inofensivo — CSS lo soporta (nvx-site-layout.css:41). El refactor b5bf6ea6 que intentó "arreglarlo" fue revertido por regresión. Documentar como "por diseño" |

---

## 🟠 CONTENIDO / BD — gaps reales (migrar/publicar)

| # | Pendiente | Estado verificado |
|---|-----------|-------------------|
| 8 | exion-body — gap real (404 prod / 200 staging) | Público por diseño (nvx-btl-clinical-governance.php:19-26, sin quarantine). Migrar. Seguro ✅ |
| 9 | emfusion — gap real (404 prod real / 200 staging) | Público por diseño. Migrar. Seguro ✅ |
| 10 | /tratamientos/ — 404 real en prod | Requiere post publicado, sin fallback (nvx-treatments-catalog.php:197-206). Verificar con `wp post list --name=tratamientos` en ambos entornos — probablemente gap-BD como exion-body |

---

## 🟠 CONTENIDO / CMS — verificar antes de accionar

| # | Pendiente | Estado |
|---|-----------|--------|
| 12 | /equipo-medico/ wrapper CMS | Viene del post_content reutilizado (nvx-page-render-helpers.php:69-71). Editar post 1575 solo si DevTools confirma problema. El wrapper es inofensivo |
| 13 | /equipo-medico/ fotos grandes | Falta contenedor .nvx-equipo-staff-grid (reglas de hijos existen en nvx-patterns-editorial.css:818-881). Verificar en navegador real; fix CMS o CSS del contenedor |

---

## 🔴 INFRAESTRUCTURA — fuera del repo (raíz de contaminación)

| # | Pendiente | Estado |
|---|-----------|--------|
| 14 | SiteGround Robot Challenge (202) | NO es del theme (sin status_header(202) en ningún .php). Intercepta crawlers en ~30 URLs, afecta Googlebot. Config panel SiteGround WAF/anti-bot. Es la causa raíz que enmascara los "404" de auditorías |

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
| ~30 URLs "inconsistente" 202 | Robot Challenge, no gaps |
| routes.json IDs "sin actualizar" | Irrelevante — path-first, nadie usa el campo |

---

## Orden de Ataque Recomendado

1. **#14 Robot Challenge** — raíz; desbloquea toda auditoría fiable y Googlebot.
2. **#10 wp post list tratamientos** — distingue gap-BD de config antes de accionar.
3. **#8, #9 migrar exion-body/emfusion** — gaps confirmados, seguros.
4. **#1-5 verificaciones VALIDATED** — navegador real + caché purgada.
5. **#12, #13 equipo** — DevTools primero, luego CMS.
6. **#11, #15** — contenido/editorial, responsable de marca.
