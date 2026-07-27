# Final master exact-SHA validation

This branch starts from master commit `17ec7c34dd4aebbc01dde6d1037f3d26bafe4a42`.

The pull request exists only to execute the complete staging2 validation chain on one immutable SHA after the legacy-route retirement, REST pagination hardening, Sonar cleanup, and rendered-acceptance alignment were merged.

Required evidence:

- Theme Hygiene Gate success.
- Immutable staging2 deployment success.
- Legacy route retirement migration and audit success.
- Ten retired routes return HTTP 410 without `Location` or `X-Redirect-By`.
- Seven retained canonical targets return HTTP 200.
- Rendered acceptance success.
- Real browser visual QA success.
- Full Site UI Audit success with route and viewport counts matching the report.
