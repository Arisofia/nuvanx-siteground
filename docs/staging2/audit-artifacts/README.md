# Auditoría maestra staging2 — 24 páginas

**Fecha:** 2026-07-16  
**Decisión global:** **NO GO para producción** · **GO para corrección controlada en staging2**

## Archivos

| Archivo | Uso |
|---------|-----|
| `nuvanx_staging2_auditoria_maestra_24_paginas.xml` | Informe completo: 24 `page_audit` numeradas 1–24, hallazgos, transformaciones, schema, HTML de módulo, FAQs, P0–P2 |
| `nuvanx_staging2_modulos_paginas_06_24.html` | Módulos HTML de transformación (páginas 06–24) + Action Banner |
| `nuvanx_staging2_componentes_canonicos_24_paginas.css` | CSS de módulos (tokens 8px); integrar solo tras revisión |
| `nuvanx_staging2_higiene_canonica_paginas.php` | Copia de referencia; **runtime en tema:** `inc/nvx-page-hygiene.php` |

## Bloqueos críticos (P0)

1. **Clínicas:** Endolift® descrito como radiofrecuencia monopolar → corregir en DB + caché.  
2. **EXILITE:** JSON-LD independiente (clínicas, coordenadas, precio, FAQ) duplica el grafo Yoast.  
3. **Casos de pacientes:** galería vacía + schema falso → borrador / noindex.  
4. **Privacidad:** responsable, salud, HubSpot, encargados, transferencias, conservación.  
5. **Aviso legal:** LSSI art. 10 (identidad, NIF, registro).  
6. **Contacto vs Valoración:** embudo duplicado.  
7. **8px:** estilos inline / Gutenberg fuera de tokens.  
8. **Equipo:** «especialistas» sin titulación / colegiación / alcance.

## Schema y staging2

- PR **#15** fusionado en master (`35b8bdc1`, 2026-07-16).  
- Grafo canónico en código: organización, Chamberí, Goya, Endolift®, EXION®.  
- Extensión EXILITE + strip de JSON-LD embebido: PR de tema (pendiente de merge/deploy).  
- **No hay** ejecución de workflow ligada al merge del PR 15 → **master ≠ staging2 desplegado**.

## Higiene en tema (`nvx-page-hygiene.php`)

- 301 cookies antiguas **18 / 31 → 577**  
- `noindex, nofollow` página **78** (solicitud recibida)  
- `noindex, follow` **2645** hasta `_nvx_cases_publication_ready=1`  
- Exclusión de sitemap Yoast cuando corresponda  

## Orden de corrección en staging2

1. Privacidad y Aviso legal  
2. Error Endolift® / RF en hub Clínicas  
3. JSON-LD duplicado EXILITE  
4. Retirada de Casos de pacientes  
5. Separación Contacto / Valoración  
6. Credenciales y funciones del equipo  
7. Inline styles y URLs absolutas  
8. Revisión clínica de claims  
9. QA sobre staging2 **realmente desplegado**  

## Cookies

La política debe ofrecer acceso permanente a la gestión del consentimiento: aceptar, rechazar y configurar de forma granular.
