# GEO + SEO competitivo NUVANX — qué implementamos

Rama: `feat/geo-seo-competitive-authority` (separada de layout).

Posicionamiento: **autoridad láser transparente** — no Depilife (descuentos) ni hermetismo de élite; sí precios citables + entidad médica + E-E-A-T del Dr. Rivera, en tono clínico (no dump infomercial del brief).

## Implementado en theme (Yoast `@graph` only)

| Señal competitiva | Cómo |
|-------------------|------|
| Opacidad de precio (brecha #1 vs Endolifter/Doctoralia) | Endolift **desde 1.460 €** en HTML (hero, bloque inversión, FAQ) + `Offer` en schema. Constante `NVX_ENDOLIFT_PRICE_FROM_EUR`. |
| Entidades MedicalClinic / Physician / MedicalProcedure | Org `MedicalOrganization` + sedes `MedicalClinic` + `Physician` (ICOMEM, alumni UCM, knowsAbout láser) + procedures con `indication` + `performer`/`reviewedBy`. |
| OfferCatalog en home | Catálogo de protocolos; Endolift con `price` EUR; resto sin precio inventado. **Sin** `InStock` retail. |
| FAQPage | Mismas Q/A que HTML (precio, indicación, duración 18m–3a, vs AH, recuperación). |
| priceRange | `€€€` en org (banda, no inventar min–max). |
| ReserveAction | Solo **valoración presencial** (`/madrid/valoracion/`). Sin videoconsulta. |
| Titles / metadesc | Home + Endolift vía `wpseo_title` / `wpseo_metadesc` con intención transaccional. |
| E-E-A-T visible | Byline “Revisado por Dr. Rivera…” en página Endolift + enlace a equipo. |

## Rechazado del brief (aunque suene “agresivo SEO”)

| Item | Por qué |
|------|---------|
| Segundo `<script type="application/ld+json">` suelto | Duplica Yoast; strip activo |
| `legalName` NUVANX S.L. | No confirmado en repo |
| geo / AggregateRating 5★·150 | No verificados en vivo; no hardcodear |
| 224% HA como hecho de marketing | Solo si se cita como preclínico en copy editorial CMS |
| Videoconsulta en CTA/schema | No operativa como producto |
| medicalSpecialty Dermatologic en Physician | No afirmar especialidad hospitalaria sin acreditación en ficha |
| Prosa “estándar de oro / océano azul” | Tono infomercial; los datos (precio, límites, downtime) sí se publican |

## CMS / contenido pendiente (no solo PHP)

1. Actualizar tarifa en Doctoralia y en `NVX_ENDOLIFT_PRICE_FROM_EUR` al unísono.
2. Precios de referencia en Endoláser / CO₂ / EXION cuando comerciales los cierren.
3. Purgar “casos en preparación”; galería real con alt clínicos.
4. Landings anatómicas (`/endolaser-papada-madrid/`, etc.) cuando haya redacción.
5. Artículos firmados (Endolift vs Morpheus/EXION; cronología CO₂).

## Validación post-deploy

- Rich Results: home → Organization + clinics + Physician + OfferCatalog; Endolift → MedicalProcedure + Offer + FAQPage.
- Un solo schema.org ld+json por página (Yoast).
- Query de prueba: “cuánto cuesta Endolift Madrid” → la página oficial debe ser extractable (precio visible en HTML).
