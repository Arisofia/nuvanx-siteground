# P0 finish runbook — post PR #37

## Already in master (theme)

| Item | Mechanism |
|------|-----------|
| EXILITE in Yoast graph | `nvx-structured-data.php` |
| Strip Schema.org JSON-LD from page content | `nvx-jsonld-content.php` + `the_content` |
| Cookies 18/31 → 577 | `nvx-page-hygiene.php` |
| Casos 2645 noindex until meta ready | `nvx-page-hygiene.php` |
| Thank-you 78 noindex | `nvx-page-hygiene.php` |
| Cleanup strips JSON-LD + Endolift/RF phrases | `cleanup-content-navigation.php` (this PR) |
| No videoconsulta CTAs (láser / Endolift) | theme modules (this PR) |
| Soften 224% HA claims in theme injects | content-presentation + catalog + laser (this PR) |

## Must run on staging2 after deploy

### 1. Deploy theme

```text
Actions → Deploy theme to staging2 + flush cache → ref: master
```

### 2. Content cleanup

```text
Actions → Staging2 content cleanup → mode: audit
# review JSON summary (Endolift RF changes, JSON-LD strips)
Actions → Staging2 content cleanup → mode: apply, confirm: CONFIRM-STAGING2
```

### 3. Manual WP (cannot invent legal/credential text in repo)

| Page | Action |
|------|--------|
| Clínicas 1399 | Confirm Endolift card no longer says RF monopolar after apply |
| Casos 2645 | Remove from menus; keep draft or noindex |
| Contacto 14 | Remove HubSpot form if present; NAP + Maps only |
| Valoración 2636 | Single form CTA; one phone secondary |
| Privacidad 3 / Aviso 20 | Legal rewrite with counsel |
| Equipo 1575 | Titulación + colegiación + alcance per person |

### 4. Verify HTTP

```text
/clinicas-de-medicina-estetica-nuvanx/  → no "radiofrecuencia monopolar" near Endolift
/btl-exilite-ipl-madrid/                → 1× application/ld+json, 0 in <main>
/casos-de-pacientes/                    → robots noindex (until meta)
/madrid/valoracion/                     → form primary
```

## Production

Still **NO GO** until legal pages, team credentials, and funnel split are done and QA passes on deployed staging2.
