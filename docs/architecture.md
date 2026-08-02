# Repository architecture

This repository is the canonical source for the NUVANX site deployed on SiteGround.

## Principles

- One theme: `wp-content/themes/nuvanx-medical`
- Form and attribution integrations live in `wp-content/mu-plugins`
- Public CSS and JS are enqueued from versioned theme source files
- Staging2 identity is the full 40-character Git SHA in `.nvx-deploy-sha` and the public `nvx-deploy-sha` meta tag
- Staging2 rendered acceptance is the release gate after an immutable SHA deploy
- Every accepted release is traceable to a single Git SHA and a single deployment marker

## Runtime layers

1. **Theme PHP** — page rendering, SEO catalogue, document and runtime governance
2. **MU plugins** — valoración and contacto HubSpot mounts and required form integrations
3. **Front-end assets** — design-system CSS, `nvx-runtime-governance.js` and accessibility/runtime guards
4. **Deploy scripts** — guarded rsync, deployment marker stamp and cache purge on SiteGround
