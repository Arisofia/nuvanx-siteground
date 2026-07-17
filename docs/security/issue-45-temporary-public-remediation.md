# Issue #45 — temporary public remediation mode

Repository visibility is temporarily public only to execute GitHub-hosted Actions
needed for the approved history-remediation workflow after private-repository
Actions limits blocked execution.

## Completed on 17 July 2026

- exact 16,459-path manifest approved with zero current-tree intersection;
- encrypted pre-purge Git bundle created and uploaded before rewriting;
- mutable branches and tags rewritten with `git-filter-repo`;
- atomic force update completed for `refs/heads/*` and `refs/tags/*`;
- fresh-mirror verification passed for mutable refs;
- incident commit is unreachable from branches and tags;
- residual prohibited paths: zero;
- approved paths still reachable from mutable refs: zero;
- Gitleaks: pass;
- `git fsck`: pass.

## Still blocking issue closure

- repository visibility must return to private;
- GitHub Support must dereference the 10 internal pull-request refs that still
  retain the incident object, remove cached commit/diff views and run server-side
  garbage collection;
- access, administrator and credential-rotation decisions must be documented;
- old local clones and deployment workspaces must be discarded and recreated
  from rewritten history.

Production remains SECURITY NO GO. Temporary public mode is not release or merge
approval. Do not add credentials, environment files, database exports, backups
or unredacted scanner reports during the remaining public interval.
