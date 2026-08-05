# Incident Closure - Required Human Actions

## IMMEDIATE (Today - Hours)

### 1. Contact GitHub Support for refs purge
**WHAT TO DO:**
1. Open GitHub Support ticket using template in `docs/security/GITHUB-SUPPORT-TICKET-TEMPLATE.md`
2. Fill in your contact information: `<name/email of responsible person>`
3. Submit ticket through GitHub Support interface
4. Document ticket number in `docs/security/INCIDENT-2026-07-15-CLOSURE.md`

**EVIDENCE REQUIRED:**
- GitHub Support ticket number
- Response from GitHub Support confirming purge or alternative instructions
- Copy of response in INCIDENT-2026-07-15-CLOSURE.md

### 2. Confirm credential rotations
**WHAT TO DO:**
1. Review if any credentials were exposed in the historical commit `9d6145ce`
2. If credentials were exposed:
   - Rotate database passwords
   - Rotate SSH keys
   - Rotate API tokens
   - Update all production environments
3. Document rotations in `docs/security/INCIDENT-2026-07-15-CLOSURE.md`

**EVIDENCE REQUIRED:**
- List of credentials rotated (or statement that none were exposed)
- Timestamp of rotation
- Confirmation that all production systems updated
- CI/Secrets management system logs showing updates

### 3. Approve admin list
**WHAT TO DO:**
1. Review current repository administrators
2. Confirm list is appropriate and minimal
3. Document approval in INCIDENT-2026-07-15-CLOSURE.md

**EVIDENCE REQUIRED:**
- Approver name/email
- Approval timestamp
- Approved admin list

## SHORT TERM (1-3 Days)

### 4. Remove production deploy block
**WHAT TO DO:**
After staging2 acceptance is green:
1. Remove `&& false` guard from `.github/workflows/deploy.yml` line 86
2. Commit and push change
3. Verify production deploy workflow works correctly

**ACCEPTANCE CRITERION:**
Production deploy runs after successful staging2 acceptance for same SHA

### 5. Resolve Playwright acceptance failures
**WHAT TO DO:**
Based on P1-FORENSIC-AUDIT-RESULTS.md showing 57 failures:
1. Fix A11Y color-contrast violations on all pages
2. Remove FacebookSignal from initial HTML
3. Remove rogue third-party scripts (HubSpot/Facebook)
4. Add missing CTAs to pages
5. Fix conversion flow form fields and submit button
6. Add mobile menu toggle to mobile pages

**ACCEPTANCE CRITERION:**
Local execution shows zero failures:
```bash
cd scripts/staging2
EXPECTED_SHA=<current> BASE_URL=https://staging2.nuvanx.com node browser-acceptance.mjs
```

## MEDIUM TERM (1-3 Sprints)

### 6. Expand Playwright coverage
**WHAT TO DO:**
- Add tests for all treatment templates
- Add tests for clinic pages
- Add visual regression snapshots
- Implement real conversion flow test with mock HubSpot

### 7. Implement atomic deployment with rollback
**WHAT TO DO:**
- Modify deploy-to-staging2.sh to use candidate directories
- Add symlink activation on success
- Add rollback on failure
- Document process in docs/operations/deployment.md

### 8. Governance documentation
**WHAT TO DO:**
- Update SECURITY.md with incident summary
- Document deploy ownership
- Create operations runbook
- Add incident response procedures

## CHECKLIST FOR AUDIT CLOSURE

- [ ] GitHub Support ticket created with confirmation
- [ ] All exposed credentials rotated (or documented as none exposed)
- [ ] Admin list approved and documented
- [ ] Playwright acceptance passes with zero failures
- [ ] Production deploy only runs after staging2 acceptance
- [ ] gitleaks historical scan shows no unrotated secrets
- [ ] SECURITY.md updated with incident summary
- [ ] INCIDENT-2026-07-15-CLOSURE.md fully documented
- [ ] NVX_ENV production allowlist implemented
- [ ] Silent data fallbacks removed

## GENERATED WITH DEVIN
This document was created as part of the forensic audit closure process.
Generated with [Devin](https://devin.ai)