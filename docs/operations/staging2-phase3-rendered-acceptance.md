# Staging2 rendered acceptance — Phase 3

## Estado de partida

La arquitectura de production readiness y el workflow protegido ya están fusionados en `master`, pero el estado renderizado de staging2 no constituye todavía una validación del código actual.

La auditoría conectada previa a esta fase confirmó:

- staging2 publicaba el marker `ddd583823b39f5698bae9eb630ed18dd86408873`, distinto del `master` auditado;
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
| `/tratamientos/` | `Portafolio Clínico.` | `Áreas de intervención clínica` |
| `/protocolos-signature/` | `Protocolos Signature: Medicina estética de diagnóstico.` | `Nuestro estándar: La firma NUVANX` |
| `/remodelacion-corporal-laser-madrid/` | `Remodelación corporal láser diseñada según tu anatomía.` | `Couture Sculpt™: El protocolo y la tecnología` |
| `/por-que-nuvanx/` | `El diagnóstico precede a la indicación.` | `Diagnóstico antes de tecnología` |
| `/inversion-medicina-estetica/` | `El presupuesto forma parte de una decisión informada.` | `Qué incluye el precio` |

Cada página debe cumplir además:

- marker `nvx-deploy-sha` igual al SHA seleccionado;
- un único H1;
- title válido y distinto de una página 404;
- meta description presente;
- `noindex,nofollow` en staging2;
- canonical o `og:url` apuntando a la URL homóloga de producción;
- CTA hacia `/madrid/valoracion/`;
- JSON-LD válido;
- `WebPage` y organización médica en Schema;
- `ItemList` adicional en `/tratamientos/`;
- ausencia de prototipos, estados internos y placeholders.

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

Un 404, 302, 307, redirect en cadena o destino diferente bloquea la aceptación.

## Evidencia

El artifact existente incorpora:

```text
staging2-deployment-evidence/rendered-acceptance.log
staging2-deployment-evidence/rendered-acceptance/report.json
staging2-deployment-evidence/rendered-acceptance/tratamientos.html
staging2-deployment-evidence/rendered-acceptance/protocolos-signature.html
staging2-deployment-evidence/rendered-acceptance/remodelacion-corporal-laser-madrid.html
staging2-deployment-evidence/rendered-acceptance/por-que-nuvanx.html
staging2-deployment-evidence/rendered-acceptance/inversion-medicina-estetica.html
```

El HTML se conserva para demostrar qué respondió staging2 durante el run, no solo qué código estaba en GitHub.

## Resultado esperado

Un run aceptado debe emitir:

```text
STAGING2_PREFLIGHT_OK
DEPLOY_STAGING2_OK
SMOKE_VERIFY_OK
RENDERED_ACCEPTANCE_OK
Production-readiness audit passed.
```

En modo `SMOKE_ONLY`, `DEPLOY_STAGING2_OK` no aplica, pero `STAGING2_PREFLIGHT_OK`, `SMOKE_VERIFY_OK` y `RENDERED_ACCEPTANCE_OK` siguen siendo obligatorios.

## Secuencia operativa

1. Fusionar esta fase solo después de que su contrato estático esté verde.
2. Ejecutar `PREFLIGHT_ONLY` sobre el SHA exacto seleccionado.
3. Revisar y descargar el artifact.
4. Ejecutar `DEPLOY_AND_MIGRATE` sobre el mismo SHA.
5. Confirmar marker, auditoría, smoke y aceptación renderizada.
6. Completar QA visual manual en desktop y móvil.
7. Registrar el SHA aceptado y no añadir commits después de la validación.
8. Promover a producción únicamente el código equivalente al SHA aceptado.
9. Repetir smoke, aceptación SEO y revisión de logs en producción con un procedimiento separado y protegido.

## Gate de producción

La promoción permanece bloqueada cuando se cumple cualquiera de estas condiciones:

- staging2 sirve un SHA diferente;
- falta una página aprobada;
- un redirect retirado devuelve 404;
- existe más de un H1;
- faltan title, description, canonical/OG o Schema;
- aparece contenido provisional o nomenclatura retirada;
- el artifact no está disponible;
- el QA visual no se ha completado;
- el candidato de producción no corresponde al estado validado.
