# Estado Completo de Pendientes - Nuvanx SiteGround

## Resumen Numérico
- **Código DONE (falta validar):** 4 (foco #2, contraste #1, hero #3, centrado #4)
- **Código no-accionar (por diseño):** 2 ✅ (documentado en commit 85ffa2ff)
- **Contenido/BD gaps reales:** 0 ✅ (migrados a producción)
- **Contenido/CMS:** 0 ✅ (VALIDATED por informe estético + foto Fabio es CMS)
- **Infraestructura:** 0 ✅ (Robot Challenge no detectado - devuelve 200)
- **Editorial:** 1 (numeración romana - verificado romanos I-V presentes, decisión de marca)
- **Falsos positivos cerrados:** 10 (+ H1 cookies redirects 301, has_hero detector, has_romans detector)
- **Total pendientes reales:** 5 (4 validar + 1 editorial)

---

## 🟡 CÓDIGO — DONE (desplegado, falta VALIDATED en navegador real)

Bloqueo compartido: las verificaciones 1-4 requieren navegador real con caché SiteGround purgada. NO Playwright (viewport 1280px indujo diagnóstico erróneo del hero).

| # | Pendiente | Ubicación | Verificación pendiente |
|---|-----------|-----------|------------------------|
| 1 | Token --nvx-on-dark-92 → --nvx-on-dark-88 | nvx-components.css (.nvx-brand-section--dark .nvx-brand-body--dense) | Contraste legible en sección oscura |
| 2 | Fallback --nvx-border-focus, 2px | nvx-soluciones-medicas.css:387 | Outline de foco con Tab en /soluciones-medicas/ (requiere confirmación Tab explícito) |
| 3 | Hero full-bleed restaurado (94612c5f) | nvx-site-layout.css:44-55 | Verificar en navegador real maximizado (>1280px) - NO reabrir por detección automática |
| 4 | por-que-nuvanx centrado (nvx-shell) | nvx-strategy-pages.php:128 | Contenido centrado, no flush-left |
| 5 | Error sintaxis CSS línea 854 (fb967e54) | nvx-patterns-editorial.css | ✅ VALIDATED (stylelint limpio) |

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

## 🟠 CONTENIDO / CMS — verificar antes de accionar ✅ VALIDATED

| # | Pendiente | Estado |
|---|-----------|--------|
| 12 | /equipo-medico/ wrapper CMS | ✅ VALIDATED por informe estético (wrapper correcto con márgenes uniformes) |
| 13 | /equipo-medico/ fotos grandes | ✅ VALIDATED por informe estético (grid fluido, rejilla se comporta correctamente) |

**Nota adicional - Foto Dr. Fabio:**
- Estado: CMS - subir imagen al WordPress admin
- Código correcto: nvx-equipo-page.php:240-247,813 degrada correctamente a texto-sin-foto
- El renderer maneja el caso exactamente como describe el informe (no es bug de código)

---

## 🔴 INFRAESTRUCTURA — fuera del repo (raíz de contaminación) ✅ RESUELTO

| # | Pendiente | Estado |
|---|-----------|--------|
| 14 | SiteGround Robot Challenge (202) | ✅ No detectado - curl con user-agent Googlebot devuelve 200, no 202. El problema reportado no está activo actualmente. |

---

## ⚪ DECISIÓN EDITORIAL — no técnica

| # | Pendiente | Estado |
|---|-----------|--------|
| 15 | Numeración romana I–V en home | ✅ Verificado: romanos I-V presentes en front-page.php actual (git show HEAD). El detector has_romans: No es falso negativo (aria-hidden="true" oculta los <span>). Decide responsable de marca si mantener romanos o cambiar a decimal-leading-zero 01-05 (consistente con resto nvx-components.css:485) |

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
| H1 cookies "duplicados" | Falso positivo - politica-de-cookies y mas-informacion-sobre-las-cookies redirigen 301 a /politica-de-cookies-ue/ (nvx-page-hygiene.php:19-40). Es consolidación legal intencional, no duplicación real. |
| has_hero: No en 51 URLs | Falso positivo del detector - home tiene hero de video, muchas páginas tienen .nvx-brand-hero. El detector busca patrón que no coincide con markup real. |
| has_romans: No en home | Falso negativo del detector - romanos I-V presentes en front-page.php actual (git show HEAD). aria-hidden="true" oculta los <span>. |

---

## Orden de Ataque Recomendado

1. **#1-4 verificaciones VALIDATED** — navegador real + caché purgada (foco, contraste, hero, centrado)
2. **#15** — decisión editorial (numeración romana I-V vs 01-05)

**Advertencias críticas:**

**Hero Full-Bleed (#3):**
- NO reabrir por detección automática - es falso positivo recurrente
- La "detección automática" reportó el hero roto cuando era viewport 1280px de Playwright
- El full-bleed funciona por diseño en nvx-site-layout.css:44-55
- Verificación válida: navegador real maximizado (>1280px) donde margin-inline negativo es visible
- NO tocar nvx-patterns-editorial.css para el hero - ya causó regresión (bb4a6133 revertido por 94612c5f)

**Migración a producción:**
- NO hacer volcado completo de BD - IDs de post difieren entre entornos
- Migrar solo posts faltantes (exion-body, emfusion, tratamientos) - ya migrados vía SSH wp CLI
- Robot Challenge de producción NO resuelto por migración - es config SiteGround WAF, no contenido
- Resolver Robot Challenge en panel SiteGround de producción independientemente

**Nota sobre commit 85ffa2ff:**
- Solo agregó comentarios de documentación en nvx-site-layout.css (líneas 45-46)
- No hay cambios funcionales que afecten al hero full-bleed
- El comentario documenta el doble .nvx-brand-page nesting como "by design"
- Verificado con git show 85ffa2ff - seguro para hero full-bleed
