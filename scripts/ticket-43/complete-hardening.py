from pathlib import Path

HTML_PATH = Path('deploy/ticket-43/post_content_v3-production-copy.html')
CSS_PATH = Path('wp-content/themes/nuvanx-medical/assets/css/nvx-brand-home.css')
WORKFLOW_PATH = Path('.github/workflows/ticket43-v4-delivery.yml')


def replace_once(text: str, old: str, new: str, label: str) -> str:
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected exactly one match, found {count}')
    return text.replace(old, new, 1)


html = HTML_PATH.read_text(encoding='utf-8')
html = replace_once(
    html,
    '<p class="nvx-brand-body nvx-home-editorial__lead">\nUbicadas estratégicamente',
    '<p class="nvx-brand-body nvx-home-editorial__body">\nUbicadas estratégicamente',
    'semantic intro body',
)
if html.count('nvx-home-editorial__lead') != 1:
    raise SystemExit('Expected exactly one semantic intro lead class')
if html.count('nvx-home-editorial__body') != 1:
    raise SystemExit('Expected exactly one semantic intro body class')
HTML_PATH.write_text(html, encoding='utf-8')

css = CSS_PATH.read_text(encoding='utf-8')
css = replace_once(
    css,
    '.nvx-home-editorial__lead:first-of-type {',
    '.nvx-home-editorial__lead {',
    'intro lead selector',
)
css = replace_once(
    css,
    '.nvx-home-editorial__lead:nth-of-type(2) {',
    '.nvx-home-editorial__body {',
    'intro body selector',
)
if 'nth-of-type' in css or 'nth-child' in css:
    raise SystemExit('Positional selectors remain in home CSS')
CSS_PATH.write_text(css, encoding='utf-8')

workflow = WORKFLOW_PATH.read_text(encoding='utf-8')

target_input = """      target_sha:
        description: 'Exact 40-character candidate SHA to deploy to staging2'
        required: true
        type: string
"""
workflow = replace_once(
    workflow,
    target_input,
    target_input + """      base_sha:
        description: 'Exact 40-character base SHA used for candidate scope validation'
        required: true
        type: string
""",
    'base_sha input',
)

workflow = replace_once(
    workflow,
    '          TARGET: ${{ inputs.target_sha }}\n',
    '          TARGET: ${{ inputs.target_sha }}\n          BASE: ${{ inputs.base_sha }}\n',
    'base_sha environment',
)

old_resolve = """          [[ "$TARGET" =~ ^[0-9a-f]{40}$ ]] || {
            echo 'ERROR: target_sha must be exactly 40 lowercase hexadecimal characters'
            exit 1
          }
          git fetch origin master
          git cat-file -e "${TARGET}^{commit}"
          git merge-base --is-ancestor origin/master "$TARGET" || {
            echo 'ERROR: candidate must contain current origin/master'
            exit 1
          }
          git checkout --detach "$TARGET"
          echo "sha=$TARGET" >> "$GITHUB_OUTPUT"
"""
new_resolve = """          [[ "$TARGET" =~ ^[0-9a-f]{40}$ ]] || {
            echo 'ERROR: target_sha must be exactly 40 lowercase hexadecimal characters'
            exit 1
          }
          [[ "$BASE" =~ ^[0-9a-f]{40}$ ]] || {
            echo 'ERROR: base_sha must be exactly 40 lowercase hexadecimal characters'
            exit 1
          }
          [[ "$TARGET" != "$BASE" ]] || {
            echo 'ERROR: target_sha and base_sha must be different commits'
            exit 1
          }
          git fetch origin master
          git cat-file -e "${TARGET}^{commit}"
          git cat-file -e "${BASE}^{commit}"
          git merge-base --is-ancestor origin/master "$TARGET" || {
            echo 'ERROR: candidate must contain current origin/master'
            exit 1
          }
          git merge-base --is-ancestor "$BASE" "$TARGET" || {
            echo 'ERROR: base_sha must be an ancestor of target_sha'
            exit 1
          }
          git checkout --detach "$TARGET"
          echo "sha=$TARGET" >> "$GITHUB_OUTPUT"
          echo "base=$BASE" >> "$GITHUB_OUTPUT"
"""
workflow = replace_once(workflow, old_resolve, new_resolve, 'candidate resolution')

old_scope = """          TARGET="${{ steps.candidate.outputs.sha }}"
          mkdir -p qa/results qa/screenshots
          git diff --name-only origin/master..."$TARGET" | tee qa/results/editorial-changed-files.txt
          git diff --stat origin/master..."$TARGET" | tee qa/results/editorial-diff-stat.txt
"""
new_scope = """          TARGET="${{ steps.candidate.outputs.sha }}"
          BASE="${{ steps.candidate.outputs.base }}"
          mkdir -p qa/results qa/screenshots
          git diff --name-only "$BASE" "$TARGET" | tee qa/results/editorial-changed-files.txt
          git diff --stat "$BASE" "$TARGET" | tee qa/results/editorial-diff-stat.txt
          test -s qa/results/editorial-changed-files.txt || {
            echo 'ERROR: candidate scope is empty'
            exit 1
          }
"""
workflow = replace_once(workflow, old_scope, new_scope, 'candidate scope')
workflow = replace_once(
    workflow,
    '          INVALID="$(git diff --name-only origin/master..."$TARGET" | grep -Ev "$ALLOWED" || true)"\n',
    '          INVALID="$(git diff --name-only "$BASE" "$TARGET" | grep -Ev "$ALLOWED" || true)"\n',
    'scope invalid-file command',
)

old_positional = """          if grep -nE 'nth-child' "$HOME_CSS" "$COMPONENTS"; then
            echo 'ERROR: nth-child positional component styling detected'
            exit 1
          fi
"""
new_positional = """          if grep -nE 'nth-child|nth-of-type' "$HOME_CSS" "$COMPONENTS"; then
            echo 'ERROR: positional component styling detected'
            exit 1
          fi
"""
workflow = replace_once(workflow, old_positional, new_positional, 'positional selector gate')

semantic_anchor = """          grep -q 'class="nvx-index-item__number"' "$HTML"
"""
semantic_checks = semantic_anchor + """          grep -q 'class="nvx-brand-body nvx-home-editorial__lead"' "$HTML"
          grep -q 'class="nvx-brand-body nvx-home-editorial__body"' "$HTML"
          [[ "$(grep -o 'nvx-home-editorial__lead' "$HTML" | wc -l)" -eq 1 ]]
          [[ "$(grep -o 'nvx-home-editorial__body' "$HTML" | wc -l)" -eq 1 ]]
"""
workflow = replace_once(workflow, semantic_anchor, semantic_checks, 'semantic HTML gates')

parse_block = """          for css in \\
            "$THEME/assets/css/nvx-tokens.css" \\
            "$THEME/assets/css/nvx-tokens.min.css" \\
            "$THEME/assets/css/nvx-components.css" \\
            "$THEME/assets/css/nvx-components.min.css" \\
            "$THEME/assets/css/nvx-brand-home.css" \\
            "$THEME/assets/css/nvx-brand-home.min.css"; do
            test -s "$css"
            npx --yes clean-css-cli@5.6.3 -o "/tmp/$(basename "$css").parsed.css" "$css"
            test -s "/tmp/$(basename "$css").parsed.css"
          done
"""
equivalence = parse_block + """
          for stem in nvx-tokens nvx-components nvx-brand-home; do
            source="$THEME/assets/css/${stem}.css"
            committed="$THEME/assets/css/${stem}.min.css"
            expected="/tmp/${stem}.expected.min.css"
            npx --yes clean-css-cli@5.6.3 -o "$expected" "$source"
            if ! cmp -s "$expected" "$committed"; then
              echo "ERROR: ${stem}.min.css is not the deterministic minification of ${stem}.css"
              diff -u "$committed" "$expected" || true
              exit 1
            fi
          done
"""
workflow = replace_once(workflow, parse_block, equivalence, 'deterministic CSS equivalence gate')

workflow = replace_once(
    workflow,
    '            "sha": "${{ steps.candidate.outputs.sha }}",\n',
    '            "sha": "${{ steps.candidate.outputs.sha }}",\n            "base_sha": "${{ steps.candidate.outputs.base }}",\n',
    'delivery manifest base SHA',
)
WORKFLOW_PATH.write_text(workflow, encoding='utf-8')
