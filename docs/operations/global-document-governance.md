# Global document and runtime governance

This contract applies to every public NUVANX theme response.

## Required document invariants

Every public route must render:

- one non-empty `<title>`
- one non-empty meta description
- one canonical URL
- one viewport declaration
- a Spanish `lang` attribute
- a non-empty `main` landmark
- the global document-contract marker
- the immutable deployment SHA marker
- the canonical design-system and accessibility assets

Staging2 must retain meta and HTTP `noindex` protection.

## Integration invariants

- Script cleanup is per `<script>` element; a regex may never cross script boundaries
- Site Kit consent bootstrap variables remain present when its consent runtime is loaded
- FacebookSignal must not reach the browser
- HubSpot embeds are demand-loaded after explicit user intent; `/madrid/valoracion/` is the full-page fallback

## Accessibility invariants

- One navigation mode per responsive breakpoint
- Closed mobile drawer is `aria-hidden` and `inert`
- Mobile navigation supports Escape, focus entry, containment and restoration
- Interactive controls meet the shared minimum touch target

## Media invariants

Same-origin WordPress attachment images missing width/height receive registered media-library dimensions in the final document.

## Validation

`Theme Hygiene Gate` validates source, workflow and runtime contracts before merge.

`Deploy Staging2 (manual)` runs rendered acceptance with `EXPECTED_SHA` equal to the immutable `DEPLOY_SHA` after the deploy marker is verified.

`Staging2 Rendered Acceptance` is independent manual revalidation of a full deployed SHA.

All checked routes must serve the exact expected SHA. HTTP 2xx alone is not release acceptance.
