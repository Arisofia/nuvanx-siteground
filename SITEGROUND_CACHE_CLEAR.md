# SiteGround Cache Clear Instructions

## Problem
After deploying code changes, staging2.nuvanx.com still serves cached content. Filters applied to prevent duplicate heroes are not taking effect.

## Solution: Clear SiteGround Cache

### Option 1: WordPress Admin (Recommended)
1. Log in to WordPress admin: https://staging2.nuvanx.com/wp-admin
2. Navigate to: SG Optimizer → Clear Cache
3. Select: "Flush Cache"
4. Click: "Clear All Caches"

### Option 2: Via cPanel/SiteGround
1. Log in to cPanel: https://staging2.nuvanx.com/cpanel
2. Navigate to: SiteGround → SuperCacher
3. Click: "Flush Cache"

### Option 3: WP-CLI (if available)
```bash
wp cache flush
wp sg-cachepress purge --url=https://staging2.nuvanx.com/madrid/valoracion/
wp sg-cachepress purge --url=https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
wp sg-cachepress purge --url=https://staging2.nuvanx.com/medicina-estetica-chamberi/
wp sg-cachepress purge --url=https://staging2.nuvanx.com/medicina-estetica-goya-barrio-salamanca/
wp sg-cachepress purge --url=https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
```

### Option 4: Bypass Cache (for testing)
Add cache-busting parameter to URLs:
- https://staging2.nuvanx.com/madrid/valoracion/?nocache=1
- https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/?nocache=1

## Verification
After clearing cache, run the audit script again:
```bash
node scripts/audit-routes-systematic.mjs
```

Expected result: All 51 pages should show OK status (0 duplicate heroes).

## Recent Changes
- Commit d6a07fef: Added URL-based hero removal for clinic pages and increased filter priority
- Filter priority: 1 (earliest execution)
- URL-based detection for clinic hub pages
- Template-based detection for Pattern A pages

## Troubleshooting
If cache clear doesn't work:
1. Check SiteGround Optimizer plugin status (is it active?)
2. Check if there's a CDN cache (Cloudflare, etc.)
3. Check browser cache (incognito mode)
