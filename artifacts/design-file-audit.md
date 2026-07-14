# Design File Audit — nuvanx-medical-wpvibe-draft

Generado: 2026-07-14 (local, post-SCP)

## Resumen

| Métrica | Valor |
|---------|-------|
| Archivos totales | 70 |
| CSS en assets/css | 44 (22 pares .css + .min.css) |
| PHP templates | 4 en templates/, 7 en template-parts/ |
| Tema activo local (scaffold) | nuvanx-medical — 13 archivos, NO es este borrador |

---

## Archivos núcleo inspeccionados

### functions.php — EXISTE
- **Propósito:** Setup tema, enqueue masivo de CSS por contexto (home, tratamiento, sede, contacto, blog).
- **Dependencias:** inc/*, define `NVX_BRAND_TREATMENT_PAGE_IDS`, carga `.min.css` para brand-system y treatment-core.
- **Contenido:** No usa DOMDocument ni parsers editoriales.
- **Decisión:** No modificar en iteración global.

### front-page.php — EXISTE
- **Propósito:** Carga `template-parts/editorial/home.php`.
- **Contenido:** `the_content()` + template part.
- **Decisión:** Pendiente fase Home.

### header.php / footer.php — EXISTE (vía template-parts)
- **Propósito:** `template-parts/header/site-header.php`, `template-parts/footer/site-footer.php`.
- **CSS:** nvx-header.css, nvx-footer.css.
- **Decisión:** CSS global editado en iteración 1.

### style.css — EXISTE
- Theme Name: NUVANX Fluid Organic 2026 · v2.0.0-draft.

### template-parts/editorial/home.php — EXISTE
- **Propósito:** Composición Home editorial.
- **Contenido:** PHP hardcodeado + datos reales referenciados.
- **Decisión:** Pendiente fase Home (no tocado).

---

## CSS inspeccionados

| Archivo | Existe | Propósito | !important | Problemas previos | Iteración 1 |
|---------|--------|-----------|------------|-------------------|-------------|
| nvx-site-layout.css | Sí | Gutter owner, shell, secciones | 0 | Comentario 20px legacy | **Editado** — fondo continuo, sin border-top global, heroes sin 100vh |
| nvx-header.css | Sí | Header sticky, nav, CTA, móvil | 0 | padding hardcoded, backdrop dupes | **Editado** — gutter único, header 64px, fondo ivory sólido |
| nvx-footer.css | Sí | Footer editorial 5 columnas | 0 | OK | **Editado** — separador pre-footer |
| nvx-components.css | Sí | Botones, cards, FAQ, frost | 0 | Cards SaaS, hover lift, glass | **Editado** — cards planas, sin elevación, FAQ con línea inferior |
| nvx-brand-home.css | Sí | Home específico | — | Sombras ya neutralizadas (servidor) | No tocar (fase Home) |
| nvx-brand-system.css | Sí | Sistema marca + bridges removidos | 19 en .css | Legacy bridges ya limpiados en servidor | No tocar |
| nvx-brand-treatment-core.css | Sí | Tratamientos compartidos | 19 | idem | No tocar |
| nvx-secondary-pages.css | Sí | Contacto, valoración, casos | — | Hero contacto ajustado en servidor | No tocar |
| nvx-sede-page.css | Sí | Chamberí, Goya | — | — | No tocar |
| nvx-posts.css | Sí | Blog/journal | — | — | No tocar |
| nvx-forms.css | Sí | Formularios | — | — | No tocar |

### CSS ausentes del listado pedido
- Ninguno de los 12 listados falta.

### Duplicaciones detectadas
- Pares `.css` + `.min.css` (22×2) — functions.php enqueuea `.min.css` en brand-system y treatment-core. **No regenerar .min** en iteración global (solo 4 CSS fuente editados; min de esos 4 no se usan en enqueue principal).
- Alias `.nvx-btn` / `.nvx-button` en components — intencional, no eliminar aún.

---

## Contenido — fuentes por template

| Template | Fuente contenido |
|----------|------------------|
| editorial/home.php | Bloques PHP + contenido referenciado |
| editorial/tratamiento.php | the_content() + estructura editorial |
| editorial/clinicas.php | the_content() + bandas sede |
| page-contacto.php | Template + HubSpot (MU-plugin) |
| page-landing-valoracion.php | Template + formulario |
| page-sede.php | the_content() |
| page-tratamiento.php | Wrapper → editorial/tratamiento.php |
| nvx-page-shell.php | the_content() genérico |

**No se modifica post_content.**

---

## Thermage

Búsqueda case-insensitive en tema borrador: **0 coincidencias**.

---

## Bloqueos / notas

1. PHP CLI no disponible en PATH Windows — lint no ejecutado (ver php-lint.txt).
2. Tema borrador completo es **untracked** en git — `git diff` vacío; diff real en `artifacts/global-design-diff.txt` vs backup.
3. `artifacts/` fuera de commit (correcto).
4. No duplicar con `C:\Users\IvónYamilethRiveraDe\artifacts\` (sesión SSH previa) — es evidencia remota, no código fuente.