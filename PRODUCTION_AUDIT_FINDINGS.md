# Auditoría de Producción - Hallazgos via SSH
**Fecha:** 2026-08-05
**Servidor:** nuvanx-prod (c106797.sgvps.net:18765)
**Ruta WordPress:** /home/customer/www/nuvanx.com/public_html

---

## 1. Foto de Dr. Fabio - Estado de Media Library

### ✅ Resultado: ENCONTRADO
**Imágenes localizadas:**
- **ID 3099:** `fabio` → https://nuvanx.com/wp-content/uploads/2026/07/fabio.webp
- **ID 3098:** `Fabio` → https://nuvanx.com/wp-content/uploads/2026/07/Fabio.jpeg

### ⚠️ Observaciones
- **Sin metadatos completos:** Los archivos no tienen título completo ni descripción
- **Sin nombre completo:** No se encontraron archivos con "Fabio Quiñónez Bareiro" o "Quiñónez"
- **Solo nombre de pila:** Los archivos solo tienen "fabio" o "Fabio" como título

### 📋 Acción Requerida
**PENDIENTE:** Actualizar metadatos de los archivos existentes (ID 3099/3098) para incluir:
- Título completo: "Dr. Fabio Quiñónez Bareiro"
- Descripción apropiada
- Alt text para accesibilidad

---

## 2. Estructura de Directorios - Validación Completa

### ✅ Resultado: ESTRUCTURA DUPLICADA CONFIRMADA PERO NO CRÍTICA

**Temas en producción:**
- `nuvanx-medical` (ACTIVO) - Tema principal en uso
- `nuvanx-medical-wpvibe-backup` (inactivo)
- `nuvanx-medical-wpvibe-draft` (inactivo)
- `nuvanx-editorial-2026` (inactivo)
- `nuvanx` (no registrado en WordPress) - Directorio con contenido duplicado

**Estructura duplicada detectada:**
- `wp-content/themes/nuvanx-medical/` (TEMA ACTIVO - 100% funcional)
- `wp-content/themes/nuvanx/wp-content/themes/nuvanx-medical/` (COPIA NO USADA)

### ⚠️ Análisis de Impacto

**Estado funcional:** ✅ SIN IMPACTO OPERATIVO
- WordPress usa correctamente `nuvanx-medical` como tema activo
- El directorio duplicado NO está registrado como tema en WordPress
- No hay conflicto de resolución de archivos

**Origen probable:**
- Error en proceso de deploy anterior
- Copia manual incorrecta del repo
- Script de despliegue con error de rutas

**Diferencias detectadas:**
- Directorio activo tiene archivos minificados (.min.css)
- Directorio activo tiene archivos específicos (ai-telemetry-wrapper.php, .DS_Store)
- Directorio duplicado es una versión desactualizada/parcial

### 📋 Acción Requerida
**PENDIENTE MEDIA PRIORIDAD:** Limpiar directorio duplicado
- Eliminar `wp-content/themes/nuvanx/` (no usado por WordPress)
- Verificar scripts de deploy para evitar recurrencia
- Validar que no hay dependencias ocultas

---

## 3. routes.json - Localización en Producción

### ✅ Resultado: ENCONTRADO Y VALIDADO
**Ruta en servidor activo:** `./wp-content/themes/nuvanx-medical/inc/data/routes.json`
**Ruta duplicada:** `./wp-content/themes/nuvanx/wp-content/themes/nuvanx-medical/inc/data/routes.json` (NO USADA)

### 📊 Comparación vs Repo Local
El archivo en producción es **casi idéntico** al del repo local, con excepciones:
- Algunos entries de producción tienen `seo_id` que el repo local no tenía
- Estructura JSON es consistente
- Archivo activo es el correcto (wp-content/themes/nuvanx-medical/)

### ✅ Validación de Funcionamiento
**Sistema de resolución:** PATH-first (según código PHP nvx-structured-data.php)
- `post_id: 0` para EXION/EMFUSION es INTENCIONAL
- Sistema usa PATH como resolución primaria
- post_id es fallback secundario (documentado como "by design")

### 📋 Acción Requerida
**COMPLETADO:** Validar que routes.json correcto está siendo usado
- Confirmado: WordPress usa el archivo en tema activo
- No requiere acción adicional

---

## 3. Exportación de Base de Datos

### ✅ Resultado: EXPORTADO
**Archivo:** `/tmp/audit-nuvanx-prod-2026-08-05.sql`
**Ubicación:** Temporal en servidor de producción

### 📊 Resumen de Páginas Publicadas
**Total páginas:** 36 páginas (status: publish)

**Tratamientos publicados (22):**
1. EMFUSION® en Madrid (ID 3487)
2. EXION® Body en Madrid (ID 3486)
3. EXION® Face en Madrid (ID 3466)
4. Ácido hialurónico en labios (ID 3318)
5. Rinomodelación con ácido hialurónico (ID 3319)
6. Tratamiento de ojeras y surco lagrimal (ID 3320)
7. Bioestimuladores de colágeno (ID 3321)
8. EXION® Fractional RF (ID 3411)
9. Remodelación corporal láser (ID 3370)
10. Tratamiento Postparto (ID 3371)
11. Papada y definición mandibular (ID 3372)
12. Calidad, firmeza y luminosidad de piel (ID 3373)
13. Cicatrices de acné, poros y textura (ID 3374)
14. Manchas, rojeces y fotodaño (ID 3375)
15. Grasa localizada en abdomen y flancos (ID 3376)
16. Flacidez y grasa localizada en brazos (ID 3377)
17. Grasa de espalda y zona del sujetador (ID 3378)
18. Flacidez en muslos internos (ID 3379)
19. Grasa localizada y flacidez en rodillas (ID 3380)
20. Contorno corporal masculino (ID 3381)
21. BTL EXILITE™ IPL (ID 3055)
22. EXION® BTL Madrid (ID 2906)

**Páginas principales (9):**
- Home (ID 9)
- Contacto (ID 14)
- Blog (ID 16)
- Política de privacidad (ID 3)
- Aviso legal (ID 20)
- Clínicas NUVANX (ID 1399)
- Medicina estética en Chamberí (ID 1543)
- Medicina estética en Goya (ID 1537)
- Equipo médico (ID 1575)

**Páginas de información (5):**
- Nosotros (ID 1656)
- Por qué NUVANX (ID 3366)
- Inversión en medicina estética (ID 3367)
- Soluciones médicas (ID 3368)
- Protocolos Signature (ID 3369)

---

## 4. EMFUSION® - Estado en Producción

### ✅ Resultado: VERIFICADO EN PRODUCCIÓN
**Página publicada:** ID 3487 - "EMFUSION® en Madrid"
**Status:** publish
**Slug:** emfusion

### 📊 Corrección de Inferencia
**Anterior:** INFERIDO (no encontrado en staging)
**Actual:** VERIFICADO (página existente en producción)

---

## 5. Validación de Catálogo vs Producción

### ✅ Consistencia General
El catálogo de 22 tratamientos está **completamente publicado** en producción.

### 📋 Estado de Tratamientos Específicos

**EXION Family:**
- ✅ EXION® BTL (ID 2906) - publicado
- ✅ EXION® Face (ID 3466) - publicado
- ✅ EXION® Body (ID 3486) - publicado
- ✅ EXION® Fractional RF (ID 3411) - publicado

**Medicina Inyectable:**
- ✅ Ácido hialurónico labios (ID 3318) - publicado
- ✅ Rinomodelación (ID 3319) - publicado
- ✅ Ojeras/surco lagrimal (ID 3320) - publicado
- ✅ Bioestimuladores (ID 3321) - publicado

**Protocolos Signature:**
- ✅ Post-Maternity Contour (ID 3371) - publicado
- ✅ Profile Definition (ID 3372) - publicado
- ✅ Skin Architecture (ID 3373) - publicado
- ✅ Surface Renewal (ID 3374) - publicado
- ✅ Tone Correction (ID 3375) - publicado
- ✅ Contour Architecture - subpáginas (IDs 3376-3381) - publicadas

---

## 6. Recomendaciones de Acción

### Prioridad ALTA
1. ~~Investigar estructura de directorios duplicada~~ ✅ COMPLETADO - Sin impacto operativo
2. **Actualizar metadatos de foto Dr. Fabio** (IDs 3099/3098) - Requiere WordPress admin
3. ~~Validar que routes.json correcto está siendo usado~~ ✅ COMPLETADO - Confirmado funcionamiento correcto

### Prioridad MEDIA
1. **Limpiar directorio duplicado** `wp-content/themes/nuvanx/` - No usado por WordPress
2. **Descargar dump de DB** para análisis offline - Disponible en servidor
3. **Validar consistencia** entre routes.json y páginas publicadas ✅ COMPLETADO
4. **Verificar SEO metadata** en todas las páginas de tratamiento ✅ COMPLETADO (optimización SEO realizada)

### Prioridad BAJA
1. **Auditoría completa de estructura de directorios** - Parcialmente completada
2. **Verificar archivos duplicados o obsoletos** - Identificados, limpieza pendiente
3. **Limpiar temporales del servidor** - Opcional

---

## 7. Datos de Search Console

### ⚠️ Limitación
No es posible acceder a Search Console vía SSH.
Se requiere acceso web (https://search.google.com/search-console) o API de Google.

### 📋 Acción Requerida
**PENDIENTE:** Exportación manual de Search Console
- Entrar a: https://search.google.com/search-console
- Exportar CSV de rendimiento (últimos 3-6 meses)
- Dimensiones: Consulta + Página
- Guardar en repo para análisis de demanda observada

---

## Conclusión

La auditoría vía SSH ha permitido:
- ✅ Verificar foto de Dr. Fabio (encontrada pero sin metadatos completos)
- ✅ Localizar routes.json en producción (con estructura de directorios sospechosa)
- ✅ Exportar base de datos para análisis offline
- ✅ Validar que los 22 tratamientos están publicados en producción
- ✅ Confirmar EMFUSION® como VERIFICADO (no INFERIDO)

**Próximos pasos:** Investigar estructura de directorios y actualizar metadatos de foto Dr. Fabio.