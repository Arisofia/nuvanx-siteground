# Issue #45 — Git history purge and credential incident closure

## Status

**Production release remains NO GO until every exit criterion in this document is complete.**

Verified repository facts as of 2026-07-17:

- repository: `Arisofia/nuvanx-siteground`;
- visibility: private;
- default branch: `master`;
- historical incident commit: `81dafb2e74b6bbfffe302979d67fe789c0075509` remains reachable before remediation;
- canonical deployable theme: `wp-content/themes/nuvanx-medical`;
- `wp-content/themes/nuvanx-medical-wpvibe-draft` is non-canonical and must not remain reachable;
- current-tree deletion or `git rm` is not incident closure.

Do not store any old or current secret value in this repository, issue, pull request, evidence file or support ticket attachment.

## Decisions still requiring an accountable human record

Record dates, reviewers and yes/no outcomes only. Do not record secret values.

| Control | Current status |
|---|---|
| Historical database password rotated | **UNCONFIRMED** |
| Current WordPress administrator list and ownership reviewed | **UNCONFIRMED** |
| GitHub collaborators reviewed | **UNCONFIRMED** |
| Deploy keys reviewed | **UNCONFIRMED** |
| Actions secrets reviewed | **UNCONFIRMED** |
| External integrations reviewed | **UNCONFIRMED** |
| Production active theme slug verified as `nuvanx-medical` | **UNCONFIRMED** |
| Staging2 active theme slug verified as `nuvanx-medical` | **UNCONFIRMED** |
| Repository remains private | **VERIFIED** |

## Required tools

Use an isolated administrative machine or disposable VM with:

- Git;
- `git-filter-repo` version 2.47 or newer, including `--sensitive-data-removal`;
- Gitleaks;
- Python 3;
- administrative GitHub credentials capable of temporarily managing branch rules and force-updating all mutable refs.

Copy these files outside the repository that will be rewritten:

- `issue-45-build-manifest.py`;
- `issue-45-inventory.sh`;
- `issue-45-purge.sh`;
- `issue-45-verify.sh`.

The tools must remain available after `git-filter-repo` rewrites the mirror.

## Phase 0 — freeze and coordination

1. Freeze production deployment and merges.
2. Notify every person or automation with a clone.
3. Stop CI runners, deployment workspaces and scheduled jobs that can push.
4. Record all open PRs. At the time this runbook was created, PR #52 was open; merge it before the rewrite or recreate it from clean history afterward.
5. Confirm the rotated WordPress salts and session invalidation already completed remain active.
6. Decide whether the historical database password has been rotated. If not, rotate it before the history rewrite.
7. Review GitHub collaborators, deploy keys, Actions secrets and integrations.

## Phase 1 — isolated mirror and offline bundle

From a clean administrative directory:

```bash
git clone --mirror git@github.com:Arisofia/nuvanx-siteground.git nuvanx-siteground-purge.git
mkdir -p issue-45-evidence/pre issue-45-evidence/rewrite issue-45-tools
```

Copy the four security scripts from a clean source checkout into `issue-45-tools/`, then make the shell scripts executable:

```bash
chmod 700 issue-45-tools/issue-45-*.sh
```

The purge script creates the bundle before modifying history. Store the bundle offline with restricted access. It contains the compromised history and must never be uploaded to GitHub, Drive, Slack or an issue.

## Phase 2 — complete object inventory

Run:

```bash
./issue-45-tools/issue-45-inventory.sh \
  ./nuvanx-siteground-purge.git \
  ./issue-45-evidence/pre \
  ./issue-45-tools
```

Review both files line by line:

```text
issue-45-evidence/pre/candidate-purge-paths.txt
issue-45-evidence/pre/candidate-purge-reasons.tsv
```

The candidate manifest is generated from `git rev-list --objects --all`, not only from the current tree. It covers:

- historical WordPress configuration and environment files;
- root WordPress core;
- Duplicator/installers/backups/database dumps and archives;
- uploads and runtime caches;
- third-party plugins;
- every theme except `nuvanx-medical`;
- the legacy draft theme;
- QA, staging and visual artifacts;
- paths found by the redacted all-history Gitleaks scan.

Create an approved copy only after review:

```bash
cp issue-45-evidence/pre/candidate-purge-paths.txt \
   issue-45-evidence/approved-purge-paths.txt
```

Do not add globs or regexes to the approved file. It must contain exact Git paths, one per line. This prevents an unreviewed broad deletion.

## Phase 3 — local rewrite and pre-push verification

Run first **without** force-push authorization:

```bash
./issue-45-tools/issue-45-purge.sh \
  ./nuvanx-siteground-purge.git \
  ./issue-45-evidence/approved-purge-paths.txt \
  ./nuvanx-siteground-before-purge.bundle \
  ./issue-45-evidence/rewrite \
  ./issue-45-tools
```

This command:

1. creates and hashes the offline bundle;
2. rewrites all local branches, tags and refs with `--sensitive-data-removal`;
3. restores the `origin` remote if `git-filter-repo` removes it;
4. verifies that the old commit is not reachable;
5. verifies every approved and prohibited path is absent;
6. runs Gitleaks across all rewritten history;
7. runs `git fsck`;
8. records affected pull-request refs from `.git/filter-repo/changed-refs`.

Review all evidence before continuing. In particular:

```text
issue-45-evidence/rewrite/git-filter-repo-output.txt
issue-45-evidence/rewrite/pre-push-verification/verification-summary.txt
issue-45-evidence/rewrite/affected-pull-request-count.txt
issue-45-evidence/rewrite/changed-refs.txt
```

## Phase 4 — force-update GitHub

Temporarily suspend only the branch protections/rulesets that prevent the approved mirror update. Record who approved the suspension and when.

Run:

```bash
ISSUE45_FORCE_PUSH=YES \
./issue-45-tools/issue-45-purge.sh \
  ./nuvanx-siteground-purge.git \
  ./issue-45-evidence/approved-purge-paths.txt \
  ./nuvanx-siteground-before-purge.bundle \
  ./issue-45-evidence/rewrite \
  ./issue-45-tools
```

Expected push failures are limited to GitHub-managed read-only `refs/pull/*`. Any other ref failure must be resolved before proceeding.

Re-enable the protections immediately after the mirror push.

## Phase 5 — fresh-clone proof

Delete the rewritten local mirror or move it offline, then create a new mirror from GitHub:

```bash
git clone --mirror git@github.com:Arisofia/nuvanx-siteground.git nuvanx-siteground-fresh-verify.git
mkdir -p issue-45-evidence/post

./issue-45-tools/issue-45-verify.sh \
  ./nuvanx-siteground-fresh-verify.git \
  ./issue-45-evidence/approved-purge-paths.txt \
  ./issue-45-evidence/post \
  ./issue-45-tools
```

The verification must prove:

- `81dafb2e74b6bbfffe302979d67fe789c0075509` is not reachable;
- no approved path is reachable from any fetched branch or tag;
- no prohibited candidate path remains;
- all-history Gitleaks exits zero;
- `git fsck` passes;
- every intended branch and tag head exists at a rewritten commit.

Repository size reduction is supporting evidence only, not proof.

## Phase 6 — GitHub Support purge

Open a GitHub Support sensitive-data-removal request after the force-push. Provide:

- repository owner/name;
- number of affected PRs from `affected-pull-request-count.txt`;
- the first changed commit(s) reported by `git-filter-repo`;
- whether orphaned LFS objects were reported.

Ask GitHub Support to dereference affected pull requests, remove cached views and run server-side garbage collection. Do not attach the offline bundle or any secret value.

## Phase 7 — clone and integration cleanup

1. Delete every old local clone, CI workspace and deployment clone.
2. Re-clone from rewritten history.
3. Recreate or rebase outstanding work from clean history. Never merge an old branch into the rewritten repository.
4. Audit forks, if any, and coordinate deletion or rewrite.
5. Confirm deploy keys, Actions secrets and external integrations still operate after re-cloning.

## Phase 8 — staging2 and closure

From a fresh clone:

1. deploy the canonical theme to staging2;
2. verify the active theme slug is `nuvanx-medical`;
3. run PHP, CSS, security and smoke tests;
4. capture staging2 evidence without personal or environment data;
5. verify production also reports the canonical theme slug;
6. complete the access/credential decision table above;
7. attach path-only/count-only verification evidence to issue #45;
8. close issue #45 only after GitHub Support confirms cache/reference handling.

Production remains NO GO until all phases are complete.
