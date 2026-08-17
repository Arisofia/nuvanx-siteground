from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text()


def replace_once(old: str, new: str, label: str) -> None:
    global text
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{label}: expected 1 occurrence, found {count}")
    text = text.replace(old, new, 1)


replace_once(
    "          git merge-base --is-ancestor origin/master \"$PR_SHA\" || { echo 'Rebase PR onto current master before preview.' >&2; exit 1; }\n",
    "",
    "remove-ancestor-policy",
)

old_candidate = '''          git diff --check origin/master "$PR_SHA"
          ! git ls-tree -r "$PR_SHA" wp-content/themes/nuvanx-medical/ | awk '$1 == "120000" { found=1 } END { exit(found ? 0 : 1) }'
          CANDIDATE_ROOT="$RUNNER_TEMP/nvx-pr-${PR_NUMBER}-${PR_SHA}"
          git worktree add --detach "$CANDIDATE_ROOT" "$PR_SHA"
          echo "CANDIDATE_ROOT=$CANDIDATE_ROOT" >> "$GITHUB_ENV"
          find "$CANDIDATE_ROOT/wp-content/themes/nuvanx-medical" -path '*/vendor' -prune -o -name '*.php' -type f -print0 | xargs -0 -n1 php -l >/dev/null
          find "$CANDIDATE_ROOT/wp-content/themes/nuvanx-medical" -name '*.js' -type f -print0 | xargs -0 -r -n1 node --check >/dev/null
          echo 'PR_BOUNDARY=PASS'
'''
new_candidate = '''          CANDIDATE_ROOT="$RUNNER_TEMP/nvx-pr-${PR_NUMBER}-${PR_SHA}"
          git worktree add --detach "$CANDIDATE_ROOT" origin/master
          set +e
          git -C "$CANDIDATE_ROOT" -c user.name='NUVANX CI' -c user.email='ci@nuvanx.invalid' merge --no-commit --no-ff "$PR_SHA"
          merge_rc=$?
          set -e
          if (( merge_rc != 0 )); then
            git -C "$CANDIDATE_ROOT" merge --abort || true
            echo 'PR preview merge conflict against current master; resolve or rebase the PR.' >&2
            exit 1
          fi
          git -C "$CANDIDATE_ROOT" -c user.name='NUVANX CI' -c user.email='ci@nuvanx.invalid' commit --no-gpg-sign -m "ci: preview merge PR #${PR_NUMBER} into master"
          PR_PREVIEW_SHA="$(git -C "$CANDIDATE_ROOT" rev-parse HEAD)"
          [[ "$PR_PREVIEW_SHA" =~ ^[0-9a-f]{40}$ ]]
          git -C "$CANDIDATE_ROOT" diff --check origin/master "$PR_PREVIEW_SHA"
          ! git -C "$CANDIDATE_ROOT" ls-tree -r "$PR_PREVIEW_SHA" wp-content/themes/nuvanx-medical/ | awk '$1 == "120000" { found=1 } END { exit(found ? 0 : 1) }'
          echo "CANDIDATE_ROOT=$CANDIDATE_ROOT" >> "$GITHUB_ENV"
          echo "PR_PREVIEW_SHA=$PR_PREVIEW_SHA" >> "$GITHUB_ENV"
          find "$CANDIDATE_ROOT/wp-content/themes/nuvanx-medical" -path '*/vendor' -prune -o -name '*.php' -type f -print0 | xargs -0 -n1 php -l >/dev/null
          find "$CANDIDATE_ROOT/wp-content/themes/nuvanx-medical" -name '*.js' -type f -print0 | xargs -0 -r -n1 node --check >/dev/null
          echo "PR_BOUNDARY=PASS pr_sha=$PR_SHA preview_sha=$PR_PREVIEW_SHA"
'''
replace_once(old_candidate, new_candidate, "merge-preview-candidate")

replace_once(
    '          ROLLBACK_DIR="$STAGING_PARENT/.nvx-rollback/pr-${PR_NUMBER}-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}-${PR_SHA}"\n          REMOTE_RELEASE="$STAGING_ROOT/wp-content/.nuvanx-deployments/pr-${PR_NUMBER}-${PR_SHA}-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"\n',
    '          ROLLBACK_DIR="$STAGING_PARENT/.nvx-rollback/pr-${PR_NUMBER}-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}-${PR_PREVIEW_SHA}"\n          REMOTE_RELEASE="$STAGING_ROOT/wp-content/.nuvanx-deployments/pr-${PR_NUMBER}-${PR_PREVIEW_SHA}-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"\n',
    "preview-path-identity",
)

replace_once(
    "          ssh nvx-staging2-pr \"NUVANX_CONFIRM=yes bash '$REMOTE_RELEASE/deploy-to-staging2.sh' --wp-root '$STAGING_ROOT' --source-theme '$REMOTE_RELEASE/theme' --sha '$PR_SHA' --confirm\"\n",
    "          ssh nvx-staging2-pr \"NUVANX_CONFIRM=yes bash '$REMOTE_RELEASE/deploy-to-staging2.sh' --wp-root '$STAGING_ROOT' --source-theme '$REMOTE_RELEASE/theme' --sha '$PR_PREVIEW_SHA' --confirm\"\n",
    "deploy-preview-sha",
)

replace_once(
    "          ssh nvx-staging2-pr \"STAGING_ROOT='$STAGING_ROOT' PR_SHA='$PR_SHA' bash -se\" <<'REMOTE'\n",
    "          ssh nvx-staging2-pr \"STAGING_ROOT='$STAGING_ROOT' PR_SHA='$PR_SHA' PR_PREVIEW_SHA='$PR_PREVIEW_SHA' bash -se\" <<'REMOTE'\n",
    "remote-preview-env",
)

replace_once(
    "          test \"$(tr -d '\\r\\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)\" = \"$PR_SHA\"\n",
    "          test \"$(tr -d '\\r\\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)\" = \"$PR_PREVIEW_SHA\"\n",
    "remote-preview-marker",
)

replace_once(
    '          echo "PR_STAGING_IDENTITY=PASS sha=$PR_SHA"\n',
    '          echo "PR_STAGING_IDENTITY=PASS pr_sha=$PR_SHA preview_sha=$PR_PREVIEW_SHA"\n',
    "preview-identity-log",
)

replace_once(
    '          EXPECTED_SHA: ${{ env.PR_SHA }}\n',
    '',
    "remove-pr-sha-browser-env",
)

old_browser = '''        run: |
          set -euo pipefail
          ssh nvx-staging2-pr "cd '$STAGING_ROOT' && test \"\$(tr -d '\\r\\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)\" = '$PR_SHA'"
          pass=0; for attempt in {1..12}; do if node scripts/staging2/verify-staging-boundary.mjs; then pass=1; break; fi; sleep 10; done; test "$pass" = 1
'''
new_browser = '''        run: |
          set -euo pipefail
          export EXPECTED_SHA="$PR_PREVIEW_SHA"
          ssh nvx-staging2-pr "cd '$STAGING_ROOT' && test \"\$(tr -d '\\r\\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)\" = '$PR_PREVIEW_SHA'"
          pass=0; for attempt in {1..12}; do if node scripts/staging2/verify-staging-boundary.mjs; then pass=1; break; fi; sleep 10; done; test "$pass" = 1
'''
replace_once(old_browser, new_browser, "browser-preview-identity")

replace_once(
    '          rm -f "$HOME/.ssh/staging_pr_key" "$HOME/.ssh/known_hosts" "$HOME/.ssh/config"\n',
    '          rm -f "$HOME/.ssh/staging_pr_key" "$HOME/.ssh/known_hosts" "$HOME/.ssh/config"\n          [[ -z "${CANDIDATE_ROOT:-}" ]] || git worktree remove --force "$CANDIDATE_ROOT" 2>/dev/null || true\n',
    "cleanup-preview-worktree",
)

if "Rebase PR onto current master before preview." in text:
    raise SystemExit("obsolete ancestor failure message still present")

required = [
    'merge --no-commit --no-ff "$PR_SHA"',
    'PR_PREVIEW_SHA="$(git -C "$CANDIDATE_ROOT" rev-parse HEAD)"',
    "--sha '$PR_PREVIEW_SHA' --confirm",
    'export EXPECTED_SHA="$PR_PREVIEW_SHA"',
    'git worktree remove --force "$CANDIDATE_ROOT"',
]
for marker in required:
    if marker not in text:
        raise SystemExit(f"missing required marker: {marker}")

path.write_text(text)
