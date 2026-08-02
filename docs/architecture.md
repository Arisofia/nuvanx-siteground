# Repository architecture

This repository is the canonical source for the NUVANX site on SiteGround.

## Principles

- One theme: `wp-content/themes/nuvanx-medical`
- Form and attribution integrations live in `wp-content/mu-plugins`
- Public CSS is enqueued from versioned theme source files
- Staging2 identity is the full 40-character Git SHA in `.nvx-deploy-sha` and the public `nvx-deploy-sha` meta tag
- Staging2 rendered acceptance is the release gate after an immutable SHA deploy

## Runtime layers

1. **Theme PHP** — page rendering, SEO catalogue, document governance
2. **MU plugins** — valoración and contacto HubSpot mounts
3. **Front-end assets** — design-system CSS and `nvx-runtime-governance.js`
4. **Deploy scripts** — guarded rsync and cache purge on SiteGround
