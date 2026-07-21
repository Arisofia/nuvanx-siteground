# Staging2 production-readiness closure — Phase 2

This runbook closes the operational work left after the production-readiness architecture was merged.

## Objective

Obtain reproducible evidence for the Staging2 deployment, migration and rendered QA without changing production and without relying on copied screenshots or partial console output.

## Workflow modes

Run **Actions → Deploy Staging2 (manual)** against the exact branch and 40-character HEAD SHA.

### 1. `PREFLIGHT_ONLY`

Use this first after any failed run. It is read-only and validates:

- SSH connectivity using the pinned host key;
- required remote commands and runtime versions;
- exact WordPress root, `siteurl`, `home` and active theme;
- database integrity through `wp db check`;
- available disk space;
- read and traversal permissions for the WordPress and theme directories;
- write permission for the private backup and deployment roots;
- `EMPTY_TRASH_DAYS >= 1`;
- current deployed SHA marker;
- HTTP 200 from the Staging2 home page.

Expected marker:

```text
STAGING2_PREFLIGHT_OK
```

No theme files, database rows, menus, redirects or caches are modified in this mode.

### 2. `DEPLOY_AND_MIGRATE`

Run only after preflight passes. This mode:

1. uploads an immutable theme and migration payload;
2. creates the private theme and database backup;
3. deploys the theme;
4. runs `audit --allow-pending`;
5. applies the governed production-readiness migration;
6. runs the blocking post-migration audit;
7. executes the rendered smoke test;
8. verifies the deployed SHA marker;
9. executes an independent smoke test from the GitHub runner;
10. collects post-run diagnostics.

Expected markers:

```text
DEPLOY_STAGING2_OK
SMOKE_VERIFY_OK
Production-readiness audit passed.
```

A post-mutation failure must restore the theme and database and emit:

```text
ROLLBACK_COMPLETE
```

### 3. `SMOKE_ONLY`

Use after a successful deployment or after cache/CDN maintenance. This mode runs preflight and the independent rendered smoke test without uploading or mutating the theme or database.

## Required evidence artifact

Every manual execution uploads:

```text
staging2-deployment-evidence-<run-id>-<attempt>
```

The artifact is retained for 30 days and may contain:

- `run-context.txt`;
- `ssh-debug.log`;
- `ssh-connectivity.log`;
- `preflight.log`;
- `remote-release.txt`;
- `remote-deploy.log`;
- `deployed-marker.log`;
- `independent-smoke.log`;
- `postflight.log`.

The artifact must be downloaded before re-running a failed attempt. The first failing file and last successful marker determine the next correction:

| Last successful evidence | Failure domain |
|---|---|
| No `SSH_CONNECTION_OK` | GitHub environment secrets, key, port or pinned host key |
| SSH passes, no `STAGING2_PREFLIGHT_OK` | SiteGround runtime, WordPress identity, permissions, disk, DB or trash configuration |
| Preflight passes, payload upload fails | Remote deployment directory or rsync transport |
| Payload passes, no `DEPLOY_STAGING2_OK` | Backup, theme sync, migration, rollback or remote smoke |
| Remote deploy passes, marker fails | Theme marker path or synchronization integrity |
| Marker passes, independent smoke fails | Public routing, cache/CDN, redirects or rendered content |

## Automated acceptance

The run is technically accepted only when all of the following are true:

- exact selected ref and SHA match;
- `STAGING2_PREFLIGHT_OK`;
- `DEPLOY_STAGING2_OK` for a deployment run;
- post-migration audit passes;
- `SMOKE_VERIFY_OK` from the remote deployment;
- `SMOKE_VERIFY_OK` from the independent runner verification;
- deployed SHA marker equals the selected immutable SHA;
- no rollback warning remains unresolved.

## Manual rendered QA

After the automated pass, validate desktop and mobile rendering for:

- `/tratamientos/`;
- `/protocolos-signature/`;
- `/remodelacion-corporal-laser-madrid/`;
- `/por-que-nuvanx/`;
- `/inversion-medicina-estetica/`.

Validate 301 redirects for:

- `/liposculpt-air/`;
- `/v-lift-awake/`;
- `/tratamiento-postparto-abdomen-contorno-corporal-madrid/`.

Confirm H1, navigation, CTA, canonical, robots, Schema and absence of prototype or provisional terminology.

## Production gate

Do not promote to production until the same candidate state has:

1. passed `DEPLOY_AND_MIGRATE` in Staging2;
2. passed independent smoke;
3. completed desktop and mobile QA;
4. recorded the evidence artifact and exact deployed SHA;
5. passed all required repository checks.

Production remains a separate, explicitly authorized operation.
