# P0 finish runbook — canonical production contract

## Implemented in `master`

| Item | Canonical mechanism |
|---|---|
| EXILITE in Yoast graph | `nvx-structured-data.php` |
| Strip embedded Schema.org JSON-LD | `nvx-jsonld-content.php` + persistent cleanup |
| Cookies 18/31 → 577 | `nvx-page-hygiene.php` |
| Casos 2645 hidden/noindex until ready | `nvx-page-hygiene.php` |
| Thank-you 78 noindex | `nvx-page-hygiene.php` |
| Endolift/RF corrections | runtime hygiene + `cleanup-content-navigation.php` |
| Legal approved + RGPD/LSSI context | `nvx-page-hygiene.php` + `nvx-p0-publication-guard.php` |
| Dra. Cristina Márquez González | ICOMEM 282858861 + formación + Doctoralia |
| Contacto form-free | `templates/template-contact.php` + modal exclusion |
| Valoración single form | `nuvanx-valoracion-native-hubspot-form.php` |
| EXION without unapproved prices/comparatives | scoped DOM guard + persistent cleanup |
| Funnel and analytics gates | `conversion-events-gate.yml` + Playwright |

## Canonical funnel

### `/contacto/`

Contacto is an informational local route, not a lead form landing.

Required rendered contract:

- zero `.hs-form-frame` and `.hbspt-form`;
- zero HubSpot iframes or embed scripts;
- zero valoración modal;
- two clinic cards and two maps;
- Chamberí and Goya telephone/WhatsApp routes;
- direct link to `/madrid/valoracion/`.

### `/madrid/valoracion/`

Valoración is the only dedicated full-page HubSpot form route.

Required rendered contract:

- exactly one `#nvx-hubspot-native-form`;
- exactly one `.hs-form-frame`;
- exactly one canonical HubSpot embed script;
- form ID `5042522a-0bc5-4381-ac3e-5aee8649b69c`;
- portal `147416356`, region `eu1`;
- exactly one privacy link inside the primary mount;
- one initialized HubSpot iframe;
- one deduplicated `generate_lead` event per successful submission.

## Staging2 execution

### 1. Confirm deploy

```text
Actions → Deploy theme to staging2 + flush cache
Expected: exact master SHA in meta[name="nvx-deploy-sha"]
```

### 2. Persistent content cleanup

```text
Actions → Staging2 content cleanup → mode: audit
Review JSON summary and semantic findings.

Actions → Staging2 content cleanup → mode: apply
confirm: CONFIRM-STAGING2

Run audit again.
Expected: deterministic_change_count = 0
```

The cleanup must remove stored:

- Contacto HubSpot remnants;
- duplicate/legacy Valoración frames and scripts;
- obsolete Cristina credential `282869501`;
- explicit EXION prices in canonical EXION routes;
- embedded schema and Endolift/radiofrequency conflations.

### 3. Rendered QA

| Route | Required evidence |
|---|---|
| `/contacto/` | no HubSpot; two NAP cards/maps; direct Valoración route |
| `/madrid/valoracion/` | one form, one script, one iframe, correct analytics event |
| `/politica-privacidad/` | approved legal text + RGPD/LSSI note |
| `/aviso-legal/` | approved legal text + RGPD/LSSI note |
| `/equipo-medico/` | Cristina 282858861 + formación + Doctoralia; no 282869501 |
| EXION family | no explicit unapproved prices; no blocked Morpheus details |
| `/casos-de-pacientes/` | noindex and absent from navigation until approved |

Also require:

- no first-party 4xx/5xx;
- no unexpected console errors;
- mobile and desktop smoke checks;
- all required GitHub gates green for the exact SHA.

## Production decision

Legal approval is complete and is no longer a blocker.

Production remains **NO GO** until:

1. the exact SHA is live on Staging2;
2. cleanup audit/apply/audit closes with zero deterministic changes;
3. rendered funnel and analytics contracts pass;
4. remaining published team credentials have documentary evidence;
5. complete Staging2 QA passes.

Production promotion must use the same immutable SHA validated on Staging2.
