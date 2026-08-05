# Informe de Auditoría Comparativa: Producción vs Staging2 (NUVANX)
**Fecha:** 2026-08-04
**Alcance:** 51 URLs (C01–C51)
**Objetivo:** Identificar brechas de contenido (Gap Real) y discrepancias de despliegue (Drift).

## 1. Resumen Ejecutivo de Drift
Se han detectado inconsistencias críticas que sugieren un bloqueo en la cadena de despliegue o configuraciones de seguridad divergentes.

| Entidad Crítica | Producción | Staging2 | Clasificación |
| :--- | :--- | :--- | :--- |
| `/tratamientos/` | **Robot Challenge (202/404)** | **OK (200)** | **Drift de Despliegue** |
| `exion-body/` | **404** | **OK (200)** | **Drift de Contenido** |
| `emfusion/` | **404** | **OK (200)** | **Drift de Contenido** |
| `exion-btl/` | **404** | **OK (200)** | **Drift de Contenido** |

> **Nota Crítica:** La URL `/tratamientos/` en producción está interceptada por un reto de seguridad (SiteGround Robot Challenge), lo que impide la navegación normal del usuario y la indexación, mientras que en Staging2 muestra la arquitectura completa de soluciones.

## 2. Análisis de Brechas (Gap Tipo)
Basado en el barrido técnico de las 51 rutas identificadas en el tema `nuvanx-medical`.

### A. Drift de Despliegue (En Staging2, falta en Prod)
Estas páginas están listas para ser publicadas pero no son accesibles en el entorno real:
1. `/tratamientos/` (Página pilar de navegación)
2. `/exion-body/`
3. `/emfusion/`
4. `/exion-btl/`
5. `/exion-face/`
6. `/exion-fractional/`

### B. Gap Real (Faltan en ambos o inconsistentes)
Páginas que, aunque existen en las rutas del tema, no devuelven contenido válido o H1 consistente en ninguno de los dos entornos:
* `/madrid/valoracion/` (Posible redirección mal configurada o borrador).
* `/casos-de-pacientes/` (Estructura presente pero sin datos médicos verificables).

## 3. Comparativa Técnica de Entidades (Top Oportunidades)

| Catalogue Key | Tratamiento | H1 (Staging2) | Precio Visible | FAQ Schema | Doctor Asociado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| C16 | Endolift Facial | Endolift® Facial y Cuello | N/D | Sí | Dr. Rivera |
| C17 | Endoláser Corporal | Endoláser® Corporal | N/D | Sí | Dr. Rivera |
| C19 | EXION BTL | EXION™: La nueva era... | N/D | No | N/D |
| C22 | EXION Body | Remodelación Corporal EXION | N/D | No | N/D |
| C23 | EMFUSION | EMFUSION™: Definición... | N/D | No | N/D |

## 4. Acciones Recomendadas
1. **Resolver Bloqueo de Producción:** Investigar por qué `/tratamientos/` activa el Robot Challenge en producción.
2. **Sincronizar Catálogo EXION:** Realizar el despliegue de las 4 nuevas páginas de la familia EXION y EMFUSION que ya están validadas en Staging2.
3. **Mapeo T01-T22:** Sigue marcado como **PENDIENTE** al no disponer del inventario histórico.

---
*Evidencia generada mediante auditoría técnica automatizada y verificación manual vía navegador en Sandbox.*
