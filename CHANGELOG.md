# Changelog

## 2026.07.20
- **Production readiness cleanup**
  - Eliminado schema LocalBusiness duplicado e incorrecto en `nvx-integrations.php` (horarios 10:00 erróneos). Fuente canónica única: `nvx-structured-data.php` (Chamberí 12:00–20:00 + sábado 10:00–18:00; Goya 11:00–20:00).
  - Sincronización de versiones: `style.css` y `NVX_THEME_VERSION` → `2.0.0-plata-pulida-canonical`; `VERSION` → `2026.07.20`.
  - Menú fallback primario enriquecido con jerarquía completa de tratamientos (Endolift®, Endoláser, CO₂, EXION Face/Body/Fractional, EMFUSION, EXILITE) alineada a la IA live.
  - Purga de archivo vacío `informe-inteligencia-competitiva-madrid.md`.
  - Comentarios de higiene y robustez; sin cambios de copy clínico publicado.
  - Hero blackout se mantiene como toggle intencional hasta aprobación fotográfica.

## 2026.07.13
- Estructura inicial del repositorio orientada a SiteGround
- Añadido tema canónico wp-content/themes/nuvanx-medical
- Añadidos mu-plugins base para HubSpot y atribución
- Añadidos workflows y scripts de validación base
