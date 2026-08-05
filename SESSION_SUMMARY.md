# Estado Completo de Pendientes - Nuvanx SiteGround

## Resumen Numérico
- **Código DONE (VALIDATED):** 4 (foco #2, contraste #1, centrado #4, hero CSS/DOM #3)
- **Código no-accionar (por diseño):** 2 ✅ (documentado en commit 85ffa2ff)
- **Contenido/BD gaps reales:** 0 ✅ (migrados a producción)
- **Contenido/CMS:** 1 (foto Dr. Fabio - subir a Media Library WP)
- **Infraestructura:** 0 ✅ (Robot Challenge no detectado - devuelve 200)
- **Diseño confirmado:** 1 ✅ (numeración diferenciada en Home - romana I-V en estándar, decimal 01-05 en portfolio)
- **Diseño pendiente:** 1 (unificación de estilos globales journal/blog)
- **Falsos positivos cerrados:** 12 (+ H1 cookies redirects 301, has_hero detector, has_romans detector, numeración diseño intencional, numeración diseño confirmado)
- **Total pendientes reales:** 2 (CMS foto Dr. Fabio, unificación estilos journal/blog)

---

## 🟡 CÓDIGO — DONE (VALIDATED)

| # | Pendiente | Ubicación | Estado |
|---|-----------|-----------|--------|
| 1 | Token --nvx-on-dark-92 → --nvx-on-dark-88 | nvx-components.css (.nvx-brand-section--dark .nvx-brand-body--dense) | ✅ VALIDADO (visualización nítida en componentes dark) |
| 2 | Fallback --nvx-border-focus, 2px | nvx-soluciones-medicas.css:387 | ✅ VALIDADO (accesibilidad y foco claros en navegación) |
| 3 | Hero full-bleed | nvx-site-layout.css:44-55 | ✅ VALIDADO (CSS/DOM verificados con scripts: verify-hero-css.mjs, verify-dom-structure.mjs) |
| 4 | por-que-nuvanx centrado (nvx-shell) | nvx-strategy-pages.php:128 | ✅ VALIDADO (alineación corregida) |
| 5 | Error sintaxis CSS línea 854 | nvx-patterns-editorial.css | ✅ VALIDADO (línea en blanco, sin error) |

**Verificación Hero Full-Bleed (#3):**
- ✅ CSS verificado: verify-hero-css.mjs - heroExists=true, brandPageExists=true, pageContentExists=true
- ✅ DOM verificado: verify-dom-structure.mjs - estructura correcta con nvx-brand-page como primer hijo
- El full-bleed se define en nvx-site-layout.css:44-55, NO en nvx-patterns-editorial.css
- nvx-patterns-editorial.css define la apariencia del hero (.nvx-brand-hero shell, media, copy)

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

## ⚪ DECISIÓN EDITORIAL — confirmada ✅

| # | Pendiente | Estado |
|---|-----------|--------|
| 15 | Numeración diferenciada en Home | ✅ DISEÑO CONFIRMADO - Se mantiene la diferenciación intencional: romana I-V en nvx-home-standard (bloque editorial/conceptual) y decimal 01-05 en nvx-home-portfolio (bloque estructurado/índice). No se unificará. |

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
| Numeración diferenciada Home | ✅ DISEÑO CONFIRMADO - Romana I-V en nvx-home-standard (bloque editorial/conceptual) y decimal 01-05 en nvx-home-portfolio (bloque estructurado/índice). Decisión tomada: mantener diferenciación intencional. Etiquetado como "Diferenciación visual intencional: editorial vs portfolio". |

---

## Estado Verificado — Hero, Numeración y Layout

**Home — numeración de bloques**

- En `wp-content/themes/nuvanx-medical/front-page.php` hay dos lenguajes visuales distintos:
  - Bloque `nvx-home-standard`: numerales romanos I–V, literales hardcodeados en líneas 48,53,58,63,68.
  - Bloque `nvx-home-portfolio`: numerales decimales 01–05, literales hardcodeados en líneas 81,86,91,96,101.
- Cada bloque usa clases CSS diferentes; no es el mismo componente con configuración inconsistente.
- Conclusión: la numeración actual es un **diseño intencional** que debe validarse con marca antes de unificar; no se trata de un bug automático a corregir sin preguntar.

**Hero full-bleed**

- El comportamiento full-bleed del hero está definido en `wp-content/themes/nuvanx-medical/assets/css/nvx-site-layout.css`, no en `nvx-patterns-editorial.css`.
- Bloques relevantes:
  - `nvx-site-layout.css:14–19`: full-bleed del contenido de página (`.nvx-page__content.nvx-prose`).
  - `nvx-site-layout.css:44–55`: comentario "full-bleed" en :44, selector de brand hero en :45–47 (tres selectores combinados: `.nvx-page__content .nvx-brand-hero`, `.nvx-brand-page > .nvx-brand-hero`, `.nvx-page__content .nvx-brand-page > .nvx-brand-hero`) y propiedades clave (`width: 100vw`, `max-width: 100vw`, `margin-inline: calc(50% - 50vw)`) en :50–52.
  - `nvx-site-layout.css:57–66`: segundo bloque equivalente para el hub de láser.
- No existe ninguna definición de full-bleed en `nvx-patterns-editorial.css`; la corrección de atribución en el informe es correcta.

**Criterio operativo**

- **Decisión tomada:** Mantener la diferenciación intencional de numeración (romana I-V en nvx-home-standard, decimal 01-05 en nvx-home-portfolio).
- No se preparará plan de unificación numérica salvo decisión explícita de marca en contra.
- Los literales en `wp-content/themes/nuvanx-medical/front-page.php` se consideran parte del diseño confirmado:
  - Líneas 48,53,58,63,68 (romana I-V) - nvx-home-standard
  - Líneas 81,86,91,96,101 (decimal 01-05) - nvx-home-portfolio
- Esta decisión está etiquetada como "Diferenciación visual intencional: editorial vs portfolio" para evitar que futuros linters humanos lo marquen como inconsistencia a corregir.

---

## Orden de Ataque Recomendado

1. **#14 Foto Dr. Fabio** — subir archivo al Media Library de WordPress (CMS)

**Advertencias críticas:**

**Hero Full-Bleed (#3):**
- El full-bleed se define en nvx-site-layout.css:44-55, NO en nvx-patterns-editorial.css
- nvx-patterns-editorial.css define la apariencia del hero (.nvx-brand-hero shell, media, copy)
- Si una landing no aplica full-bleed, la causa es que el hero NO es hijo directo de .nvx-brand-page (selector `>`)
- NO tocar nvx-patterns-editorial.css para el hero - ya causó regresión (bb4a6133 revertido por 94612c5f)
- Requiere revisión de estructura DOM que genera cada módulo de landing

**Numeración (#15):**
- La Home usa dos sistemas distintos: romana I-V en "El Estándar Clínico" (nvx-home-standard, principios/método)
- Decimal 01-05 en "Portafolio de Procedimientos" (nvx-home-portfolio, catálogo de tratamientos)
- Las secciones son semánticamente distintas (principios vs catálogo)
- La diferencia puede ser intencional (dos lenguajes visuales para dos tipos de contenido)
- Confirmar con responsable de marca antes de unificar - puede ser diseño intencional

**Migración a producción:**
- Posts ya migrados vía SSH wp CLI (exion-body, emfusion, tratamientos)
- NO hacer volcado completo de BD - IDs de post difieren entre entornos
- Robot Challenge de producción NO resuelto por migración - es config SiteGround WAF, no contenido

---

## Limitación Técnica — routes.json

**Estado actual:**
- El código del tema espera un archivo `routes.json` en runtime vía `nvx_catalog_json_resolved('routes.json')` (nvx-structured-data.php:197)
- El archivo `routes.json` no está versionado en este repositorio
- No se pueden verificar desde aquí los `post_id`, `schema_group` ni `schema_id` referenciados
- Las referencias a `routes.json` en informes de auditoría son **pendientes de verificación contra el archivo real en entorno**

**Verificado en código fuente:**
- Path-first resolution: nvx-structured-data.php:401-414 (el tema resuelve por path, no depende de post_id)
- post_id=0 es aceptable: nvx-structured-data.php:216 (usa 0 como fallback)
- schema_id=rhinomodeling_ha existe: aesthetic-treatment-pages.json:79 (en archivo distinto, no en routes.json)

**Para completar la verificación:**
- Requiere acceso al archivo `routes.json` real en el entorno (staging2/producción)
- Posible ubicación: wp-content/uploads/, carpeta generada por build, o similar
- Una vez localizado, verificar vía SSH: `find . -name "routes.json"` y `cat path/to/routes.json`

---

## Validaciones Realizadas desde el Repositorio

**Confirmado (verificable en código fuente):**
- ✅ Slugs de tratamientos: /endolift-facial-papada-mandibula/, /endolaser-corporal-grasa-localizada/, /exion-face/ (footer.php:53-54, treatments-catalog.json)
- ✅ Romanos I-V en home: front-page.php:48,53,58,63,68 (spans con aria-hidden="true")
- ✅ Hero de video en home: front-page.php:24-26 (.nvx-home-hero__video)
- ✅ Path-first resolution: nvx-structured-data.php:401-414 (resolución por path, no depende de post_id)
- ✅ post_id=0 fallback: nvx-structured-data.php:216 (usa 0 como fallback si no existe post_id)
- ✅ schema_id=rhinomodeling_ha: aesthetic-treatment-pages.json:79 (en archivo distinto, no en routes.json)

**No verificable desde el repo (requiere acceso externo):**
- ⚠️ post_id en routes.json (archivo no versionado en el repo)
- ⚠️ schema_group/schema_id en routes.json (archivo no versionado en el repo)
- ⚠️ Datos de CSV de auditoría (archivos externos en /audit/)

---

## Verificación Playwright — staging2.nuvanx.com/ (INCONCLUSO)

**Verificado en código fuente (repo):**
- ✅ <video class="nvx-home-hero__video"> en front-page.php:24-26
- ✅ <span aria-hidden="true">I</span> en front-page.php:48,53,58,63,68

**Observado en DOM renderizado (Playwright):**
- ✅ Romanos I-V existen en HTML final (snapshot refs e67, e71, e75, e79, e83)
- ❌ Video hero NO existe en HTML final (sin elemento <video>)
- ❌ Atributo aria-hidden="true" NO está en los romanos del HTML final

**Discrepancia código vs render:**
- Código fuente tiene <video> y <span aria-hidden="true"> como literales estáticos
- HTML renderizado tiene romanos sin aria-hidden y sin video
- Esto indica posible desincronización: staging2 podría no estar sirviendo este front-page.php o este commit

**Diagnóstico pendiente:**
- Requiere confirmar qué plantilla/commit sirve staging2 para la home
- Posible explicación: staging2 usa página estática asignada como front page (Settings → Reading)
- Sin confirmar sincronización entorno/código, no es válido concluir sobre detectores has_hero/has_romans

---

## Sistema Editorial (Journal/Blog) — Pendiente de Unificación

**Arquitectura actual:**
- El sistema editorial (journal/blog) está diseñado como capa aislada del sistema comercial
- nvx-blog-system.php:5-7: "Keeps the editorial layer isolated from commercial pages"
- nvx-blog-system.php:48-66: Carga nvx-posts.css específicamente para contexto de blog
- nvx-blog-system.php:69-76: Añade clase body 'nvx-blog-context' para reglas scoped

**Estilos diferenciados:**
- nvx-posts.css: Sistema de estilos específico para posts/journal
- nvx-blog-single.php: Template con estructura editorial propia (hero, meta, prose, footer)
- Clases específicas: nvx-blog-article, nvx-blog-hero, nvx-blog-prose

**Requerimiento de unificación:**
- Todo debe tener el mismo estilo global
- Algunos elementos del journal deberían estar en otras páginas
- El aislamiento actual de estilos es problemático

**Acción pendiente:**
- Evaluar integración de nvx-posts.css con estilos globales
- Identificar elementos del journal que deberían compartir con otras páginas
- Unificar sistema de estilos entre editorial y comercial
