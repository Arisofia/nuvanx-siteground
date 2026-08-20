#!/usr/bin/env python3
from pathlib import Path

path = Path('.github/workflows/production.yml')
text = path.read_text(encoding='utf-8')
old = '''          rsync -az "$CANDIDATE_ROOT/tools/migrations/audit-content-divergence.php" "nvx-prod:$REMOTE_RELEASE/tools/migrations/"
          rsync -az "$CANDIDATE_ROOT/tools/migrations/content-hygiene-shared.php" "nvx-prod:$REMOTE_RELEASE/tools/migrations/"
          rsync -az "$CANDIDATE_ROOT/tools/migrations/governed-blog-markdown-hygiene.php" "nvx-prod:$REMOTE_RELEASE/tools/migrations/"
          rsync -az "$CANDIDATE_ROOT/tools/migrations/content-normalizer.php" "nvx-prod:$REMOTE_RELEASE/tools/migrations/"
          rsync -az "$CANDIDATE_ROOT/tools/migrations/reconcile-publication-robots.php" "nvx-prod:$REMOTE_RELEASE/tools/migrations/"
          echo "PRODUCTION_PAYLOAD=PASS exact_sha=$CANDIDATE_SHA"
'''
new = '''          # Keep the release payload in lockstep with deploy-to-prod.sh. The deploy
          # script owns the exact migration set; synchronizing the whole accepted
          # directory prevents future required migrations from being omitted here.
          rsync -az --delete "$CANDIDATE_ROOT/tools/migrations/" "nvx-prod:$REMOTE_RELEASE/tools/migrations/"
          required_migrations=(
            audit-content-divergence.php
            content-hygiene-shared.php
            governed-blog-markdown-hygiene.php
            content-normalizer.php
            reconcile-publication-robots.php
            reconcile-publication-indexables.php
            run-yoast-indexable-rebuild.php
            audit-publication-sitemap-selection.php
            invalidate-publication-sitemap-cache.php
          )
          for migration in "${required_migrations[@]}"; do
            test -s "$CANDIDATE_ROOT/tools/migrations/$migration"
          done
          ssh nvx-prod "test -s '$REMOTE_RELEASE/tools/migrations/audit-content-divergence.php' && test -s '$REMOTE_RELEASE/tools/migrations/content-hygiene-shared.php' && test -s '$REMOTE_RELEASE/tools/migrations/governed-blog-markdown-hygiene.php' && test -s '$REMOTE_RELEASE/tools/migrations/content-normalizer.php' && test -s '$REMOTE_RELEASE/tools/migrations/reconcile-publication-robots.php' && test -s '$REMOTE_RELEASE/tools/migrations/reconcile-publication-indexables.php' && test -s '$REMOTE_RELEASE/tools/migrations/run-yoast-indexable-rebuild.php' && test -s '$REMOTE_RELEASE/tools/migrations/audit-publication-sitemap-selection.php' && test -s '$REMOTE_RELEASE/tools/migrations/invalidate-publication-sitemap-cache.php'"
          echo "PRODUCTION_MIGRATION_PAYLOAD=PASS required=9 exact_sha=$CANDIDATE_SHA"
          echo "PRODUCTION_PAYLOAD=PASS exact_sha=$CANDIDATE_SHA"
'''
count = text.count(old)
if count != 1:
    raise SystemExit(f'expected exactly one incomplete migration upload block, found {count}')
updated = text.replace(old, new, 1)
if updated.count('rsync -az --delete "$CANDIDATE_ROOT/tools/migrations/" "nvx-prod:$REMOTE_RELEASE/tools/migrations/"') != 1:
    raise SystemExit('canonical migration directory rsync contract missing or duplicated')
if updated.count('PRODUCTION_MIGRATION_PAYLOAD=PASS required=9') != 1:
    raise SystemExit('required migration payload marker missing or duplicated')
path.write_text(updated, encoding='utf-8')
print('PRODUCTION_PAYLOAD_PATCH=PASS required_migrations=9')
