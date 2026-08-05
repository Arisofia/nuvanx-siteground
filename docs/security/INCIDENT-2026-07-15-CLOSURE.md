# INCIDENT 2026-07-15 — Closure & Evidence

This document records closure activities for the incident originally described in docs/security/INCIDENT-2026-07-15.md (deleted in later cleanup commit). Use this file to store verifiable evidence of completed actions.

## Summary
- **Incident:** accidental commit of WordPress backup with auth salts to public repository.
- **First reported:** 2026-07-15
- **Initial remediation:** repository set private, credentials rotated in production, sessions invalidated, history rewritten with git filter-repo (see local-secure copy).

## Closure checklist

- [ ] **GitHub Support PR refs purge requested**
  - Ticket: <GH Ticket #>
  - Request date:
  - Support response (copy/paste excerpt):
  - Completed: [yes/no] Date:

- [ ] **Confirm DB credential rotation**
  - Rotated by: <name/email>
  - Rotation timestamp:
  - Evidence: (command output / audit log / CI secret update)

- [ ] **Admin list approved**
  - Approver: <name/email>
  - Approval timestamp:
  - Evidence:

- [ ] **Retained evidence in repo:** this closure doc + secure attach of sanitized logs
- [ ] **Lessons learned and required process changes added to SECURITY.md**

## Append evidence below
(Paste GitHub Support ticket text, rotation logs, commands, screenshots). Do not include secrets in this document.

---

## Audit References
- Commit that removed original incident doc: `22f151d4` (cleanup for client delivery)
- Recovery document created: `docs/security/INCIDENT-2026-07-15-RECOVERY.md`
- Forensic audit confirmation: commit `9d6145ce` removed SiteGround operational secrets