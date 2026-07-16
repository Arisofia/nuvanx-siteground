# Staging2 content and navigation cleanup — 2026-07-16

## Scope

These corrections belong to WordPress content, excerpts and navigation stored in the staging2 database. They must not be implemented as theme CSS, PHP output filters, runtime string replacement or compatibility patches.

Target environment only:

```text
https://staging2.nuvanx.com
/home/customer/www/staging2.nuvanx.com/public_html
```

Production must remain unchanged.

## Required deployment order

1. Execute the GitHub Actions workflow **Deploy theme to staging2 + flush cache**.
2. Use the exact ref:

```text
fix/staging2-geo-schema-20260716
```

3. Confirm:
   - staging `siteurl` and `home` are `https://staging2.nuvanx.com`;
   - active theme is `nuvanx-medical`;
   - workflow returns `DEPLOY_FLUSH_OK`;
   - production was not contacted or modified.
4. Run the database cleanup script first in audit mode.
5. Review the generated report.
6. Run deterministic corrections with `--apply`.
7. Resolve semantic blockers manually in WordPress staging2.
8. Re-run audit and require zero deterministic findings before visual QA.

## Deterministic corrections

The supplied script can safely perform these staging-only changes:

- rename the exact menu label `Clínica Salamanca-Goya` to `Clínica Goya · Barrio Salamanca`;
- replace incomplete CTA text `Solicitar.` with `Solicitar valoración médica`;
- remove empty `<ul>` and `<ol>` elements;
- replace absolute `https://nuvanx.com` internal links with `https://staging2.nuvanx.com` in staging content and excerpts;
- correct `características induales` to `características individuales`;
- collapse duplicated commas in the Goya excerpt;
- remove inline `style` attributes from legacy post content, while preserving script/style element contents and external embeds.

Every mutation is preceded by a protected JSON export outside the public web root. The script is dry-run by default and requires `--apply` plus an exact staging environment guard.

## Semantic review blockers

The following items must not be replaced through broad search-and-replace because the approved final wording is not yet defined:

### Consulta virtual / consulta online

Audit all published pages, excerpts, menu labels and reusable content for:

```text
consulta virtual
consulta online
valoración virtual
videoconsulta
online
```

Doctoralia currently indicates that NUVANX does not offer online consultation. Until the actual operating model is approved, remove promises of virtual service or replace them manually with verified wording. Do not claim a service that is not operational.

### Endolift and radiofrequency monopolar

Locate content that describes Endolift as radiofrequency monopolar or otherwise merges the two technologies. This requires clinical copy review. The database script reports matching records but does not invent replacement medical copy.

Approved correction must preserve the distinction between:

- Endolift/endolaser: laser-assisted subdermal treatment according to medical assessment;
- radiofrequency monopolar: a different energy modality and treatment category.

### Doctoralia consistency

Doctoralia is an external platform and cannot be corrected through this repository. Review its profile separately because the current public representation reportedly shows:

- one address: Chamberí;
- 103 opinions;
- no online consultation.

The web presents two NUVANX locations. Align Doctoralia only after confirming which locations and services should be publicly represented.

## Validation matrix after database cleanup

Review at minimum:

```text
/
/contacto/
/madrid/valoracion/
/medicina-estetica-chamberi/
/clinicas-de-medicina-estetica-nuvanx/
/equipo-medico/
/endolift-facial-papada-mandibula/
/endolaser-corporal-grasa-localizada/
/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
```

Required checks:

- menu label is exact;
- no incomplete `Solicitar.` CTA;
- no empty lists;
- no unintended inline style attributes in legacy content;
- no staging links pointing to production;
- typo corrected;
- Goya excerpt punctuation corrected;
- no medically incorrect Endolift/radiofrequency statement;
- no unapproved virtual-consultation promise;
- forms remain visible and submit correctly;
- HTTP 200;
- no horizontal overflow;
- production unchanged.

## Release status

This branch may be deployed to staging2 for validation. It is not a production release authorization. The database cleanup must be executed directly against staging2 after the theme/schema workflow completes.