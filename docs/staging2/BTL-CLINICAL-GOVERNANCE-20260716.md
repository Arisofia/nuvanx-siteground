# BTL clinical governance — staging2

Date: 2026-07-16

## Scope

This control applies to:

- `/exion-face/`
- `/exion-body/`
- `/exion-fractional/`
- `/emfusion/`

Production deployment is not authorized by this document.

## Immediate safeguards

1. The BTL page publisher accepts only `NVX_BTL_PAGES_APPLY`.
2. `NVX_BLOG_APPLY` is rejected explicitly to prevent cross-workflow publication.
3. The staging URL and active theme guards remain mandatory.
4. High-risk comparative, quantitative and recovery claims are replaced at render time with neutral wording.
5. Yoast descriptions are overridden with conservative descriptions on the four governed routes.
6. A visible evidence notice is appended to each governed page.

## Source-copy debt

The render-time safeguard is not the final editorial architecture. The source registry in `inc/nvx-btl-detail-pages.php` still requires a complete rewrite and medical sign-off.

The following classes of statement must not be restored without a page-specific evidence record:

- fixed percentages of fat reduction, laxity improvement or matrix markers;
- universal treatment temperatures;
- universal session counts, pain scores or recovery times;
- claims of superiority or lower risk against HIFU, Thermage, Morpheus8, Potenza, CoolSculpting, HydraFacial or Dermapen;
- language such as catastrophic laxity, massive bleeding, chronic pain, complete tightening or miracle result;
- claims inferred from manufacturer preclinical material and presented as patient outcomes.

## Required evidence record

Each quantitative or comparative claim requires:

- exact claim text;
- page and section;
- primary source;
- device model and applicator;
- study design and population;
- endpoint and follow-up;
- limitations;
- medical reviewer;
- approval date;
- next review date.

## Staging validation

Run the publisher in audit mode first:

```bash
wp eval-file scripts/staging2/publish-btl-detail-pages.php
```

Apply only on staging2:

```bash
NVX_BTL_PAGES_APPLY=1 wp eval-file scripts/staging2/publish-btl-detail-pages.php
```

Validation must confirm:

- the four pages resolve as WordPress pages;
- the active theme is `nuvanx-medical`;
- no aggressive comparative wording reaches rendered HTML;
- meta descriptions use the governed wording;
- only the canonical Yoast JSON-LD graph is emitted;
- no production URL or database is changed.

## Exit criteria

The temporary governance filter may be removed only after:

1. the source registry is rewritten;
2. all claims have medical and legal review;
3. CI validates prohibited claim patterns;
4. staging2 smoke tests pass;
5. rendered HTML and schema are reviewed together.
