#!/usr/bin/env python3
import re
from pathlib import Path

workflow = Path('.github/workflows/production.yml')
text = workflow.read_text(encoding='utf-8')
marker = '>>>>>>> Stashed changes\n'
count = text.count(marker)
if count != 1:
    raise SystemExit(f'expected exactly one stashed conflict marker, found {count}')
text = text.replace(marker, '', 1)
workflow.write_text(text, encoding='utf-8')

contract = Path('scripts/ci/test-release-regression-contract.sh')
ctext = contract.read_text(encoding='utf-8')
anchor = '''done

# Bridal retirement must remain an AND condition.'''
insert = '''done

# Workflow files must never contain unresolved merge/stash conflict markers.
# A single marker makes GitHub register the file without its declared name or
# workflow_dispatch trigger, which can silently disable the production control plane.
if grep -RInE '^[[:space:]]*(<<<<<<<|=======|>>>>>>>)' "$ROOT/.github/workflows" --include='*.yml' --include='*.yaml'; then
  fail 'workflow_conflict_marker_present'
fi
pass_assert 'workflow-no-conflict-markers'

# Bridal retirement must remain an AND condition.'''
if ctext.count(anchor) != 1:
    raise SystemExit(f'expected one release-contract insertion anchor, found {ctext.count(anchor)}')
ctext = ctext.replace(anchor, insert, 1)
contract.write_text(ctext, encoding='utf-8')

marker_re = re.compile(r'^\s*(?:<<<<<<<|=======|>>>>>>>)')
for path in Path('.github/workflows').glob('*.y*ml'):
    for lineno, line in enumerate(path.read_text(encoding='utf-8').splitlines(), start=1):
        if marker_re.match(line):
            raise SystemExit(f'unresolved conflict marker remains in {path}:{lineno}')

print('PRODUCTION_WORKFLOW_CONFLICT_REPAIR=PASS files=2')
