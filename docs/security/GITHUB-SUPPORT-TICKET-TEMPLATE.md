# GitHub Support Ticket Template

## Subject: Purge refs/pull/*/head linking to pre-rewrite objects for repo Arisofia/nuvanx-siteground

Hello GitHub Support,

We performed a history rewrite on repository Arisofia/nuvanx-siteground (public) to purge sensitive blobs after an accidental commit on 2026-07-15. Some pull refs may still reference pre-rewrite objects (refs/pull/*/head) and could allow recovery of objects we attempted to remove.

Please:
1) Search for any refs under refs/pull/*/head that reference objects introduced before 2026-07-15 and remove/purge them.
2) Confirm by reply which refs (if any) were purged and provide a ticket number for audit.

**Repo:** https://github.com/Arisofia/nuvanx-siteground
**Commit range rewritten:** before 2026-07-16
**Contact:** <name/email of responsible person>

Thanks,
<your name>

---

## Instructions for Use
1. Copy the subject line to the GitHub Support ticket subject field
2. Copy the body content to the ticket description
3. Fill in the placeholder `<name/email of responsible person>` with actual contact
4. Submit the ticket through GitHub Support interface
5. Once you receive a response, document the ticket number and confirmation in `docs/security/INCIDENT-2026-07-15-CLOSURE.md`

## Checklist for Incident Closure
- [ ] GitHub Support ticket created with above template
- [ ] Response received with ticket number
- [ ] Confirmation of which refs were purged (if any)
- [ ] Documentation of response in INCIDENT-2026-07-15-CLOSURE.md
- [ ] Any confirmed secret rotations documented
- [ ] Admin list approval obtained and documented