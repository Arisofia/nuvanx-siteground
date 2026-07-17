# Issue #45 — temporary public remediation mode

Repository visibility is temporarily public only to execute GitHub-hosted Actions
needed for the approved history-remediation workflow after private-repository
Actions limits blocked execution.

This does not change the incident status:

- production remains SECURITY NO GO;
- the closure gate remains red while the incident commit or prohibited paths are reachable;
- the closure gate also remains red after the rewrite until repository visibility returns to private;
- public-remediation mode is not release or merge approval;
- the repository must be changed back to private immediately after fresh-clone verification and before issue #45 can close.

Do not add credentials, environment files, database exports, backups or unredacted
scanner reports during the temporary public interval.
