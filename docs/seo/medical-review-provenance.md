# Medical review provenance

NUVANX treatment pages may expose a visible medical-review disclosure and the
Schema.org `reviewedBy` property only after an explicit approval record exists.

The feature does not infer approval from authorship, employment, a physician
card, page modification dates or the presence of clinical copy.

## Required page metadata

| Meta key | Required value |
|---|---|
| `_nvx_medical_review_status` | `approved` |
| `_nvx_medical_reviewer` | A key registered by `nvx_medical_reviewers()`; currently `rivera` |
| `_nvx_medical_review_date` | A real calendar date in `YYYY-MM-DD` format |

All three values are required. Missing, unknown or malformed values produce no
visible disclosure and no `reviewedBy` schema property.

## Current scope

The first release is restricted to treatment pages registered in
`nvx_schema_page_registry()`. It does not automatically mark the home page,
clinic pages or blog posts as medically reviewed.

## Visible output

An approved page displays:

- reviewer name linked to the canonical physician profile;
- ICOMEM registration number;
- exact clinical review date.

The same reviewer is referenced from the page's existing Yoast `WebPage` node.
No second JSON-LD block is created.

## Approval workflow

1. Medical direction reviews the visible page and the claims used by its HTML,
   FAQ and schema.
2. Pending claims in `docs/clinical-claims/claims-register.json` are resolved or
   retained with appropriate qualification.
3. Record the reviewer key, approval status and review date in WordPress post
   meta.
4. Purge caches and validate both the visible component and `reviewedBy` in the
   rendered Yoast graph.
5. Repeat the review when material clinical wording, device parameters,
   indications or recovery guidance changes.

Do not copy a review date between pages or set a future date. A content update
is not equivalent to a medical review.
