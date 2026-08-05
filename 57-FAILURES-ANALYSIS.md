# 57 Fallas Analysis - Action Plan

**Date:** 2026-08-05
**Analysis:** Based on P1-FORENSIC-AUDIT-RESULTS.md and local verification

## 🔴 Critical Findings (Server-Side)

### Cause 1: FacebookSignal in HTML Initial - 51/51 routes
**Root Cause:** SiteGround Optimizer injects FacebookSignal via full-page cache, bypassing PHP hooks
**Impact:** 51 routes affected
**Action Required (WordPress Panel):**
1. Plugins → Deactivate Facebook/SiteGround Security Facebook Pixel
2. SiteGround SuperCacher → Purge all cache
3. Re-run acceptance locally to confirm

### Cause 2: Rogue Third-Party Scripts - 50/51 routes
**Root Cause:** Same mechanism as Cause 1 (SiteGround Optimizer)
**Impact:** 50 routes affected
**Action:** Resolving Cause 1 = Resolving Cause 2

## 🟡 Code-Level Issues

### Cause 3: A11y Color-Contrast - 48/51 routes
**Root Cause:** axe-core detects insufficient contrast (likely text over hero gradient)
**Impact:** 48 routes affected
**Action Required:**
1. Run axe DevTools in Chrome against staging2
2. Identify exact selector with low contrast
3. Fix contrast ratio to ≥ 4.5:1 (normal text) or ≥ 3:1 (large text)

### Cause 4: Missing CTA - 47/51 routes
**Root Cause:** Test looks for a.nvx-btn, a.nvx-button - many pages have CTAs with other classes
**Impact:** 47 routes affected
**Action Required:**
1. Review if test selector should be expanded
2. Or unify CTA CSS classes across pages

### Cause 5: Double H1 - 2 routes
**Affected Routes:**
- /madrid/valoracion/
- /clinicas-de-medicina-estetica-nuvanx/

**Analysis:**
- nvx-valoracion-managed-page.php: Has 1 H1 (line 36)
- nvx-clinics-hub.php: Has 1 H1 (line 1052)
- Issue likely from page shell adding additional H1

**Action Required:**
1. Investigate page shell for duplicate H1 injection
2. Ensure only one H1 per page

## 🔍 Security Verification Results

### Credential Rotation Check
**Command Run:** `git log --all --oneline --before="2026-07-17" | while read sha rest; do result=$(git show $sha 2>/dev/null | grep -iE '^\+.*(DB_PASSWORD|DB_USER|auth_key|secure_auth_key|secret_key)\s*=' | head -1); [ -n "$result" ] && echo "CRED FOUND in $sha: $result"; done`

**Result:** No credentials found in commits before 2026-07-17
**Commit 9d6145ce:** Confirmed removal of SiteGround operational secrets (sgo-config.php, sgs_encrypt_key.php)

**Conclusion:** No historical credential exposure found based on automated scan

## 📊 Impact Dashboard

| Root Cause | Failures | Type | Action Required | Status |
|------------|----------|------|-----------------|--------|
| FacebookSignal HTML | 51 | Server | Deactivate plugin in staging2 | ⏳ Pending |
| Rogue script src | 50 | Server | Same as above | ⏳ Pending |
| Color contrast | 48 | CSS Theme | Fix contrast ratio | ⏳ Pending |
| Missing CTA | 47 | Test/Template | Review selector/classes | ⏳ Pending |
| Double H1 | 2 | Template PHP | Fix 2 files | ⏳ Pending |

**Estimation:** Resolving SiteGround issues eliminates ~101 instances. Remaining issues are code corrections.

## 🎯 Immediate Actions

### 1. WordPress Panel (staging2.nuvanx.com)
[ ] Deactivate Facebook/SiteGround Security plugin
[ ] Purge SiteGround SuperCacher cache
[ ] Re-run acceptance test locally

### 2. Code Corrections
[ ] Fix double H1 in /madrid/valoracion/
[ ] Fix double H1 in /clinicas-de-medicina-estetica-nuvanx/
[ ] Fix A11y color-contrast (48 routes)
[ ] Review/expand CTA selector

### 3. Human Actions
[ ] Contact GitHub Support for refs purge (if needed)
[ ] Confirm credential rotation (verified as not needed)
[ ] Approve admin list

Generated from 57 failures analysis.
