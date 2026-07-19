# P0 finish runbook — post PR #37

## Already in master (theme)

| Item | Mechanism |
|------|-----------|
| EXILITE in Yoast graph | `nvx-structured-data.php` |
| Strip Schema.org JSON-LD from page content | `nvx-jsonld-content.php` + `the_content` |
| Cookies 18/31 → 577 | `nvx-page-hygiene.php` |
| Casos 2645 noindex until meta ready | `nvx-page-hygiene.php` |
| Thank-you 78 noindex | `nvx-page-hygiene.php` |
| Cleanup strips JSON-LD + Endolift/RF phrases | `cleanup-content-navigation.php` |
| No videoconsulta CTAs (láser / Endolift) | theme modules |
| Soften 224% HA claims in theme injects | content-presentation + catalog + laser |
| Marco normativo en Privacidad/Aviso Legal | `nvx-page-hygiene.php` · legal aprobado |
| Perfil Dra. Cristina Márquez González | ICOMEM 282858861 + formación + Doctoralia en `nvx-page-hygiene.php` |

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

### 3. Content and credential verification

| Page | Action |
|------|--------|
| Clínicas 1399 | Confirm Endolift card no longer says RF monopolar after apply |
| Casos 2645 | Remove from menus; keep draft or noindex |
| Contacto 14 | Validate the approved funnel contract in rendered HTML |
| Valoración 2636 | Validate one primary form and the approved secondary contact route |
| Privacidad 3 / Aviso 20 | Legal approved; confirm full text, permanent access and the RGPD/LSSI context note |
| Equipo 1575 | Confirm Cristina: Dra. Cristina Márquez González · ICOMEM 282858861 · formation · Doctoralia; verify remaining people against documentary evidence |

### 4. Verify HTTP

```text
/clinicas-de-medicina-estetica-nuvanx/  → no "radiofrecuencia monopolar" near Endolift
/btl-exilite-ipl-madrid/                → 1× application/ld+json, 0 in <main>
/casos-de-pacientes/                    → robots noindex (until meta)
/madrid/valoracion/                     → approved primary form contract
/politica-privacidad/                   → approved legal text + RGPD/LSSI note
/aviso-legal/                           → approved legal text + RGPD/LSSI note
/equipo-medico/                         → Cristina 282858861 + formation + Doctoralia; no 282869501
```

## Production

Legal approval is no longer a blocker. Production remains **NO GO** until the rendered funnel contract, remaining team credentials and full staging2 QA pass for the exact deploy SHA.
