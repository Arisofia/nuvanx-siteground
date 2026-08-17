# Gate de contenido clínico — Endoláser corporal

**Estado:** pendiente de completar por la dirección médica y la persona responsable de operación/compliance.
**Ámbito:** `/endolaser-corporal-grasa-localizada/`, su catálogo de contenido, tarifas y `MedicalProcedure` canónico.
**Principio:** ninguna modificación de la página puede introducir, ampliar o hacer más específica una afirmación clínica, técnica, temporal, económica o de identidad profesional sin evidencia y una aprobación rastreable.

> Este documento es un control de gobernanza editorial. No valida indicaciones médicas, no sustituye la autorización sanitaria que pueda ser aplicable ni autoriza publicar contenidos.

## 1. Source of truth y límites de implementación

| Elemento | Fuente canónica | Regla |
|---|---|---|
| Contenido editorial | `wp-content/themes/nuvanx-medical/inc/data/endolaser-page.json` | No pegar HTML alternativo en WordPress; los cambios se versionan aquí. |
| Renderizado | `wp-content/themes/nuvanx-medical/inc/nvx-endolaser-page.php` | No crear un segundo propietario del cuerpo de página. |
| Ruta y SEO | `inc/data/routes.json` y `inc/data/seo-metadata.json` | Conservar `seo_id=endolaser`, `schema_id=endolaser_corporal` y canonical salvo PR específica aprobada. |
| Schema | `inc/nvx-structured-data.php` | Un único emisor. No añadir JSON-LD embebido, `performer` ni `recognizingAuthority`. |
| FAQ | `nvx_schema_faq_catalog()` y FAQ visible | El contenido visible y `FAQPage` deben coincidir literalmente. |
| Tarifas | `inc/data/tariff-catalog.json` | Prohibido hardcodear importes o usar claves inexistentes. |

## 2. Evidencia y aprobaciones requeridas

Antes de abrir una PR de contenido deben quedar registrados los seis bloques siguientes en el anexo de aprobación asociado al ticket/PR. La evidencia sensible, como IFU o documentos de proveedor, no se copia a Git si no es pública; se referencia por identificador y almacenamiento de acceso restringido.

| Bloque | Dato mínimo verificable | Aprobador | Afecta a |
|---|---|---|---|
| Equipo | Fabricante, modelo, denominación autorizada, IFU/ficha técnica y evidencia de que es el equipo usado por NUVANX. | Dirección médica + operaciones | Marca, mecanismo, longitudes de onda, técnica y schema. |
| Técnica | Aspiración/extracción o no, anestesia, ámbito asistencial, protocolo general y restricciones de indicación. | Dirección médica | Mecanismo, proceso, riesgos, recuperación y FAQ. |
| Claims | Fuente de soporte y redacción autorizada para sesiones, evolución, compresión, reincorporación, actividad física, resultados y límites. | Dirección médica + compliance | Todo texto clínico, meta descripción y schema. |
| Identidad | Nombre editorial autorizado, identidad estructurada, número ICOMEM y vínculo de la persona con el servicio. | Dirección médica + operaciones | Byline, página profesional y schema. |
| Tarifas | Alias contractual Endoláser ↔ Endolift corporal, o catálogo `endolaser.*` con PVP, vigencia y responsable. | Operaciones + dirección médica | Shortcodes, copy de precios y ofertas. |
| Taxonomía | Clasificación regulatoria/editorial: relación entre Endoláser corporal, Laserlipólisis, Endolift® corporal y cualquier marca/equipo. | Dirección médica + operaciones/compliance | Denominación de servicio, catálogo, copy y schema. |

## 3. Matriz de claims que actualmente requiere reconciliación

Los siguientes contenidos existentes se consideran `RECONCILIATION_REQUIRED`: no son declarados falsos por este gate, pero no tienen en Git una referencia a evidencia y aprobación específica para el equipo y protocolo actuales.

| Dominio | Ejemplos de contenido a reconciliar | Decisión permitida |
|---|---|---|
| Sesiones y tiempos | Una sesión, semanas de evolución y plazos máximos. | Mantener solo con fuente/protocolo aprobado; si no, hacer condicional o retirar. |
| Recuperación | Reincorporación, deporte y compresión con días/semanas concretos. | Alinear con protocolo real y variabilidad individual documentada. |
| Técnica | Anestesia, sedación, ecografía, fibra, lipólisis y aspiración. | Describir únicamente lo confirmado para NUVANX. |
| Resultado | Permanencia de adipocitos, reducción de volumen, retracción o firmeza. | Evitar garantías y absolutos; usar redacción respaldada y condicionada. |
| Equipo | Smartlipo, DEKA, Cynosure u otra marca/modelo. | No mencionar hasta confirmar fabricante y modelo. |
| Tarifas | El catálogo no define `endolaser.*`; los PVP corporales actualmente utilizados proceden de `endolift.*` y `endolift_combo.*`, con zonas y combinaciones corporales. | Documentar alias explícito o crear claves y PVP Endoláser propios. |
| Taxonomía | Endoláser corporal, Laserlipólisis, Endolift® corporal y posibles marcas/equipos aparecen como términos relacionados sin una equivalencia aprobada. | Aprobar la taxonomía comercial y clínica antes de reutilizar precios o claims. |

## 4. Regla de aprobación de PR

Una PR requiere un registro de aprobación completo solo cuando cambia una superficie Endoláser protegida. Un registro `APPROVED` debe vincular `approved_change.base`, `approved_change.head` y la huella de las proyecciones protegidas al cambio evaluado; no se puede reutilizar una aprobación previa con un retoque irrelevante del JSON. El gate compara los catálogos compartidos semánticamente para evitar que cambios de otros tratamientos queden bloqueados por error. Si no puede leer o clasificar de forma segura una superficie gobernada modificada, falla de forma cerrada.

| Archivo o superficie | Cuándo queda protegida |
|---|---|
| `inc/data/endolaser-page.json` | Cualquier cambio. |
| `inc/nvx-endolaser-page.php` | Cualquier cambio activa el gate, incluidos comentarios o documentación, para mantener un contrato fail-closed sobre el renderer dedicado. |
| `inc/data/routes.json` | Cambia la entrada de `/endolaser-corporal-grasa-localizada/` o una entrada asociada por `seo_id=endolaser`, `schema_id=endolaser_corporal`, canonical/ruta o `post_id`. |
| `inc/data/seo-metadata.json` | Cambia el registro `endolaser` o un registro cuyo `seo_id` sea `endolaser`. |
| `inc/data/tariff-catalog.json` | Cambia cualquier namespace `endolaser.*` o una clave corporal Endolift/Endolift combo declarada explícitamente en el contrato del gate. Los cambios de EXION, CO₂ u otros catálogos no relacionados no se bloquean. |
| `inc/nvx-structured-data.php` | Cambia la asignación o fallback FAQ de `endolaser_corporal`, el branch `MedicalProcedure/Service` de Endoláser, su entrada de ofertas/etiqueta u otro anclaje Endoláser reconocido. Los cambios exclusivos de FAQ/schema de otros tratamientos no se bloquean; un anclaje Endoláser nuevo no clasificable falla cerrado. |

La lista de claves tarifarias corporales protegidas se declara y se prueba en `scripts/lint/test-endolaser-claim-approval.mjs`; no se infiere mediante búsquedas globales ambiguas. El contrato semántico reproduce la estructura compartida de `nvx_schema_faq_catalog()` para demostrar que una modificación exclusiva de otra FAQ no activa el gate, mientras que una modificación de la FAQ Endoláser sí lo activa.

| Control | Criterio de aceptación |
|---|---|
| Evidencia | Cada claim nuevo o más específico se enlaza a un documento con propietario y fecha de revisión. |
| Aprobación | Dirección médica y operación/compliance confirman por escrito la versión final. |
| Identidad | El nombre editorial no contradice la identidad estructurada/colegiación verificable. |
| Tarifas | Cada precio proviene de una clave de catálogo existente y aprobada. |
| Taxonomía | La nomenclatura de servicio, técnica y marca coincide con la decisión regulatoria/editorial aprobada. |
| Schema | No se crean emisores paralelos; los datos visibles, FAQ y JSON-LD se mantienen coherentes. |
| Pruebas | Lint SEO/schema, validación de tarifas, prueba de FAQ visible/schema y Staging2 completos. |

## 5. Anexo de aprobación requerido por PR

```md
### Endoláser — registro de evidencia y aprobación

| Campo | Valor / referencia privada | Revisado por | Fecha |
|---|---|---|---|
| Fabricante, modelo e IFU del equipo |  |  |  |
| Técnica aplicada en NUVANX |  |  |  |
| Claims clínicos aprobados y soporte |  |  |  |
| Nombre editorial e identidad ICOMEM |  |  |  |
| Decisión tarifaria y vigencia |  |  |  |
| Clasificación taxonómica y denominación autorizada |  |  |  |

**Dirección médica:** nombre, fecha y aprobación explícita
**Operaciones/compliance:** nombre, fecha y aprobación explícita
```

## Referencias

[1] Real Decreto 1907/1996, criterios de veracidad y limitaciones de la publicidad con finalidad sanitaria: https://www.boe.es/buscar/act.php?id=BOE-A-1996-18085

[2] Comunidad de Madrid, publicidad de productos sanitarios dirigida al público: https://sede.comunidad.madrid/autorizaciones-licencias-permisos-carnes/publicidad-productos-sanitarios

[3] Contrato interno de schema: `scripts/lint/test-schema-semantic-contract.mjs`.
