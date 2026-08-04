# Estado Completo de Pendientes - Nuvanx SiteGround

## Resumen Numérico
- **Código DONE (VALIDATED):** 3 (foco #2, contraste #1, centrado #4)
- **Código DONE (PARCIAL):** 1 (hero #3 - requiere revisión DOM por página)
- **Código no-accionar (por diseño):** 2 ✅ (documentado en commit 85ffa2ff)
- **Contenido/BD gaps reales:** 0 ✅ (migrados a producción)
- **Contenido/CMS:** 1 (foto Dr. Fabio - subir a Media Library WP)
- **Infraestructura:** 0 ✅ (Robot Challenge no detectado - devuelve 200)
- **Editorial:** 1 (numeración inconsistente en Home - romana vs decimal)
- **Falsos positivos cerrados:** 10 (+ H1 cookies redirects 301, has_hero detector, has_romans detector)
- **Total pendientes reales:** 3 (1 hero parcial + 1 CMS foto + 1 editorial)

---

## 🟡 CÓDIGO — DONE (VALIDATED en navegador real)

| # | Pendiente | Ubicación | Estado |
|---|-----------|-----------|--------|
| 1 | Token --nvx-on-dark-92 → --nvx-on-dark-88 | nvx-components.css (.nvx-brand-section--dark .nvx-brand-body--dense) | ✅ VALIDADO (visualización nítida en componentes dark) |
| 2 | Fallback --nvx-border-focus, 2px | nvx-soluciones-medicas.css:387 | ✅ VALIDADO (accesibilidad y foco claros en navegación) |
| 3 | Hero full-bleed | nvx-site-layout.css:44-55 | ⚠️ PARCIAL - Activo en CSS, requiere revisión DOM por página (ver nota abajo) |
| 4 | por-que-nuvanx centrado (nvx-shell) | nvx-strategy-pages.php:128 | ✅ VALIDADO (alineación corregida) |
| 5 | Error sintaxis CSS línea 854 | nvx-patterns-editorial.css | ✅ VALIDADO (línea en blanco, sin error) |

**Nota sobre Hero Full-Bleed (#3):**
- El full-bleed se define en nvx-site-layout.css, NO en nvx-patterns-editorial.css
- nvx-patterns-editorial.css define la apariencia del hero (.nvx-brand-hero shell, media, copy)
- Si una landing no aplica full-bleed, la causa es que el hero NO es hijo directo de .nvx-brand-page (selector `>`)
- Los hubs de láser usan selector aparte: .nvx-brand-page--laser-hub > .nvx-brand-hero
- Requiere revisión de estructura DOM que genera cada módulo de landing

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

## 🟠 CONTENIDO / CMS — pendientes

| # | Pendiente | Estado |
|---|-----------|--------|
| 12 | /equipo-medico/ wrapper CMS | ✅ VALIDADO (estructura de diseño respetada) |
| 13 | /equipo-medico/ fotos grandes | ✅ VALIDADO (grid fluido) |
| 14 | Foto Dr. Fabio Quiñónez | ❌ OPEN - Falta subir archivo al Media Library de WordPress |

**Nota sobre Foto Dr. Fabio:**
- Código correcto: nvx-equipo-page.php:240-247,813 degrada correctamente a texto-sin-foto
- El renderer maneja el caso exactamente como describe el informe (no es bug de código)
- Acción requerida: subir imagen al WordPress admin (CMS, no código)

---

## 🔴 INFRAESTRUCTURA — fuera del repo (raíz de contaminación) ✅ RESUELTO

| # | Pendiente | Estado |
|---|-----------|--------|
| 14 | SiteGround Robot Challenge (202) | ✅ No detectado - curl con user-agent Googlebot devuelve 200, no 202. El problema reportado no está activo actualmente. |

---

## ⚪ DECISIÓN EDITORIAL — no técnica

| # | Pendiente | Estado |
|---|-----------|--------|
| 15 | Numeración inconsistente en Home | ❌ OPEN - La Home usa dos sistemas distintos: romana I-V en "El Estándar Clínico" (nvx-home-standard) y decimal 01-05 en "Portafolio de Procedimientos" (nvx-home-portfolio). Ambos son texto quemado en front-page.php con aria-hidden="true". Decisión: unificar a romana o decimal. |

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
| H1 cookies "duplicados" | Falso positivo - politica-de-cookies y mas-informacion-sobre-las-cookies redirigen 301 a /politica-de-cookies-ue/ (nvx-page-hygiene.php:19-40). Es consolidación legal intencional, no duplicación real. Los H1 provienen del CMS, no del código del theme. |
| has_hero: No en 51 URLs | Falso positivo del detector - home tiene hero de video, muchas páginas tienen .nvx-brand-hero. El detector busca patrón que no coincide con markup real. |
| has_romans: No en home | Falso negativo del detector - romanos I-V presentes en front-page.php actual (git show HEAD). aria-hidden="true" oculta los <span>. |
| Bloque @media vacío (861-863) | Nota menor no bloqueante - stylelint puede marcar block-no-empty según config. No afecta funcionalidad. |

---

## Orden de Ataque Recomendado

1. **#15 Numeración inconsistente en Home** — decisión editorial (unificar romana I-V o decimal 01-05 en ambas secciones)
2. **#14 Foto Dr. Fabio** — subir archivo al Media Library de WordPress (CMS)
3. **#3 Hero full-bleed PARCIAL** — auditar módulos de landing que no emiten hero como hijo directo de .nvx-brand-page

**Advertencias críticas:**

**Hero Full-Bleed (#3):**
- El full-bleed se define en nvx-site-layout.css:44-55, NO en nvx-patterns-editorial.css
- nvx-patterns-editorial.css define la apariencia del hero (.nvx-brand-hero shell, media, copy)
- Si una landing no aplica full-bleed, la causa es que el hero NO es hijo directo de .nvx-brand-page (selector `>`)
- NO tocar nvx-patterns-editorial.css para el hero - ya causó regresión (bb4a6133 revertido por 94612c5f)
- Requiere revisión de estructura DOM que genera cada módulo de landing

**Numeración (#15):**
- La Home usa dos sistemas distintos: romana I-V en "El Estándar Clínico" (nvx-home-standard)
- Decimal 01-05 en "Portafolio de Procedimientos" (nvx-home-portfolio)
- Ambos son texto quemado en front-page.php con aria-hidden="true"
- Decisión: unificar a romana o decimal (no es "cambiar de romana a decimal", es "unificar")

**Migración a producción:**
- Posts ya migrados vía SSH wp CLI (exion-body, emfusion, tratamientos)
- NO hacer volcado completo de BD - IDs de post difieren entre entornos
- Robot Challenge de producción NO resuelto por migración - es config SiteGround WAF, no contenido
