# Editorial catalog–renderer migration plan (P2)

**Status:** Plan documental; no introduce cambios de runtime.  
**Sequence:** PR #267 fue cerrado sin merge. Ningún PR P2 puede asumir que sus cambios de hardening existen en `master`.  
**Scope:** Separar duplicación estructural de diferencias clínicas reales sin cambiar copy médico público, URLs ni contratos renderizados.

---

## 1. Inventario actual y baseline reproducible

El baseline provisional de planificación es el commit exacto `a8bde7d1a726848a20a74771bd4ab50e4cb99b82`, correspondiente a `master` después del merge de PR #266. El cierre sin merge de PR #267 no modifica este árbol.

Este baseline provisional no sustituye al baseline autoritativo de ejecución. El PR **P2.0** debe:

1. resolver el `HEAD` exacto de `master` al abrirse;
2. registrar ese valor como `BASELINE_SHA` en el documento y en los artefactos de CI;
3. recalcular líneas, funciones, hooks, slugs, dependencias y métricas Sonar sobre ese SHA;
4. conservar un artefacto versionado de inventario para que todos los PR P2.1–P2.7 utilicen la misma referencia o documenten expresamente un rebaseline.

Reproducción del baseline provisional:

```bash
git checkout a8bde7d1a726848a20a74771bd4ab50e4cb99b82
wc -l \
  wp-content/themes/nuvanx-medical/inc/nvx-btl-detail-pages.php \
  wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-treatment-pages.php \
  wp-content/themes/nuvanx-medical/inc/nvx-anatomical-pages.php \
  wp-content/themes/nuvanx-medical/inc/nvx-signature-phase-pages.php
```

| Familia | Archivo actual | Líneas | Datos | Renderizado | SEO | Schema | Seeder | Funciones públicas | Hooks |
| --- | --- | ---: | --- | --- | --- | --- | --- | ---: | --- |
| BTL | `inc/nvx-btl-detail-pages.php` | ~726 | Sí | Sí | Sí (`wpseo_title` / `wpseo_metadesc`) | Parcial | No | 16 | `the_content`, `wpseo_title`, `wpseo_metadesc` |
| Medicina estética | `inc/nvx-aesthetic-treatment-pages.php` | ~419 | Sí | Sí | Sí (title, description, OG, Twitter, document title) | Sí | Sí | 11 | `init`, `document_title_parts`, filtros Yoast múltiples |
| Anatomía | `inc/nvx-anatomical-pages.php` | ~364 | Sí | Sí | Sí, indirecto | Sí, indirecto | Variable | 6 | Ninguno directo en el archivo |
| Signature phases | `inc/nvx-signature-phase-pages.php` | ~343 | Sí | Sí | Variable | Variable | Variable | 12 | `the_content`, `wp`, `nvx_navigation_primary_blueprint` |

### Notas de inventario

| Familia | Slugs / rutas gobernadas (muestra) | Dependencias cruzadas |
| --- | --- | --- |
| BTL | `/exion-face/`, `/exion-body/`, `/exion-fractional/`, `/emfusion/`, `/btl-exilite-ipl-madrid/`, enlaces a `/exion-btl/` y Endoláser | Registry, helper de secciones, content restructure y SEO Yoast local |
| Medicina estética | Claves de catálogo por slug | FAQ catalog, schema catalog, seed staging, Yoast y document title |
| Anatomía | Catálogos facial upper/mid/lower y body mediante `nvx_anatomical_*_catalog` | Consumido por páginas y protocolos; builders `nvx_anatomical_entry` |
| Signature phases | `/protocolos-signature/`, fases Contour y CTA `/madrid/valoracion/` | Navegación primaria, shell prepare y helpers SEO |

### Duplicación histórica de planificación

| Archivo | Líneas duplicadas aprox. | Duplicación aprox. |
| --- | ---: | ---: |
| `nvx-aesthetic-treatment-pages.php` | 263 | 62,8 % |
| `nvx-anatomical-pages.php` | 220 | 60,4 % |
| `nvx-btl-detail-pages.php` | 314 | 43,3 % |
| `nvx-signature-phase-pages.php` | 140 | 40,8 % |
| `nvx-soluciones-medicas-github.php` | 109 | 43,1 % |
| `nvx-structured-data.php` | 219 | 15,3 % |

Estas cifras son contexto histórico, no un gate ejecutable. P2.0 debe sustituirlas por métricas exportadas desde el análisis Sonar del `BASELINE_SHA` autoritativo.

La duplicación observada es principalmente estructura repetida de catálogo y render —hero, secciones, FAQ, SEO—, no copy clínico intercambiable. No se deben unificar textos médicos diferentes para mejorar una métrica.

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

- **Definición canónica de página** mediante `nvxNormalizeEditorialPageDefinition` o clase final equivalente:
  - `slug`, `marker`, `h1`, `seoTitle`, `description`;
  - `sections[]`, `faqs[]`, `schema`, CTA y estado de revisión médica.
- **Validación:** campos obligatorios, un solo H1, secciones permitidas, FAQ estructurada, schema clínico, CTA y review status.

### Responsabilidades

| Capa | Debe | No debe |
| --- | --- | --- |
| `catalogs/*` | Contener datos clínicos y editoriales | Registrar hooks, construir HTML o ejecutar I/O WordPress |
| `renderers/*` | Producir HTML desde una definición normalizada | Conocer slugs concretos de una familia |
| `integrations/*` | Gestionar Yoast, JSON-LD, routing y seed | Incluir copy exclusivo de un tratamiento |
| Contratos | Definir forma y validación | Introducir presentación visual ad hoc |

---

## 3. Principios no negociables

1. Los catálogos contienen **datos**, no hooks.
2. Los renderers **no conocen slugs** concretos.
3. SEO y schema se generan desde la **misma definición canónica**.
4. **Cero** cambios de copy clínico durante la migración.
5. **Cero** cambios de URLs, IDs, anchors o clases CSS sin migración explícita documentada.
6. No crear abstracciones que borren **diferencias médicas reales**.
7. Cada familia se migra en un **PR independiente**.
8. El legacy se elimina **solo** tras equivalencia demostrada.
9. Full-site UI audit, visual QA, theme hygiene y Sonar se ejecutan según el alcance de cada etapa.
10. El merge utiliza `expected_head_sha` o mecanismo equivalente sobre el SHA validado.
11. Ningún gate puede depender de código que solo exista en un PR cerrado o no fusionado.

---

## 4. Estrategia de migración incremental

| Etapa | Entrega | PR |
| --- | --- | --- |
| **P2.0** | Fijar `BASELINE_SHA`; generar inventario vivo, artefacto de métricas, contratos y validación sin cambiar páginas | Independiente |
| **P2.1** | Renderer compartido de secciones y FAQ, con adopción opcional por BTL sin borrar legacy | Independiente |
| **P2.2** | Migrar **BTL** como piloto | Independiente |
| **P2.3** | Migrar medicina estética | Independiente |
| **P2.4** | Migrar páginas anatómicas | Independiente |
| **P2.5** | Migrar signature phases | Independiente |
| **P2.6** | Consolidar SEO y schema | Independiente |
| **P2.7** | Eliminar legacy y medir duplicación final | Independiente |

Cada etapa debe ser reversible mediante feature flag o coexistencia temporal cuando el riesgo lo justifique.

Artefacto mínimo de P2.0:

```text
docs/architecture/baselines/editorial-p2-<short-baseline-sha>.md
artifacts/editorial-p2-baseline/sonar-measures.json
artifacts/editorial-p2-baseline/inventory.json
```

---

## 5. Gates de equivalencia por slug

### Modelo A — SHA baseline y SHA candidato

Es el modelo predeterminado:

- `BASELINE_SHA`: commit exacto de `master` registrado por P2.0.
- `CANDIDATE_SHA`: `HEAD` exacto del PR de migración.
- Ambos se despliegan de forma inmutable, con la misma configuración, datos y viewports.
- Se generan artefactos independientes identificados por SHA.
- El comparador consume ambos artefactos y produce un diff reproducible.

### Modelo B — mismo SHA con feature flag

Solo se admite cuando legacy y nueva implementación coexisten en el mismo commit:

- se despliega un único `CANDIDATE_SHA`;
- se captura el modo `legacy` y el modo `new` por separado;
- cada artefacto registra nombre, valor y mecanismo de activación del flag;
- el comparador rechaza artefactos sin metadata del flag.

No se permite describir “antes” y “después” sin identificar uno de estos dos modelos.

| Dimensión | Criterio de paso |
| --- | --- |
| HTML | Normalizado equivalente, con whitespace controlado |
| Headings | Mismo H1 y jerarquía |
| Texto visible | Idéntico; cero diff clínico |
| Links / CTA | Mismos `href` y labels |
| IDs / anchors | Sin cambios no declarados |
| Clases estructurales | Sin cambios no declarados |
| SEO | Mismos title, description y canonical |
| Schema | JSON-LD normalizado equivalente |
| Visual | Capturas desktop y móvil comparables |
| HTTP | Mismo status y redirects solo mediante allowlist explícita |
| Layout | Sin overflow crítico |
| Deploy | SHA desplegado igual al SHA declarado en el artefacto |
| Performance | Lighthouse sin regresión material documentada |

**Slugs migrados sin evidencia = 0.**

---

## 6. Objetivos cuantitativos reproducibles

| Objetivo | Métrica y alcance | Herramienta / configuración | Baseline y artefacto | Gate |
| --- | --- | --- | --- | --- |
| Duplicación global | `duplicated_lines_density` del mismo scope de código analizado en P2.0 y P2.7 | SonarQube Cloud, mismo quality profile, exclusiones y parámetros | `sonar-measures.json` del `BASELINE_SHA` frente al export del SHA final P2.7 | Reducción relativa mínima del 30 % |
| Duplicación en código nuevo | `new_duplicated_lines_density` sobre archivos añadidos o modificados por cada PR P2 | SonarQube Cloud PR analysis y mismo New Code definition durante toda la etapa | Comentario/check de Sonar y export JSON asociado al `CANDIDATE_SHA` | Menor o igual al 3 % |
| Copy clínico | Diff de texto visible normalizado por slug | Comparador de artefactos baseline/candidato | Reporte versionado por SHA | 0 diferencias no aprobadas |
| URLs públicas | Set de rutas, canonical y redirects | Crawl + auditoría HTTP | JSON por SHA | 0 cambios no declarados |
| Regresiones visuales críticas | Findings críticos y diff visual | Full-site UI audit y visual QA | Artefactos por SHA | 0 |
| Regresiones de schema | Diff JSON-LD normalizado | Validador schema | JSON por SHA | 0 no declaradas |
| Slugs sin evidencia | Slugs migrados menos slugs con artefactos completos | Manifest de migración | `inventory.json` | 0 |

La comparación de duplicación es válida únicamente si el scope, las exclusiones y el quality profile son idénticos. Cualquier cambio de configuración exige un rebaseline documentado.

---

## 7. Contratos de evidencia y `report.json`

Existen productores distintos y sus esquemas no deben mezclarse.

### Visual QA: `scripts/staging2/capture-visual-qa-browser.mjs`

Su reporte utiliza `report.pages[]`. Cada elemento representa una combinación ruta × viewport e incluye, según el contrato vigente, campos como `path`, `viewport`, `deploySha`, `h1`, `screenshot`, `overflow`, `headerVisible` y `footerVisible`. El nivel raíz incluye `base_url`, `expected_sha`, `chrome`, `navigation` y `findings`.

Gate:

```text
report.pages.length = número esperado de combinaciones ruta × viewport
```

### Full-site UI audit

El auditor full-site puede utilizar un esquema diferente, por ejemplo `routes`, `results`, `critical`, `warnings` y métricas de discovery cuando estén disponibles en `master`. Su gate debe validar el esquema real del productor presente en el `CANDIDATE_SHA`; no debe exigir campos introducidos únicamente en un PR no fusionado.

Cada implementación P2 debe declarar:

- productor y versión/commit del contrato;
- ruta del artefacto;
- campos obligatorios;
- fórmula de cardinalidad esperada;
- SHA contenido en el reporte.

---

## 8. Primer piloto: BTL

**Elegido:** `nvx-btl-detail-pages.php`.

**Motivo:** estructura consistente y acotada:

```text
Hero → Mecanismo → Indicaciones → Comparativa → Proceso → FAQ → CTA → SEO
```

Ya existe `nvxRenderEditorialListSection`, lo que reduce el coste del primer renderer compartido.

No se comienza por `nvx-aesthetic-treatment-pages.php` pese a su mayor duplicación porque combina catálogo clínico, seeding, metadata, riesgos y schema, con mayor superficie de regresión.

### Criterio de éxito del piloto

- Equivalencia de los gates de §5 en todos los slugs BTL gobernados.
- Duplicación BTL reducida de forma medible sin modificar copy.
- Ningún cambio no previsto en menú, schema o SEO.
- Evidencia identificada por SHA para todos los slugs y viewports.

---

## 9. Fuera de alcance de este documento

- Movimientos de archivos en runtime.
- Refactors ejecutables.
- Cambios de tema o CI.
- Reintroducir automáticamente cambios procedentes de PR #267.
- Unificación de `nvx-structured-data.php`, planificada para P2.6.

---

## 10. Criterios de entrada para los PR de implementación

1. PR #267 permanece cerrado sin merge; sus cambios no forman parte del baseline.
2. P2.0 registra el `BASELINE_SHA` exacto del `master` vigente y publica inventario y métricas reproducibles.
3. Cada PR posterior declara su `CANDIDATE_SHA`, el modelo de comparación de §5 y los contratos de evidencia de §7.
4. Theme hygiene y Sonar deben pasar en el SHA final del PR.
5. Los audits aplicables deben finalizar sin hallazgos críticos sobre el mismo SHA.
6. CodeRabbit no debe mantener una revisión vigente en estado `CHANGES_REQUESTED`.
7. El merge se ejecuta con `expected_head_sha` del commit validado.
8. Este plan se entrega en `docs/architecture/editorial-catalog-renderer-p2.md`, dentro de la rama `docs/editorial-architecture-p2` del PR #268.
9. Después del merge documental, ejecutar P2.0 → P2.2 en PRs pequeños, independientes y reversibles.
