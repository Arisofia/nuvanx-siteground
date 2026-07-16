# Schema canónico, deploy y EXILITE — 2026-07-16

## Diagnóstico

| Hecho | Evidencia |
|-------|-----------|
| PR de schema fusionado en master | `#15` → `35b8bdc1` (`feat(staging2): canonical GEO schema…`) |
| Grafo del tema (código) | `inc/nvx-structured-data.php` vía `wpseo_schema_graph` |
| Cobertura inicial del grafo | Sedes (Chamberí/Goya + hub), **Endolift® facial**, **EXION® BTL** |
| EXILITE fuera del registry | Path `/btl-exilite-ipl-madrid/` no resolvía treatment key → sin nodo Service en Yoast |
| JSON-LD independiente en EXILITE | Live: **2** scripts `application/ld+json` — (1) grafo Yoast en head, (2) lista embebida en `<main>` con `MedicalClinic` ×2 + `MedicalWebPage` + `FAQPage` |
| Deploy ligado al merge de schema | **No** hay run de `Deploy theme to staging2` cuyo `headSha` sea `35b8bdc1`. El módulo llegó a staging en deploys posteriores de master (p. ej. tras #29–#33), no en un deploy dedicado al merge de #15. |

## Cierre técnico en este cambio

1. **Registry + Service EXILITE** — path `/btl-exilite-ipl-madrid/`, descripción conservadora (sin claims numéricos).
2. **Strip runtime** — `the_content` / `the_excerpt` / `widget_text_content` eliminan `<script type="application/ld+json">` del HTML de contenido para no duplicar el grafo.
3. **Cleanup staging2 (DB)** — `cleanup-content-navigation.php` también purga JSON-LD embebido en `post_content` (audit/apply).
4. **Deploy** — tras merge a master, el workflow auto-deploy debe ejecutarse; si el smoke del runner falla por respuestas cortas, re-lanzar `staging2-smoke-verify` o verificar HTTP manual.

## Qué sigue fuera de código (contenido / clínica)

- Validar claims de EXION/EXILITE (RF, IPL, cifras) con dirección clínica.
- Confirmar cookies 18 → 577 en DB.
- FAQPage en home suelto: el strip de contenido lo mitiga si está en `post_content`; si viene de otro plugin, revisar aparte.
- No inventar FAQ schema para EXILITE hasta que la página muestre FAQ visible espejada.

## Verificación post-deploy

```text
https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
```

Esperado:

- exactamente **1** `application/ld+json` (grafo Yoast + nodos NUVANX);
- tipos incluyen `Service` (EXILITE) y **no** un segundo bloque `MedicalClinic`/`FAQPage` en body;
- sin scripts `ld+json` dentro de `<main>`.
