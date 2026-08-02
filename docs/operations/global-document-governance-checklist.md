# Global governance release checklist

## Before merge

- Theme PHP lint passes
- JavaScript syntax checks pass
- Permanent workflows pass `actionlint` in Theme Hygiene Gate
- Document governance runtime contract passes (including double-normalize idempotence)
- Staging2 exact-SHA acceptance contract source is present
- Catalog, rendering and valoración contracts remain green
- Branch is based on current `master`

## Staging2 deployment

- Deploy one full 40-character SHA already contained in `master`
- Verify the theme deployment marker
- Purge WordPress and SiteGround caches
- Deployment job runs complete rendered acceptance with `EXPECTED_SHA` equal to `DEPLOY_SHA`
- Do not treat deploy as successful until exact-SHA verification completes
- Use `Staging2 Rendered Acceptance` only for independent revalidation with the full deployed SHA

## Rendered acceptance

- Home, Contacto, Soluciones, Valoración, medical hubs, Equipo and Clínicas return 2xx
- Every route renders exactly one title, description, canonical and viewport
- Every route serves the expected deployment SHA
- Staging2 remains protected by meta and HTTP noindex
- Site Kit consent bootstrap survives document normalization when present
- No FacebookSignal or unresolved CMS strategy marker in public HTML
- HubSpot is absent from initial HTML scripts and loads only after user intent
- Home evidence image has intrinsic dimensions
- Soluciones renders its hierarchy and dedicated stylesheet

## Browser validation

- Lighthouse mobile on Home and Contacto after cache purge
- No consent-mode exception in the browser console
- Desktop navigation and mobile drawer (Escape, Tab containment, focus restore)
- Valoración modal loads the form after an explicit CTA and keeps the full-page fallback

## Promotion rule

Production promotion is prohibited until the exact Staging2 SHA passes complete rendered acceptance and browser validation. No page-level override or ad-hoc CSS injection substitutes for that gate.
