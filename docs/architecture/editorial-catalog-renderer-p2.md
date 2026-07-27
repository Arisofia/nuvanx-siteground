# Editorial catalog–renderer migration plan (P2)

**Status:** Draft plan only — no runtime refactor in this document.  
**Sequence:** Close PR #267 (Quality Gate + full-site audit on exact SHA) before any P2 implementation PR.  
**Scope:** Separate structural duplication from real clinical content differences without changing public medical copy or URLs.

---

## 1. Inventario actual

Cifras de líneas y superficie funcional tomadas del árbol en commit `a8bde7d1a726848a20a74771bd4ab50e4cb99b82` (merge de PR #266). Para reproducir el inventario en cada PR de migración, ejecutar `git checkout a8bde7d1a726848a20a74771bd4ab50e4cb99b82` y contar líneas con `wc -l` sobre los archivos listados, o usar el script de análisis de superficie funcional si está disponible.

| Familia | Archivo actual | Líneas | Datos | Renderizado | SEO | Schema | Seeder | Funciones públicas | Hooks |
| --- | --- | ---: | --- | --- | --- | --- | --- | ---: | --- |
| BTL | `inc/nvx-btl-detail-pages.php` | ~726 | Sí | Sí | Sí (`wpseo_title` / `wpseo_metadesc`) | Parcial (vía markup / no graph dedicado en el mismo archivo) | No | 16 | `the_content`, `wpseo_title`, `wpseo_metadesc` |
| Medicina estética | `inc/nvx-aesthetic-treatment-pages.php` | ~419 | Sí | Sí (via catálogo + páginas) | Sí (title, description, OG, Twitter, document title) | Sí (`nvx_aesthetic_treatment_schema_catalog` + módulo schema) | Sí (`nvx_aesthetic_treatment_seed_staging_pages`) | 11 | `init`, `document_title_parts`, filtros Yoast múltiples |
| Anatomía | `inc/nvx-anatomical-pages.php` | ~364 | Sí | Sí (catálogo consumido por otros módulos) | Sí (indirecto) | Sí (indirecto) | Variable | 6 | Ninguno directo en el archivo (catálogo puro + builders) |
| Signature phases | `inc/nvx-signature-phase-pages.php` | ~343 | Sí | Sí | Variable | Variable | Variable | 12 | `the_content`, `wp`, `nvx_navigation_primary_blueprint` |

### Notas de inventario

| Familia | Slugs / rutas gobernadas (muestra) | Dependencias cruzadas |
| --- | --- | --- |
| BTL | `/exion-face/`, `/exion-body/`, `/exion-fractional/`, `/emfusion/`, `/btl-exilite-ipl-madrid/`, enlaces a `/exion-btl/`, Endoláser | Registry + list section helper + content restructure; SEO Yoast local |
| Medicina estética | Claves de catálogo por slug (facial/tratamientos) | FAQ catalog, schema catalog, seed staging, filtros Yoast y document title |
| Anatomía | Catálogos facial (upper/mid/lower) y body vía `nvx_anatomical_*_catalog` | Consumido por páginas / protocolos; builders `nvx_anatomical_entry` |
| Signature phases | `/protocolos-signature/` y fases Contour; CTA `/madrid/valoracion/` | Navegación primary blueprint, shell prepare, SEO title/description helpers |

### Duplicación (contexto Sonar histórico)

La concentración de líneas duplicadas reportada en el diagnóstico post-#266 (orden de magnitud):

| Archivo | Líneas duplicadas (aprox.) | Duplicación % (aprox.) |
| --- | ---: | ---: |
| `nvx-aesthetic-treatment-pages.php` | 263 | 62,8 % |
| `nvx-anatomical-pages.php` | 220 | 60,4 % |
| `nvx-btl-detail-pages.php` | 314 | 43,3 % |
| `nvx-signature-phase-pages.php` | 140 | 40,8 % |
| `nvx-soluciones-medicas-github.php` | 109 | 43,1 % |
| `nvx-structured-data.php` | 219 | 15,3 % |

Interpretación: la mayor parte es **estructura de catálogo + render repetido** (hero, secciones, FAQ, SEO), no copy clínico idéntico. No se debe “secar” unificando textos médicos distintos.

Re-medir con Sonar en cada PR de migración; estos números son baseline de planificación.

---

## 2. Arquitectura objetivo

```text
inc/editorial/
├── contracts/
│   ├── nvx-editorial-definition.php
│   └── nvx-editorial-validation.php
├── renderers/
│   ├── nvx-editorial-page-renderer.php
│   ├── nvx-editorial-hero-renderer.php
│   ├── nvx-editorial-section-renderer.php
│   └── nvx-editorial-faq-renderer.php
├── integrations/
│   ├── nvx-editorial-seo.php
│   ├── nvx-editorial-schema.php
│   ├── nvx-editorial-routing.php
│   └── nvx-editorial-seeder.php
└── catalogs/
    ├── nvx-catalog-btl.php
    ├── nvx-catalog-aesthetic.php
    ├── nvx-catalog-anatomical.php
    └── nvx-catalog-signature.php
```

### Contratos

- **Definición canónica de página** (`nvxNormalizeEditorialPageDefinition` o clase final equivalente):
  - `slug`, `marker`, `h1`, `seoTitle`, `description`
  - `sections[]`, `faqs[]`, `schema`, CTA, estado de revisión médica
- **Validación**: campos requeridos, un solo H1, secciones permitidas, FAQ estructurada, schema clínico, CTA, review status.

### Responsabilidades

| Capa | Debe | No debe |
| --- | --- | --- |
| `catalogs/*` | Datos clínicos/editoriales | `add_action` / `add_filter`, HTML suelto, I/O WP |
| `renderers/*` | HTML a partir de definición normalizada | Conocer slugs concretos de una familia |
| `integrations/*` | Yoast, JSON-LD, routing, seed | Embutir copy de un solo tratamiento |
| Contratos | Forma y validación | Presentación visual ad hoc |

---

## 3. Principios no negociables

1. Los catálogos contienen **datos**, no hooks.
2. Los renderers **no conocen slugs** concretos.
3. SEO y schema se generan desde la **misma definición canónica**.
4. **Cero** cambios de copy clínico durante la migración.
5. **Cero** cambios de URLs, IDs, anchors o clases CSS sin migración explícita documentada.
6. No crear abstracciones que borren **diferencias médicas reales**.
7. Cada familia se migra en **PR independiente**.
8. El legacy se elimina **solo** tras equivalencia demostrada.
9. Full-site UI audit + theme hygiene + Sonar en cada etapa.
10. Merge solo con `expected_head_sha` (o equivalente) del SHA auditado.

---

## 4. Estrategia de migración incremental

| Etapa | Entrega | PR |
| --- | --- | --- |
| **P2.0** | Inventario vivo + contratos + validación (sin cambiar runtime de páginas) | Independiente |
| **P2.1** | Renderer compartido de secciones y FAQ (adoptado opcionalmente por BTL sin borrar legacy) | Independiente |
| **P2.2** | Migrar **BTL** como piloto | Independiente |
| **P2.3** | Migrar medicina estética | Independiente |
| **P2.4** | Migrar páginas anatómicas | Independiente |
| **P2.5** | Migrar signature phases | Independiente |
| **P2.6** | Consolidar SEO y schema | Independiente |
| **P2.7** | Eliminar legacy y medir duplicación final | Independiente |

Cada etapa debe ser **reversible** (feature flag o coexistencia temporal si hace falta).

---

## 5. Gates de equivalencia (por slug)

Comparar **antes / después** en el mismo SHA de staging. "Antes" = implementación legacy (código previo a la migración); "después" = implementación nueva (tras adoptar renderers compartidos). Si ambas implementaciones coexisten detrás de un feature flag en el mismo commit, comparar ambos modos del flag en el mismo SHA desplegado en staging, documentando el estado del flag y el mecanismo de activación para cada versión auditada:

| Dimensión | Criterio de paso |
| --- | --- |
| HTML | Normalizado equivalente (whitespace controlado) |
| Headings | Mismo H1 y jerarquía |
| Texto visible | Idéntico (0 diff clínico) |
| Links / CTA | Mismos `href` y labels |
| IDs / anchors | Sin cambios no declarados |
| Clases estructurales | Sin cambios no declarados |
| SEO | title, description, canonical |
| Schema | JSON-LD normalizado equivalente |
| Visual | Capturas desktop + móvil |
| HTTP | Mismo status; sin redirects no allowlist |
| Layout | Sin overflow crítico |
| Deploy | SHA desplegado = SHA auditado |
| Performance | Lighthouse sin regresión material |

**Slugs migrados sin evidencia = 0.**

---

## 6. Objetivos cuantitativos

```text
Duplicación global:           reducción mínima del 30 % (medida con SonarQube análisis de duplicación; comparar % de duplicación total del repositorio entre SHA baseline a8bde7d1a726848a20a74771bd4ab50e4cb99b82 y el SHA final de P2.7; usar el mismo quality profile y configuración de Sonar; baseline de duplicación global disponible en dashboard histórico de Sonar del proyecto)
Duplicación en código nuevo:  < 3 % (medida con SonarQube New Code quality gate sobre archivos nuevos o modificados en cada PR de migración; criterio de paso: porcentaje de duplicación en new code <= 3 % según reporte de Sonar para el PR; configurar New Code period al inicio de cada PR de migración)
Cambios de copy clínico:      0
Cambios de URLs públicas:     0
Regresiones visuales críticas: 0
Regresiones de schema:        0
Slugs migrados sin evidencia: 0
```

---

## 7. Primer piloto: BTL

**Elegido:** `nvx-btl-detail-pages.php`

**Motivo:** estructura ya consistente y acotada:

```text
Hero → Mecanismo → Indicaciones → Comparativa → Proceso → FAQ → CTA → SEO
```

Ya existe un helper de lista editorial (`nvxRenderEditorialListSection`) que reduce el coste del primer renderer compartido.

**No empezar por** `nvx-aesthetic-treatment-pages.php` pese a mayor % de duplicación: combina catálogo clínico, seeding, metadata, riesgos y schema con mayor superficie de regresión.

### Criterio de éxito del piloto

- Equivalencia de gates (§5) en todos los slugs BTL gobernados.
- Duplicación del archivo BTL reducida de forma medible sin tocar copy.
- Ningún cambio en menú / schema / SEO no previsto en el PR del piloto.
- Full Site UI Audit OK sobre el SHA exacto del PR.

---

## 8. Fuera de alcance de este documento

- Movimientos de archivos en runtime.
- Refactors ejecutables.
- Cambios de tema o de CI más allá de lo ya estabilizado en #267.
- Unificación de `nvx-structured-data.php` (solo se planifica en P2.6).

---

## 9. Secuencia recomendada con #267

1. **Merge #267** solo con:
   - Full Site UI Audit **success** en el SHA final.
   - `report.json` (generado por `scripts/staging2/capture-visual-qa-browser.mjs`) con `report.pages[]` conteniendo un objeto por cada combinación de ruta × viewport auditado, incluyendo campos `path`, `viewport`, `deploySha`, `h1`, `screenshot`, `overflow`, `headerVisible`, `footerVisible`, etc.; también `report.base_url`, `report.expected_sha`, `report.chrome`, `report.navigation`, y `report.findings[]`.
   - `report.pages.length` debe igualar el número esperado de combinaciones ruta × viewport definidas en el archivo de configuración de páginas auditadas.
   - Sonar Quality Gate **Passed**.
   - CodeRabbit sin `CHANGES_REQUESTED`.
   - Política de redirects estricta + allowlist.
   - Merge con `expected_head_sha` del SHA auditado.
2. Abrir este plan como **Draft PR documental** añadiendo el archivo `docs/architecture/editorial-catalog-renderer-p2.md` (este documento).
3. Ejecutar P2.0 → P2.2 (piloto BTL) en PRs pequeños y reversibles.

