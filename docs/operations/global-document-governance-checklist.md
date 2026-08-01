# Global governance release checklist

## Before merge

- Theme PHP lint passes.
- JavaScript syntax checks pass.
- All three governance workflows pass `actionlint`.
- Document governance runtime contract passes twice to prove idempotence.
- The exact-SHA Staging2 acceptance contract passes.
- Existing catalog, rendering and valoración contracts remain green.
- The branch is based on the current `master` without historical merge commits.

## Staging2 deployment

- Deploy one full 40-character SHA already contained in `master`.
- Verify the theme deployment marker.
- Purge WordPress, SiteGround dynamic and browser-facing asset caches.
- The deployment job must run complete rendered acceptance with `EXPECTED_SHA` equal to its own immutable `DEPLOY_SHA`.
- Do not accept a successful deployment until this exact-SHA verification completes.
- Use `Staging2 Rendered Acceptance` only for independent manual revalidation, always supplying the full deployed SHA.

## Rendered acceptance

- Home, Contacto, Soluciones, Valoración, medical hubs, Equipo and Clínicas return 2xx.
- Every route renders exactly one title, description, canonical and viewport.
- Every route serves the expected deployment SHA.
- Staging2 remains protected by meta and HTTP noindex directives.
- Site Kit consent bootstrap variables survive document normalization.
- No retired FacebookSignal runtime or unresolved CMS marker reaches the browser.
- HubSpot is absent from initial non-form page scripts and loads only after user intent.
- The Home evidence image contains intrinsic dimensions.
- Soluciones renders its canonical hierarchy and dedicated stylesheet.

## Browser validation

- Run Lighthouse mobile on Home and Contacto after caches are purged.
- Confirm no consent-mode exception in the browser console.
- Confirm desktop navigation, mobile drawer, Escape, Tab containment and focus restoration.
- Confirm the valoración modal loads its form after an explicit CTA action and retains its full-page fallback.

## Promotion rule

Production promotion is prohibited until the exact Staging2 SHA passes the complete rendered acceptance and browser validation. No page-level override, manual CSS injection or rollback branch is an acceptable substitute.
