# Global document and runtime governance

This contract applies to every public NUVANX theme response and is enforced by the rendered-acceptance suite.

## Required document invariants

Every public route must render, in the final normalized HTML:

- one non-empty `<title>`
- one non-empty `<meta name="description">`
- one canonical URL
- one viewport declaration
- a Spanish `lang` attribute on the root element
- a non-empty `main` landmark
- the global document-contract marker
- the immutable deployment SHA marker
- the canonical design-system and accessibility assets

Staging2 must retain meta and HTTP `noindex` protection on all public routes.

Any route missing one of these invariants is a release-blocking defect.

## Integration invariants

- Script cleanup is per `<script>` element; a regex may never cross script boundaries
- Site Kit consent bootstrap variables remain present when its consent runtime is loaded
- FacebookSignal must not reach the browser
- HubSpot embeds are demand-loaded after explicit user intent; `/madrid/valoracion/` is the full-page fallback when scripts are blocked or unavailable

Any violation of these invariants in accepted HTML is a release-blocking defect.

## Measurement ownership

NUVANX uses explicit single-owner boundaries so browser tags and server-side conversion relays cannot duplicate the same responsibility.

| Surface | Canonical owner | Contract |
| --- | --- | --- |
| Google Tag / GTM / GA4 / Google Ads / Consent Mode loader | Google Site Kit + the governed GTM container | The theme never injects another GTM loader, noscript iframe, GA4 tag or Google Ads conversion-action ID. |
| NUVANX browser analytics context | `inc/nvx-gtm-integration.php` | The theme may populate `dataLayer` and NUVANX business/QA context only; context is not a second tag loader. |
| Meta browser Pixel / FacebookSignal | No public browser owner | Facebook/Meta Pixel plugins and SiteGround FacebookSignal are intentionally disabled on public requests. Rendered acceptance must fail if FacebookSignal reaches public HTML. |
| Meta server-side conversion delivery | `Nuvanx-System` → Supabase `web-events` | Meta CAPI is server-side only, authenticated internally and remains subject to consent/QA suppression. The WordPress theme must not recreate a parallel browser Pixel path. |

The historical `FacebookSignal is not defined` failure was a symptom of overlapping browser ownership/optimizer behavior. It is therefore not fixed by re-enabling the Facebook plugin or by adding another inline Pixel. Any future decision to restore a browser Meta Pixel requires an explicit architecture change, event-deduplication contract and rendered-acceptance update before activation.

## Accessibility invariants

- One navigation mode per responsive breakpoint
- Closed mobile drawer is `aria-hidden` and `inert`
- Mobile navigation supports Escape, focus entry, containment and restoration
- Interactive controls meet the shared minimum touch target

Accessibility regressions observed on checked routes are a release-blocking defect.

## Media invariants

Same-origin WordPress attachment images missing width/height receive registered media-library dimensions in the final document. Images must not rely on layout-only heuristics when canonical dimensions are available.

## Validation

- `Deploy Staging2` runs rendered acceptance with `EXPECTED_SHA` equal to the immutable `DEPLOY_SHA` after the deploy marker is verified
- `Staging2 Rendered Acceptance` is independent automated revalidation of a full deployed SHA

All checked routes must:

- serve the exact expected SHA marker
- satisfy all document, integration, accessibility and media invariants

HTTP 2xx alone is not release acceptance. Any contract failure must block release until corrected and revalidated against the exact SHA.
