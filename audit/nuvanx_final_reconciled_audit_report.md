# Informe Final de Auditoría Técnica Reconciliada: Producción vs Staging2 (NUVANX)

**Fecha:** 2026-08-04
**Alcance:** 51 URLs (C01–C51) del inventario de NUVANX
**Objetivo:** Resolver contradicciones técnicas, validar estados reales de página y reconciliar la auditoría con la lógica interna del código de NUVANX para identificar brechas de contenido (Gap Real) y discrepancias de despliegue (Drift).

## 1. Resumen Ejecutivo y Hallazgos Clave

La auditoría comparativa entre Producción y Staging2 ha revelado una complejidad significativa debido a la interacción de un **SiteGround Robot Challenge** en el entorno de producción, que enmascaraba los estados HTTP reales de las páginas para las herramientas de auditoría automatizadas. Tras una validación manual en navegador y la reconciliación con el código base, se han resuelto las contradicciones y se han identificado las siguientes brechas y acciones prioritarias:

| Hallazgo | Descripción | Impacto | Clasificación Final |
| :--- | :--- | :--- | :--- |
| **SiteGround Robot Challenge** | Un reto de seguridad (estado 202) intercepta las peticiones automatizadas en Producción, impidiendo el acceso al contenido real de la página. | Contamina las auditorías automatizadas, afecta la indexación de Google. | **Problema de Infraestructura** |
| **`/tratamientos/` (Producción)** | Devuelve un **404 real** en navegador, a pesar de que las herramientas automatizadas reportaban un 202 (Robot Challenge). | La página principal de tratamientos no es accesible para usuarios reales. | **Gap Real de Contenido/Configuración** |
| **`exion-body/` (Producción)** | Devuelve un **404 real** en navegador, mientras que en Staging2 está **200 OK**. El código confirma que debe ser pública. | Contenido clave de un tratamiento no disponible en Producción. | **Drift de Contenido (Gap Real)** |
| **`emfusion/` (Producción)** | Devuelve un **404 real** en navegador, mientras que en Staging2 está **200 OK**. El código confirma que debe ser pública. | Contenido clave de un tratamiento no disponible en Producción. | **Drift de Contenido (Gap Real)** |
| **`exion-btl/` (Producción)** | **200 OK** en navegador, mostrando contenido. Las auditorías automatizadas lo reportaban como 404/202 (Robot Challenge). | Falso positivo de gap. La página existe y es accesible. | **Falso Positivo (Resuelto)** |
| **`/madrid/valoracion/`** | Página gestionada intencionalmente, no un gap. El H1 no era visible para bots sin ejecutar filtros. | Falso positivo de gap. | **Falso Positivo (Resuelto)** |
| **`/casos-de-pacientes/`** | Force-404 intencional por código hasta publicación. | Falso positivo de gap. | **Falso Positivo (Resuelto)** |
| **Drift H1 (Home)** | El H1 de la página de inicio difiere entre Producción y Staging2. | Inconsistencia menor de copy. | **Drift de Contenido (Menor)** |
| **Robots Staging** | `noindex, nofollow` en Staging2 es correcto por diseño. | Comportamiento esperado. | **Correcto por Diseño** |

## 2. Acciones Prioritarias Recomendadas

Las siguientes acciones se priorizan en función de su impacto y la resolución de problemas raíz:

1.  **Resolver el SiteGround Robot Challenge en Producción:**
    *   **Acción:** Investigar y configurar el panel de SiteGround (WAF/anti-bot) para evitar que el reto de seguridad intercepte a los crawlers legítimos (incluido Googlebot) y a las herramientas de auditoría. Este es el problema raíz que contamina la visibilidad de todas las páginas para bots.
    *   **Justificación:** Sin resolver esto, ninguna auditoría automatizada será fiable y la indexación orgánica puede verse comprometida.

2.  **Corregir `/tratamientos/` en Producción:**
    *   **Acción:** Investigar por qué `/tratamientos/` devuelve un 404 real en Producción (visto en navegador), a pesar de que en Staging2 está operativa. Esto podría ser un problema de contenido, configuración de WordPress o redirección.
    *   **Justificación:** Es una página pilar de navegación y su inaccesibilidad afecta la experiencia del usuario y la estructura del sitio.

3.  **Migrar `exion-body/` a Producción:**
    *   **Acción:** Desplegar la página `exion-body/` de Staging2 a Producción.
    *   **Justificación:** Es un gap de contenido real confirmado (404 en Prod, 200 en Staging2) y el código base confirma que es una página diseñada para ser pública.

4.  **Migrar `emfusion/` a Producción:**
    *   **Acción:** Desplegar la página `emfusion/` de Staging2 a Producción.
    *   **Justificación:** Es un gap de contenido real confirmado (404 en Prod, 200 en Staging2) y el código base confirma que es una página diseñada para ser pública.

5.  **Re-auditar Contenido Detallado (H1, Precios, FAQ, Schema):**
    *   **Acción:** Una vez resuelto el SiteGround Robot Challenge, realizar una nueva auditoría exhaustiva (idealmente desde un navegador real con sesión humana o una herramienta que pueda bypassar el challenge) para obtener datos fiables de H1, precios, FAQ y schema en todas las URLs.
    *   **Justificación:** Los datos de contenido extraídos por las herramientas automatizadas estaban contaminados por el Robot Challenge, impidiendo un análisis preciso de estos elementos.

## 3. Conclusiones sobre Contradicciones y Falsos Positivos

*   **Contradicción `exion-btl/` resuelta:** La página existe y es accesible en Producción. El `404` reportado previamente era una interpretación errónea del Robot Challenge.
*   **Falsos Positivos de Gaps:** Las páginas `/madrid/valoracion/` y `/casos-de-pacientes/` no son gaps de contenido, sino que están gestionadas intencionalmente por el código con lógicas específicas (redirecciones canónicas, force-404 condicional, etc.).

## 4. Referencias

*   [1] `pasted_content.txt`: Encargo inicial de auditoría de catálogo.
*   [2] `pasted_content_2.txt`: Solicitud de ampliación de auditoría a Staging2 y comparación.
*   [3] `pasted_content_3.txt`: Análisis del código base y refutación de falsos positivos.
*   [4] `pasted_content_4.txt`: Confirmación de estado público de EXION/EMFUSION y reordenación de prioridades.
*   [5] `nuvanx_drift_final.csv`: Archivo CSV con la auditoría técnica comparativa completa (Producción vs Staging2).
*   [6] `audit_comprehensive.py`: Script Python utilizado para la auditoría técnica.

---
**Autor:** Manus AI
