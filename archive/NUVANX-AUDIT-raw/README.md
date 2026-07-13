# NUVANX-AUDIT

Esta carpeta contiene scripts y validaciones de auditoría para el tema NUVANX y los mu-plugins de SiteGround.

## Contenido clave
- Scripts para limpiar CSS heredado y validaciones de estilo.
- Validaciones de redirecciones y formularios HubSpot.
- Herramientas para inspeccionar el estado del tema y de los assets.

## Reglas de validación detectadas
- Evitar `!important` en CSS fuente salvo excepciones mínimas.
- Eliminar marcadores legacy como `Thermage`, `phase3c`, `legacy`, `old`, `patch`, `hotfix`.
- Evitar uso de `DOMDocument` y reescritura runtime del DOM.
- Mantener CSS fuente y minificado sincronizados.
