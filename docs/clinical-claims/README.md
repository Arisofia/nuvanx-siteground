# NUVANX clinical claims governance

`claims-register.json` is the machine-readable inventory for clinical,
regulatory, credential and tariff statements used by the public site.

## Statuses

- `approved`: may be published in the scopes listed in the record. It must have
  a source and review date.
- `pending`: may not be expanded into a stronger or broader statement. The
  required primary evidence and medical approval are recorded explicitly.
- `rejected`: prohibited in deployable public code. The claims gate scans the
  canonical theme and fails when rejected language is found.

A `pending` record does not represent medical approval. It exists to make the
missing evidence visible and assign an owner.

## Required evidence

Clinical claims should use, in descending order of preference:

1. current manufacturer IFU, intended-use or declaration documents for the
   exact model installed at NUVANX;
2. regulatory or professional guidance;
3. peer-reviewed primary clinical evidence relevant to the communicated
   indication and parameters;
4. an approved NUVANX protocol signed and dated by medical direction.

Marketing pages, competitor websites and AI-generated reports are not primary
clinical evidence.

## Change procedure

1. Create or update a stable `NVX-*` claim record.
2. Attach a source without committing confidential patient or credential data.
3. Record the permitted scope and any qualification.
4. Obtain medical-direction approval.
5. Change the status to `approved` and set `review_due`.
6. Update visible HTML, schema and FAQ from the same approved wording.
7. Run `node scripts/clinical/audit-claims.mjs`.

## Release rule

SEO, GEO or conversion value does not override claim status. Rejected wording
blocks CI. Pending wording must remain qualified and cannot be converted into
absolute safety, superiority, duration or outcome promises.
