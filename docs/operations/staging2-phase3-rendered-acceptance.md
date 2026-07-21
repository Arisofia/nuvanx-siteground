# Staging2 rendered acceptance — Phase 3

## Estado de partida

La arquitectura de production readiness y el workflow protegido ya están fusionados en `master`, pero el estado renderizado de staging2 no constituye todavía una validación del código actual.

La auditoría conectada previa a esta fase confirmó:

- staging2 publicaba el marker `ddd583823b39f5698bae9eb630ed18dd86408873`, distinto del candidato auditado;
- `/protocolos-signature/` devolvía una página 404;
- `/remodelacion-corporal-laser-madrid/` devolvía una página 404;
- los slugs retirados de LipoSculpt-Air™, V-Lift Awake™ y Post-Maternity mostraban 404 en vez de los redirects 301 gobernados;
- `por-que-nuvanx` e `inversion-medicina-estetica` estaban publicados;
- las páginas Signature aprobadas todavía no existían en la base de datos;
- LipoSculpt-Air™ y V-Lift Awake™ permanecían como borradores con meta `pending_medical_legal`.

Por tanto, el estado se mantiene en **NO-GO para producción** hasta desplegar y validar un SHA exacto.

## Objetivo

Convertir la aceptación renderizada en una condición automática y bloqueante del workflow manual de staging2.

El gate no escribe en WordPress, no modifica archivos remotos y no toca producción. Solo consulta la web pública de staging2 después del preflight y, cuando corresponda, después del deployment y la migración.

## Script

Archivo:

```text
scripts/staging2/verify-rendered-acceptance.mjs
```

Variables obligatorias:

```text
BASE_URL=https://staging2.nuvanx.com
EXPECTED_SHA=<SHA completo de 40 caracteres>
EVIDENCE_DIR=staging2-deployment-evidence/rendered-acceptance
```

El script se niega a consultar cualquier base distinta de `https://staging2.nuvanx.com`.

## Contrato de páginas

Debe obtener HTTP 200 sin redirección inesperada en:

| Ruta | H1 exacto | Marcador de contenido |
|---|---|---|
| `/tratamientos/` | `Portafolio clínico.` | `Áreas de intervención clínica` |
| `/protocolos-signature/` | `Protocolos Signature: Medicina estética de diagnóstico.` | `Nuestro estándar: La firma NUVANX` |
| `/remodelacion-corporal-laser-madrid/` | `Remodelación corporal láser diseñada según tu anatomía.` | `Couture Sculpt™: El protocolo y la tecnología` |
| `/por-que-nuvanx/` | `El diagnóstico precede a la indicación.` | `Diagnóstico antes de tecnología` |
| `/inversion-medicina-estetica/` | `El presupuesto forma parte de una decisión informada.` | `Qué incluye el precio` |

Cada página debe cumplir además:

- marker `nvx-deploy-sha` igual al SHA seleccionado;
- un único H1;
- `title`, meta description, `og:title` y `og:description` iguales a los valores versionados;
- `noindex,nofollow` en staging2;
- canonical o `og:url` con el mismo path sobre `staging2.nuvanx.com`, `nuvanx.com` o `www.nuvanx.com`;
- CTA hacia `/madrid/valoracion/`;
- JSON-LD válido;
- `WebPage` y organización médica en Schema;
- `ItemList` adicional en `/tratamientos/`;
- ausencia de prototipos, estados internos, placeholders y formulaciones clínicas o comerciales bloqueadas.

## Contrato SEO exacto

El catálogo canónico `inc/nvx-seo-metadata.php` debe resolver los valores siguientes. El gate compara también las equivalencias Open Graph.

| Ruta | Title exacto | Meta description exacta |
|---|---|---|
| `/tratamientos/` | Tratamientos Medicina Estética Láser Madrid \| NUVANX | Tratamientos de medicina estética láser en Madrid: Endolift®, Láser CO₂, EXION® BTL, IPL y medicina facial con valoración clínica. |
| `/protocolos-signature/` | Protocolos Signature \| NUVANX Madrid | Protocolos Signature de medicina estética en Madrid diseñados desde el diagnóstico anatómico, la indicación médica y el seguimiento individualizado. |
| `/remodelacion-corporal-laser-madrid/` | Remodelación corporal láser Madrid \| NUVANX | Remodelación corporal láser en Madrid por unidades anatómicas para grasa localizada, laxitud y continuidad del contorno tras valoración médica. |
| `/por-que-nuvanx/` | Por qué NUVANX \| Criterio médico en Madrid | Cómo decide NUVANX una indicación en medicina estética: valoración médica, información clara, seguimiento y centros sanitarios autorizados en Madrid. |
| `/inversion-medicina-estetica/` | Inversión en medicina estética \| NUVANX Madrid | Tarifas orientativas verificadas y cómo se confirma un presupuesto de medicina estética tras la valoración médica presencial en NUVANX Madrid. |

Esto impide que una página pase la aceptación con metadata genérica, antigua o heredada del estado previo de Yoast.

## Gobierno de contenido público

El contrato estático y el gate renderizado controlan conjuntamente:

```text
wp-content/themes/nuvanx-medical/inc/nvx-portfolio-hub.php
wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php
wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php
wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php
```

El contenido debe conservar:

- diagnóstico antes de tecnología;
- indicación y límites individualizados;
- expectativas realistas;
- selección según anatomía, diagnóstico y fototipo cuando corresponda;
- presupuesto documentado después de la valoración;
- seguimiento y derivación cuando estén indicados;
- promociones sin alterar la indicación ni generar urgencia comercial.

El gate bloquea, entre otras, estas categorías de formulación:

- garantía o promesa de resultados;
- absolutos de control, seguridad, discreción o recuperación;
- superioridad no demostrada como “estándar de oro”;
- comparaciones no verificables con otras clínicas o presupuestos;
- nombres de prototipos retirados y estados internos;
- afirmaciones incompatibles con la política comercial vigente.

## Contrato de redirects

Debe obtener exactamente HTTP 301 y `Location` absoluto de staging2:

```text
/liposculpt-air/
→ /remodelacion-corporal-laser-madrid/

/v-lift-awake/
→ /papada-definicion-mandibular-madrid/

/tratamiento-postparto-abdomen-contorno-corporal-madrid/
→ /protocolos-signature/
```

El gate comprueba también que el destino final responda HTTP 200. Un 404, 302, 307, redirect en cadena o destino diferente bloquea la aceptación.

## Evidencia

El artifact existente incorpora:

```text
staging2-deployment-evidence/run-context.txt
staging2-deployment-evidence/ssh-connectivity.log
staging2-deployment-evidence/preflight.log
staging2-deployment-evidence/remote-deploy.log
staging2-deployment-evidence/deployed-marker.log
staging2-deployment-evidence/independent-smoke.log
staging2-deployment-evidence/rendered-acceptance.log
staging2-deployment-evidence/postflight.log
staging2-deployment-evidence/rendered-acceptance/report.json
staging2-deployment-evidence/rendered-acceptance/tratamientos.html
staging2-deployment-evidence/rendered-acceptance/protocolos-signature.html
staging2-deployment-evidence/rendered-acceptance/remodelacion-corporal-laser-madrid.html
staging2-deployment-evidence/rendered-acceptance/por-que-nuvanx.html
staging2-deployment-evidence/rendered-acceptance/inversion-medicina-estetica.html
```

El HTML se conserva para demostrar qué respondió staging2 durante el run, no solo qué código estaba en GitHub.

## Resultado esperado

Un run `DEPLOY_AND_MIGRATE` aceptado debe emitir:

```text
STAGING2_PREFLIGHT_OK
DEPLOY_STAGING2_OK
SMOKE_VERIFY_OK
RENDERED_ACCEPTANCE_OK
Production-readiness audit passed.
```

En modo `PREFLIGHT_ONLY`, solo aplican la conectividad SSH, el diagnóstico remoto y `STAGING2_PREFLIGHT_OK`.

En modo `SMOKE_ONLY`, `DEPLOY_STAGING2_OK` no aplica, pero `STAGING2_PREFLIGHT_OK`, `SMOKE_VERIFY_OK` y `RENDERED_ACCEPTANCE_OK` siguen siendo obligatorios.

## Secuencia operativa

1. Mantener el PR abierto como draft y estabilizar su HEAD final.
2. Confirmar que los contratos estáticos están verdes sobre ese HEAD.
3. Ejecutar `PREFLIGHT_ONLY` sobre el mismo HEAD de la rama.
4. Revisar y descargar el artifact.
5. Ejecutar `DEPLOY_AND_MIGRATE` sobre exactamente el mismo HEAD.
6. Confirmar marker, auditoría, smoke y aceptación renderizada.
7. Completar QA visual manual en desktop y móvil.
8. Registrar el SHA aceptado y no añadir commits después de la validación.
9. Marcar el PR como ready y fusionar únicamente ese SHA ya validado.
10. Promover a producción solo el código equivalente al SHA aceptado mediante un procedimiento separado y protegido.
11. Repetir smoke, aceptación SEO y revisión de logs en producción.

## Gate de producción

La promoción permanece bloqueada cuando se cumple cualquiera de estas condiciones:

- staging2 sirve un SHA diferente;
- falta una página aprobada;
- un redirect retirado devuelve 404 o un destino distinto;
- existe más de un H1;
- title, description, Open Graph, canonical/OG o Schema no coinciden con el contrato;
- aparece contenido provisional, nomenclatura retirada o una formulación clínica/comercial bloqueada;
- la auditoría de migración no queda limpia;
- el artifact no está disponible o está incompleto;
- el QA visual no se ha completado;
- el candidato de producción no corresponde al estado validado.
