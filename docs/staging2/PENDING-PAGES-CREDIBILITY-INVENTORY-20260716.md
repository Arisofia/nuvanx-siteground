# Inventario de páginas pendientes — credibilidad y clínica (staging2)

Fecha: 2026-07-16  
Ámbito: contenido WordPress en `https://staging2.nuvanx.com` (no es un cambio de tema/CSS).  
Estado: **borrador operativo** a partir de auditoría editorial + verificación HTTP de rutas clave.

## 1. Disposición cerrada (sin ambigüedad)

| ID | Rol | Acción |
|----|-----|--------|
| **1380** | Página privada (Medicina Estética Láser) | No publicar; no indexar; no enlazar. |
| **2635** | Duplicado **publicado** de Medicina Estética Láser | Retirar de publicación o redirigir al canónico público del hub láser. Una sola URL canónica. |
| **18** | Política de cookies antigua | Sustituida por versión UE **577**. Mantener 18 fuera de menús/sitemap; redirigir `/politica-de-cookies/` (o slug antiguo) → 577 si aún resuelve a 18. |

Estas tres dejan de formar parte del backlog de las 19 pendientes.

## 2. Las 19 páginas pendientes — mapa por bloque

Clases de fallo:

| Código | Clase |
|--------|--------|
| **C1** | Afirmación clínica incorrecta o confusión de modalidad |
| **C2** | Schema / JSON-LD duplicado o que describe hechos inexistentes |
| **C3** | Enlace roto o recurso técnico inexistente |
| **C4** | Página vacía / promesa sin contenido (bloqueo de credibilidad) |
| **C5** | Embudo de conversión ambiguo (varios CTAs sin prioridad) |
| **C6** | HTML roto, estilos inline, semántica inválida |
| **C7** | Duplicación improductiva de perfiles/sedes/tratamientos |
| **C8** | Roles clínicos vagos (sin titulación / alcance / responsabilidad) |
| **C9** | Mezcla de servicios sin separar indicación vs ejecución |
| **C10** | Certeza clínica (tiempos/sesiones/recuperación) sin fuente ni revisión |

### Bloque A — Fallos distintos ya tipificados

| # | Página (slug / título) | Fallos | Severidad | Verificación staging2 (2026-07-16) | Acción requerida |
|---|------------------------|--------|-----------|-------------------------------------|------------------|
| 1 | **BTL EXILITE™ IPL** (`/btl-exilite-ipl-madrid/`) | C1, C2 | Alta | HTTP 200; **2** bloques `application/ld+json`; claims clínicos densos en body | Purgar claims no respaldados; **un solo grafo schema** (Yoast `wpseo_schema_graph`, sin JSON-LD embebido en contenido). |
| 2 | **EXION® BTL** (`/exion-btl/`) | C3 | Media | HTTP 200; buscar guía técnica enlazada 404 | Quitar o sustituir el enlace a guía técnica inexistente; no dejar dead-ends en CTA secundarios. |
| 3 | **Casos de pacientes** (`/casos-de-pacientes/`) | C4, C2, C5, C6 | **Bloqueo** | HTTP 200; **`<main>` anidado** (`#nvx-main` + `#nvx-casos-pacientes`); CTA incompleto tipo «Solicitar»; texto «Presencial o vir…»; schema de casos sin casos reales | **No publicitar en menú/footer hasta tener ≥N casos con consentimiento.** Quitar schema de casos inexistentes; un solo `<main>`; CTAs completos; sin promesa virtual no operativa. |

### Bloque B — Embudo, CO₂ y Nosotros

| # | Página | Fallos | Severidad | Notas / verificación | Acción requerida |
|---|--------|--------|-----------|----------------------|------------------|
| 4 | **Valoración** (`/madrid/valoracion/`) | C5 | Alta | «gratuita» + lenguaje virtual + formulario + WhatsApp + dos sedes sin jerarquía clara | Una **prioridad única de conversión**: formulario clínico principal; WhatsApp y sedes como secundarios; eliminar «virtual» no verificada. |
| 5 | **Láser CO₂ fraccionado** | C5, C10 | Media–alta | Contenido clínico más maduro; captación casi solo WhatsApp; rangos temporales sin fuente/revisión | Mantener profundidad clínica; añadir formulario o CTA valoración canónico; pie de revisión (autor/revisor/fecha); fuentes o matiz de variabilidad. |
| 6 | **Nosotros** | C6, C8 | Alta | Estilos inline (~9); HTML frágil; cifra de reseñas desalineada con Doctoralia | Reparar markup; quitar inline styles; sincronizar reseñas con fuente pública o no publicar número. |

### Bloque C — Identidad local (duplicación)

| # | Página | Fallos | Severidad | Notas / verificación | Acción requerida |
|---|--------|--------|-----------|----------------------|------------------|
| 7 | **Equipo médico** | C7, C8, C6 | Alta | Inline styles (~11); colegiación presente en parte; roles de «especialistas» a menudo sin titulación/alcance | Un patrón de ficha: nombre, titulación, nº colegiado, sede, alcance asistencial, foto. Sin copy genérico reutilizable vacío. |
| 8 | **Chamberí** (`/medicina-estetica-chamberi/`) | C7, C6 | Media–alta | Repite perfiles/tratamientos del equipo | Página de **centro**: registro sanitario, dirección, horarios, mapa, equipo **asignado a esa sede**, CTA valoración. Enlazar tratamientos, no reescribir catálogo. |
| 9 | **Goya** (`/medicina-estetica-goya/` o alias barrio Salamanca) | C7, C6 | Media–alta | 200 en `/medicina-estetica-goya/`; evitar slug 404 | Igual que Chamberí; un solo slug canónico + redirect del resto. |

### Bloque D — Catálogo y error clínico grave

| # | Página | Fallos | Severidad | Notas / verificación | Acción requerida |
|---|--------|--------|-----------|----------------------|------------------|
| 10 | **Estética avanzada** | C9, C5 | Alta | Mezcla higiene/well-aging, depilación, hidrolipoclasia, IPL sin separar quién indica/ejecuta | Separar módulos por **nivel de responsabilidad** (médico vs complementario); no listar IPL/láser como “estética” sin marco médico. |
| 11 | **Hub Clínicas** (`/clinicas-de-medicina-estetica-nuvanx/`) | **C1** | **Crítica** | **Confirmado en HTML público:** tarjeta «Firmeza Endolift®» descrita como **«Radiofrecuencia monopolar para firmeza sin cirugía»** | Corregir copy ya: Endolift® ≠ radiofrecuencia monopolar. Texto de sustitución **aprobado por dirección clínica** (láser/endoláser subdérmico según valoración). |
| 12 | **Endoláser corporal** | C10, C5 | Alta | Contenido extenso; tiempos/sesiones/recuperación con certeza alta; doble WhatsApp sin form central | Suavizar certeza; añadir variabilidad + revisión; CTA valoración/formulario como primario. |

### Resto del backlog de 19 (a completar ID/slug en WP)

Completar en admin con post ID real. Placeholders de filas 13–19 según el inventario editorial previo (hubs, legal, contacto, home si aún lista, medicina estética no-láser, cookies canónica 577 solo validación de menú, etc.):

| # | Página (definir ID WP) | Fallos esperados | Severidad | Acción |
|---|------------------------|------------------|-----------|--------|
| 13 | Medicina estética (hub no láser / facial) | C9, C5 | Alta | Separar seguridad, límites, complicaciones; CTA única. |
| 14 | Endolift® facial (canónica) | C10, C1 | Alta | Profundizar selección anatómica, riesgos, límites; sin confusión RF. |
| 15 | Contacto | C5 | Media | Un WhatsApp canónico + form o valoración; dos sedes como datos, no como competidores de CTA. |
| 16 | Home | C5 | Media | Conversión no retrasada solo por vídeo + párrafos densos; action banner con prioridad clara. |
| 17 | Tratamientos (catálogo) | C9 | Alta | Matriz de decisión clínica, no muro de marcas. |
| 18 | Política cookies UE (577) | — | Baja | Verificar menú/footer apuntan a 577; 18 no indexable. |
| 19 | (Legal / privacidad / aviso si en cola) | C6 | Baja | Consistencia de enlaces y sin HTML legacy. |

> Si el recuento editorial de “19” usa otra lista de IDs, **sustituir filas 13–19** con esos IDs sin cambiar la lógica de bloques A–D ni la severidad de 1–12.

## 3. Prioridad de ejecución (orden cerrado)

1. **Crítico clínico** — Hub Clínicas: quitar «Endolift® = radiofrecuencia monopolar» (bloque D #11).  
2. **Bloqueo de credibilidad** — Casos de pacientes: despublicar o noindex + quitar de nav/footer hasta galería real; eliminar schema de casos inventados; un solo `<main>` (bloque A #3).  
3. **Duplicados / legal** — 2635 y 1380; cookies 18 → 577 (sección 1).  
4. **Embudo** — Valoración: una conversión primaria (bloque B #4).  
5. **EXILITE schema + claims** / **EXION enlace muerto** (bloque A #1–2).  
6. **Nosotros + Equipo/Sedes** — HTML, inline, roles, anti-duplicación (bloques B–C).  
7. **Estética avanzada + Endoláser + CO₂** — taxonomía, certeza, CTA (bloque D + B).  
8. Resto 13–19 y alineación Doctoralia (doc separada).

## 4. Qué NO hace este repositorio

- No inventar copy médico de sustitución sin aprobación clínica.  
- No mutar producción.  
- Scripts en `scripts/staging2/` solo staging2, dry-run por defecto; reportan Endolift/RF y virtual pero **no** reescriben claims clínicos.  
- Schema: integrar en grafo Yoast (`wpseo_schema_graph`), no JSON-LD suelto en el post_content.

## 5. Criterio de “hecho” por página

- [ ] Sin afirmación Endolift↔RF monopolar.  
- [ ] Sin schema que describa casos/servicios inexistentes.  
- [ ] Sin `<main>` anidado ni CTAs truncados.  
- [ ] Una prioridad de conversión por página.  
- [ ] Roles clínicos con titulación/colegiación/alcance donde se presenten profesionales.  
- [ ] Sin estilos inline de legado en body (salvo embeds necesarios).  
- [ ] Un slug canónico por sede/tratamiento; redirects del resto.

## 6. Relación con docs existentes

- [CONTENT-NAVIGATION-CLEANUP-20260716.md](./CONTENT-NAVIGATION-CLEANUP-20260716.md) — correcciones deterministas + blockers semánticos.  
- [CONTENT-CLEANUP-EXECUTION-CHECKLIST-20260716.md](./CONTENT-CLEANUP-EXECUTION-CHECKLIST-20260716.md) — orden deploy → audit → apply.  
- [DOCTORALIA-ALIGNMENT-20260716.md](./DOCTORALIA-ALIGNMENT-20260716.md) — reseñas / consulta online externa.

Este inventario **no autoriza** producción. Solo ordena el trabajo de contenido en staging2.
