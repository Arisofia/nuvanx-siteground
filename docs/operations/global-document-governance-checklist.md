# Global governance release checklist

## Before deploy

- Branch is based on current `master`
- Target SHA is a full 40-character commit contained in `master`
- Staging2 deploy confirmation is `DEPLOY_STAGING2`

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
