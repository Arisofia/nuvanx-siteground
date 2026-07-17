# Registro de Activos para Producción — Estado Real
# PENDING-ASSETS-REGISTRY-20260717.md

**Fecha de apertura:** 2026-07-17  
**Última revisión:** 2026-07-17 (rellenado con datos de archivos locales)  
**Estado:** 🟡 PARCIAL — Bloques 1–3 y 5 parcialmente completos con datos existentes en el repo. Bloques 6–8 siguen sin activos físicos.

> Los datos marcados como `✅ EN REPO` fueron extraídos automáticamente de los archivos PHP/JSON del repositorio.  
> Los marcados como `🔴 PENDIENTE` requieren que alguien del equipo entregue el dato real.

---

## BLOQUE 1 — Google Business Profile (GBP)

| # | Activo | Estado | Valor encontrado | Notas |
|---|--------|--------|------------------|-------|
| 1.1 | **Place ID — Chamberí** | 🔴 PENDIENTE | — | No encontrado. El código usa `hasMap` genérico con búsqueda, no Place ID real |
| 1.2 | **URL directa GBP — Chamberí** | 🔴 PENDIENTE | — | Placeholder `[GOOGLE_REVIEW_CHAMBERI_URL]` en `google-business-profile-review-activation.md` |
| 1.3 | **Place ID — Goya** | 🔴 PENDIENTE | — | No encontrado. El código usa `hasMap` genérico con búsqueda, no Place ID real |
| 1.4 | **URL directa GBP — Goya** | 🔴 PENDIENTE | — | Placeholder `[GOOGLE_REVIEW_GOYA_URL]` en `google-business-profile-review-activation.md` |
| 1.5 | **Coordenadas — Chamberí** | 🔴 PENDIENTE | Dirección: `C/ Fernández de la Hoz 4, Bajo Derecha, 28010 Madrid` | Coordenadas Lat/Lon no están en ningún archivo del repo |
| 1.6 | **Coordenadas — Goya** | 🔴 PENDIENTE | Dirección: `C/ Fernán González 26, 28009 Madrid` | Coordenadas Lat/Lon no están en ningún archivo del repo |

**Acción necesaria:** Entrar a [business.google.com](https://business.google.com), copiar el enlace de reseña de cada sede y ejecutar:
```bash
wp option update nvx_google_review_chamberi_url 'PEGAR_LINK_REAL'
wp option update nvx_google_review_goya_url 'PEGAR_LINK_REAL'
```

---

## BLOQUE 2 — Datos Operativos de Sedes

| # | Activo | Estado | Valor encontrado | Fuente |
|---|--------|--------|------------------|--------|
| 2.1 | **Teléfono — Chamberí** | ✅ EN REPO | `+34 669 319 836` | `nvx-structured-data.php:495`, `zzzzzzzzzz-geo-entity-bridge.php:139` |
| 2.2 | **Teléfono — Goya** | ✅ EN REPO | `+34 647 505 107` | `nvx-structured-data.php:536`, `zzzzzzzzzz-geo-entity-bridge.php:162` |
| 2.3 | **Horario — Chamberí** | ✅ EN REPO | L-V `12:00–20:00` / Sábado `10:00–18:00` | `nvx-structured-data.php:512–524` |
| 2.4 | **Horario — Goya** | ✅ EN REPO | L-V `11:00–20:00` (sin sábado registrado) | `nvx-structured-data.php:553–560` — ⚠️ verificar si Goya abre sábados |
| 2.5 | **Nº Registro sanitario — Chamberí** | ✅ EN REPO | `CS20144` | `nvx-structured-data.php:508` |
| 2.6 | **Nº Registro sanitario — Goya** | ✅ EN REPO | `CS20073` | `nvx-structured-data.php:549` |

> ⚠️ **Verificar** que los horarios en el código coinciden con los horarios reales actuales antes de producción.

---

## BLOQUE 3 — Documentación Técnica de Equipos (CE / IFU)

> Los documentos CE e IFU **no se suben al repositorio público**. Se depositan en carpeta privada.

| # | Equipo | Activo | Estado | Notas |
|---|--------|--------|--------|-------|
| 3.1 | **BTL EXION®** | Certificado CE | 🔴 PENDIENTE | No hay ningún archivo en el repo |
| 3.2 | **BTL EXION®** | IFU español | 🔴 PENDIENTE | No hay ningún archivo en el repo |
| 3.3 | **BTL EMFUSION®** | Certificado CE | 🔴 PENDIENTE | No hay ningún archivo en el repo |
| 3.4 | **BTL EMFUSION®** | IFU español | 🔴 PENDIENTE | No hay ningún archivo en el repo |
| 3.5 | **BTL EXILITE™ IPL** | Certificado CE | 🔴 PENDIENTE | No hay ningún archivo en el repo |
| 3.6 | **BTL EXILITE™ IPL** | IFU español | 🔴 PENDIENTE | No hay ningún archivo en el repo |

---

## BLOQUE 4 — Aprobación Médica de Contenido Clínico

| # | Contenido | Estado | Notas |
|---|-----------|--------|-------|
| 4.1 | Tiempos de recuperación — Thermage FLX | 🔴 PENDIENTE | Ver `PENDING-PAGES-CREDIBILITY-INVENTORY-20260716.md` Bloque D |
| 4.2 | Tiempos de recuperación — Endolift® | 🔴 PENDIENTE | Ídem |
| 4.3 | Tiempos de recuperación — Láser CO₂ | 🔴 PENDIENTE | Ídem |
| 4.4 | Fibras en Endolift® — tipo y calibre publicables | 🔴 PENDIENTE | Requiere aprobación Dr. Rivera Tejeda |
| 4.5 | Indicaciones — EXION, EMFUSION, EXILITE | 🔴 PENDIENTE | Gobernanza BTL activa (`nvx-btl-clinical-governance.php`) — pendiente validación final |
| 4.6 | Comparativas entre tratamientos | 🔴 PENDIENTE | Prohibido publicar sin aprobación expresa |
| 4.7 | **Corrección Endolift® ≠ RF monopolar** (Hub Clínicas) | 🔴 PENDIENTE CRÍTICO | Copy de sustitución aprobado por dirección médica — acción P0 |

---

## BLOQUE 5 — Credenciales del Equipo Médico

| # | Activo | Estado | Valor encontrado | Fuente |
|---|--------|--------|------------------|--------|
| 5.1 | **Nº colegiado — Dr. José Javier Rivera Tejeda** | ✅ EN REPO | `ICOMEM 282864786` | `nvx-equipo-page.php:60`, constante `NVX_DIRECTOR_COLEGIADO` |
| 5.2 | **Nº colegiado — Dra. Ivon Yamileth Rivera Deras** | ✅ EN REPO | `ICOMEM 284621525` | `nvx-equipo-page.php:61`, constante `NVX_IVON_COLEGIADO` |
| 5.3 | **Nº colegiado — Dr. Fabio Augusto Quiñónez Bareiro** | ✅ EN REPO | `ICOMEM 282877543` | `nvx-equipo-page.php:62`, constante `NVX_FABIO_COLEGIADO` |
| 5.4 | **Especialidad MIR / título — Dr. Rivera Tejeda** | ✅ EN REPO | `Médico estético`, especialidades: Medicina estética, Medicina estética láser, Tricología, Medicina capilar | `geo-entity-bridge.php:186–191` |
| 5.5 | **Doctoralia — Dr. Rivera Tejeda** | ✅ EN REPO | `https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid` | `geo-entity-bridge.php:195` |
| 5.6 | **Sociedades médicas** | 🔴 PENDIENTE | No aparece en ningún archivo del repo | Requiere que el Dr. Rivera facilite los nombres y nº de socio |
| 5.7 | **Fotografía profesional — Dr. Rivera Tejeda** | 🔴 PENDIENTE | No hay ruta a fotografía verificada en el repo | Requiere foto de alta resolución con autorización |

---

## BLOQUE 6 — Casos Clínicos (`/casos-de-pacientes/`)

> ⚠️ **Página bajo noindex** hasta que todos los ítems de este bloque estén `✅`. Referencia: `P0-FINISH-RUNBOOK.md` (Casos 2645).

| # | Activo | Estado | Notas |
|---|--------|--------|-------|
| 6.1–6.3 | Fotografías antes/después (mín. 3 casos) | 🔴 PENDIENTE | No hay ningún archivo de imagen en el repo |
| 6.4 | Consentimiento informado firmado (por cada caso) | 🔴 PENDIENTE | Documento legal; no va al repo público |
| 6.5 | Expediente clínico anonimizado (por cada caso) | 🔴 PENDIENTE | Documento clínico; no va al repo público |

---

## BLOQUE 7 — Activos Multimedia

| # | Activo | Estado | Notas |
|---|--------|--------|-------|
| 7.1 | **Vídeo Hero** `.mp4` H.264 | 🔴 PENDIENTE | No encontrado en el repo |
| 7.2 | **Poster/Thumbnail** `.webp` o `.jpg` | 🔴 PENDIENTE | No encontrado en el repo |
| 7.3 | **Vídeo móvil** (vertical/recortado) | 🔴 PENDIENTE | No encontrado en el repo |
| 7.4 | **Fotos institucionales — Chamberí** | 🔴 PENDIENTE | Mín. 5 fotos high-res con derechos cedidos |
| 7.5 | **Fotos institucionales — Goya** | 🔴 PENDIENTE | Mín. 5 fotos high-res con derechos cedidos |

---

## BLOQUE 8 — Analytics y SEO

| # | Activo | Estado | Notas |
|---|--------|--------|-------|
| 8.1 | **Search Console** — propiedad `nuvanx.com` verificada | 🔴 PENDIENTE | Archivo `googlee8160480bf01506f.html` existe en raíz del repo → verificar si está activo en prod |
| 8.2 | **Capturas Search Console** — últimos 28 días | 🔴 PENDIENTE | Exportar desde console.google.com |
| 8.3 | **GA4** — Property ID y flujo activo | 🔴 PENDIENTE | No encontrado ID de GA4 en el repo |
| 8.4 | **Core Web Vitals** — PageSpeed staging2 | 🔴 PENDIENTE | Ejecutar: `https://pagespeed.web.dev/analysis?url=https://staging2.nuvanx.com/` |
| 8.5 | **Score móvil ≥80** — Home staging2 | 🔴 PENDIENTE | Requerido antes del GO a producción |

---

## Tablero de Estado

| Bloque | Total | ✅ Completo | 🟡 Parcial | 🔴 Pendiente |
|--------|-------|------------|-----------|--------------|
| 1 — GBP Place IDs y URLs | 6 | 0 | 2 (dirección conocida) | 4 |
| 2 — Datos operativos | 6 | 5 | 1 (horario Goya sábado) | 0 |
| 3 — CE / IFU | 6 | 0 | 0 | 6 |
| 4 — Aprobación médica | 7 | 0 | 0 | 7 |
| 5 — Credenciales equipo | 7 | 5 | 0 | 2 |
| 6 — Casos clínicos | 5 | 0 | 0 | 5 |
| 7 — Multimedia | 5 | 0 | 0 | 5 |
| 8 — Analytics y SEO | 5 | 0 | 1 (archivo GSC existe) | 4 |
| **TOTAL** | **47** | **10** | **4** | **33** |

**Estado global: 🟡 NO GO — 10/47 resueltos**

---

## Próximas Acciones (por prioridad)

1. **CRÍTICO** — `BLOQUE 4 #4.7`: Aprobar copy de sustitución Endolift ≠ RF monopolar. Acción P0 bloqueante.
2. **URGENTE** — `BLOQUE 1`: Obtener Place IDs y URLs de reseña GBP desde business.google.com.
3. **URGENTE** — `BLOQUE 1 #1.5–1.6`: Confirmar coordenadas exactas de ambas sedes.
4. **URGENTE** — `BLOQUE 2 #2.4`: Confirmar si Goya abre sábados y con qué horario.
5. **ALTA** — `BLOQUE 5 #5.6–5.7`: Sociedades médicas y foto del Dr. Rivera Tejeda.
6. **ALTA** — `BLOQUE 8`: Confirmar GA4 Property ID y ejecutar PageSpeed en staging2.
7. **MEDIA** — `BLOQUE 3`: Solicitar CE e IFU a distribuidora BTL y archivar en carpeta privada.
8. **MEDIA** — `BLOQUE 7`: Entregar activos de vídeo y fotos institucionales.
9. **BAJA** — `BLOQUE 6`: Preparar casos clínicos con consentimientos cuando estén listos.
