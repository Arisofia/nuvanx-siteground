# Staging2 rendered acceptance

## Objetivo

La aceptación renderizada demuestra que staging2 sirve exactamente el SHA autorizado y que las rutas canónicas cumplen el contrato de contenido, SEO, redirects y Schema después del despliegue.

El control es de solo lectura. No modifica WordPress, la base de datos ni los archivos remotos.

## Implementación canónica

El único verificador de aceptación renderizada es:

```text
scripts/staging2/verify-rendered-acceptance-ssh.mjs
```

La consulta se ejecuta desde el origen mediante la conexión SSH configurada por el workflow. Esto evita depender de la respuesta del edge público de SiteGround y elimina la antigua variante de transporte HTTP directo.

Módulos compartidos:

```text
scripts/staging2/staging2-contract-common.mjs
scripts/staging2/visual-qa-edge-preload.mjs
```

## Variables obligatorias

```text
BASE_URL=https://staging2.nuvanx.com
EXPECTED_SHA=<SHA completo de 40 caracteres>
EVIDENCE_DIR=staging2-deployment-evidence/rendered-acceptance
STAGING2_SSH_ALIAS=nvx-staging2
```

El verificador rechaza cualquier `BASE_URL` distinto de staging2, cualquier SHA incompleto y cualquier alias SSH con caracteres no permitidos.

## Rutas canónicas verificadas

La aceptación exige HTTP 200, un H1 exacto, metadata versionada, marcadores editoriales y ausencia de contenido retirado en:

- `/soluciones-medicas/`
- `/protocolos-signature/`
- `/remodelacion-corporal-laser-madrid/`
- `/tratamiento-postparto-abdomen-contorno-corporal-madrid/`
- `/por-que-nuvanx/`
- `/inversion-medicina-estetica/`
- las páginas anatómicas publicadas definidas en `staging2-contract-common.mjs`

## Redirects gobernados

Debe existir un redirect 301 directo para:

```text
/tratamientos/     → /soluciones-medicas/
/liposculpt-air/   → /remodelacion-corporal-laser-madrid/
/v-lift-awake/     → /protocolos-signature/
```

No se aceptan 302, 307, cadenas de redirects, destinos diferentes o rutas 404.

## Contrato por página

Cada página debe cumplir:

- marker `nvx-deploy-sha` igual a `EXPECTED_SHA`;
- un único H1;
- title y meta description iguales al catálogo versionado;
- equivalencia de Open Graph;
- `noindex,nofollow` en staging2;
- canonical u `og:url` con el path correcto;
- CTA hacia `/madrid/valoracion/`;
- JSON-LD válido;
- entidades `WebPage` y `Organization` o `MedicalOrganization`;
- ausencia de prototipos, placeholders, estados internos y claims bloqueados.

## Secuencia del workflow

```text
Validar contratos estáticos
→ validar sintaxis
→ comprobar identidad de staging2
→ crear backup
→ sincronizar el tema del SHA autorizado
→ purgar cachés
→ probar callbacks reales de WordPress
→ auditar y aplicar la migración gobernada
→ ejecutar smoke remoto
→ ejecutar aceptación renderizada por SSH
→ ejecutar QA visual real
→ publicar evidencia
```

La migración no comienza si el bootstrap del tema, `the_content`, los callbacks o la página de Soluciones Médicas no superan la sonda de runtime.

## Evidencia

El artifact `staging2-deployment-evidence` debe incluir como mínimo:

```text
run-context.txt
ssh-connectivity.log
preflight.log
remote-deploy.log
deployed-marker.log
independent-smoke.log
rendered-acceptance.log
rendered-acceptance/report.json
postflight.log
```

El directorio de aceptación conserva también el HTML recibido para cada ruta auditada.

## Resultado esperado

Un despliegue aceptado debe emitir:

```text
STAGING2_PREFLIGHT_OK
runtime_hooks=ok
Production-readiness audit passed.
SMOKE_VERIFY_OK
RENDERED_ACCEPTANCE_OK
VISUAL_QA_OK
DEPLOY_STAGING2_OK
```

## Criterios de bloqueo

La promoción queda bloqueada cuando:

- staging2 sirve un SHA diferente;
- falta una ruta aprobada;
- un redirect no es 301 directo;
- existe más de un H1;
- la metadata, canonical, Open Graph o Schema no coincide;
- aparece contenido provisional o una formulación clínica bloqueada;
- la auditoría de migración no queda limpia;
- falta evidencia;
- el QA visual falla;
- el candidato de producción no es el mismo SHA validado.
