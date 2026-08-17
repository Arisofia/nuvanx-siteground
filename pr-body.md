## Resumen

Este PR corrige el bloqueo estructural de los previews de PR en Staging2 y deja evidencia reproducible de higiene y validación del repositorio.

### Cambios incluidos

1. **Higiene de texto — `fe7e8ad8cbefbbf135be2eed82f032bb68ea7dc4`**
   - Normalización EOL a LF donde correspondía.
   - Eliminación de trailing whitespace no intencional.
   - Se preserva el único hard break Markdown intencional detectado en `DESIGN_GUIDE.md`.
   - Resultado final: `CRLF_FILES=0`, `UNINTENDED_TRAILING_WHITESPACE_LINES=0`.

2. **Archivos vacíos / generados rastreados — sin commit de borrado**
   - La auditoría encontró `EMPTY_TRACKED_FILES=0`.
   - La auditoría encontró `FORBIDDEN_TRACKED_ITEMS=0`.
   - No se crea un commit vacío ni se elimina ningún fichero sin evidencia.
   - `artifacts/audit-report/empty-tracked.txt` y `artifacts/audit-report/forbidden-tracked.txt` forman parte del artifact de auditoría y quedan vacíos porque no existen hallazgos.

3. **PR preview seguro — `e85ecf5435de40e847c2baf3098f6c4535f600f0`**
   - Elimina la política que exigía que `origin/master` fuese ancestro directo de `PR_SHA` antes del preview.
   - Construye un worktree temporal desde `origin/master` y realiza un merge local `master + PR` con `merge --no-commit --no-ff`.
   - Si existe un conflicto real, el preview falla sin desplegar y exige resolver/rebasar el PR.
   - El árbol combinado se materializa como `PR_PREVIEW_SHA` y esa identidad se usa de extremo a extremo para `.nvx-deploy-sha`, boundary verification y browser acceptance.
   - `PR_SHA` sigue identificando la propuesta original; `PR_PREVIEW_SHA` identifica exactamente el árbol probado.
   - El worktree temporal se elimina en cleanup.
   - Se mantiene el boundary de seguridad existente: el preview etiquetado solo acepta cambios del tema `wp-content/themes/nuvanx-medical/*`.

4. **Comparación exacta repo ↔ Staging2 ↔ Production — `tools/remote-compare.sh`**
   - Script read-only reutilizable con SHA-256 y rutas relativas completas.
   - Compara manifiestos de archivos y checksums sin perder identidad de directorios.
   - Excluye únicamente estado de runtime/deploy (`.nvx-deploy-sha`, `.nvx-deploy-stamp.json`, logs, backups y directorios de runtime conocidos).
   - Muestra diferencias local↔production, staging2↔production y los deploy markers.
   - Sintaxis Bash validada en CI.

## Evidencia de auditoría

Auditoría final sobre `fa25144bc5413f41e490465a0bf59dc064211fae`:

- Run: `32013822333`
- Artifact: `9282739124` — `audit-report-pr-preview-fix-final`
- GitHub Actions: https://github.com/Arisofia/nuvanx-siteground/actions/runs/32013822333
- Artifact: https://github.com/Arisofia/nuvanx-siteground/actions/runs/32013822333/artifacts/9282739124

Contenido del artifact `artifacts/audit-report/`:

- `php-lint.txt`
- `js-check.txt`
- `json-parse.txt`
- `routes-schema-validation.txt`
- `repo-lints.txt`
- `eol-whitespace.txt`
- `empty-tracked.txt`
- `forbidden-tracked.txt`
- `status.txt`

### Resultado final

```text
PHP_LINT_FAILURES=0
JS_CHECK_FAILURES=0
JSON_PARSE_FAILURES=0
ROUTES_SCHEMA_RC=0
REPO_LINT_FAILURES=0
CRLF_FILES=0
UNINTENDED_TRAILING_WHITESPACE_LINES=0
INTENTIONAL_MARKDOWN_HARD_BREAKS=1
EMPTY_TRACKED_FILES=0
FORBIDDEN_TRACKED_ITEMS=0
```

## Acciones priorizadas

- **P0 — blocking:** sin hallazgos. PHP lint = 0 errores.
- **P1 — SEO / catálogos / schema:** sin hallazgos. JSON parse = 0 errores y `routes.json` valida contra schema.
- **P2 — higiene:** completado. No quedan CRLF, trailing whitespace no intencional, archivos vacíos rastreados ni elementos prohibidos rastreados.
- **Gate restante:** no hacer merge hasta que los checks requeridos del PR estén verdes. Tras merge, `Staging` validará el nuevo `master` mediante el pipeline canónico.

## Pruebas realizadas

- `php -l` sobre PHP del tema, `tools/migrations` y `lib`.
- `node --check` sobre JavaScript del tema.
- Parse de todos los JSON de `wp-content/themes/nuvanx-medical/inc/data/*.json`.
- Validación de `routes.json` contra `routes.schema.json`.
- Lints canónicos del repositorio: SEO governed metadata, governed blog request contract, HubSpot single mount, console classifier, hardcoded colors/font-size, inline layout styles, rendered prices y semantic schema contract.
- Auditoría EOL / trailing whitespace distinguiendo hard breaks Markdown intencionales.
- Auditoría de archivos vacíos y artefactos prohibidos rastreados.
- `bash -n tools/remote-compare.sh`.

## Comparación exacta repo ↔ producción

El comparador es read-only. Desde un checkout local con los aliases SSH configurados:

```bash
bash tools/remote-compare.sh
```

Defaults:

```text
STAGING_SSH_ALIAS=nvx-staging2
PROD_SSH_ALIAS=nvx-prod
STAGING_ROOT=/home/customer/www/staging2.nuvanx.com/public_html
PROD_ROOT=/home/customer/www/nuvanx.com/public_html
THEME_REL=wp-content/themes/nuvanx-medical
```

Pueden sobrescribirse mediante variables de entorno. El script no escribe en Staging2 ni Production.

**Nota de interpretación:** Production está actualmente en el release exitoso de `master@7c74bbaff8a55a6ca2b98dedd076497de8c3f974`; esta rama contiene cambios pendientes, por lo que una comparación del HEAD del PR contra Production debe mostrar diferencias hasta que el PR se fusione y se despliegue.

## Estado de los fallos anteriores

- El run de Production `32002306439` reportó 65 `URL_SEO`, pero el issue efectivo era `deploy-sha-mismatch`, no 65 defectos independientes de meta description.
- La corrección de identidad quedó desplegada posteriormente en `master@7c74bbaf...` y el run `32003879917` terminó en success para promoción, SEO/GEO, Lighthouse y verificación HubSpot.
- El antiguo PR operativo `#565` se cerró sin merge. Sus helpers one-off (`Python` con estado global y workflow con target hardcodeado) no forman parte de este PR; por tanto, los comentarios de review se resuelven eliminando esa arquitectura temporal en lugar de perpetuarla.

## Notas de seguridad

- No se tocaron secretos.
- No se mutó Production durante esta preparación.
- Las auditorías fueron read-only sobre el repositorio.
- Los workflows auxiliares usados para auditoría/normalización fueron efímeros y sus ramas se eliminaron.
- Los cambios son reversibles y están separados por responsabilidad.
- **No aplicar la etiqueta `deploy-staging2` a este PR:** el boundary de seguridad del preview solo permite PRs con cambios dentro del tema, mientras que este PR modifica el propio workflow y tooling. El preview corregido debe validarse con un PR posterior que cambie únicamente el tema, después de fusionar este control-plane fix.
