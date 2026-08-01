# Global document and runtime governance

This contract applies to every public NUVANX theme response. It replaces page-by-page acceptance with one platform-level rendered-document standard.

## Required document invariants

Every public route must render:

- one non-empty `<title>`;
- one non-empty meta description;
- one canonical URL;
- one viewport declaration;
- one Spanish `lang` attribute;
- a non-empty `main` landmark;
- the global document-contract marker;
- the immutable deployment SHA marker;
- the canonical design-system and accessibility assets.

Staging2 must retain both meta and HTTP `noindex` protection.

## Integration invariants

- Script cleanup is performed per `<script>` element. A regex may never cross from one script element into another.
- Site Kit consent bootstrap variables must remain present whenever its consent runtime is loaded.
- Retired FacebookSignal code must not reach the browser.
- HubSpot modal assets are demand-loaded only after explicit user intent. The full valoración route remains available as the fallback.

## Accessibility invariants

- Only one navigation mode is exposed at each responsive breakpoint.
- The closed mobile drawer is `aria-hidden` and `inert`.
- Mobile navigation supports Escape, focus entry, focus containment and focus restoration.
- Interactive controls preserve the shared minimum touch target.

## Media invariants

Same-origin WordPress attachment images missing intrinsic dimensions receive their registered media-library width and height in the final document. This protects aspect ratio and layout stability without page-specific image patches.

## Validation

`Theme Hygiene Gate` validates source and runtime contracts before merge.

`Staging2 Rendered Acceptance` runs after a successful manual deployment and validates the rendered HTML of the critical route inventory. All checked routes must serve the same deployment SHA. A 2xx response alone is not release acceptance.
