# Canonical cleanup payload — retired

The `.cleanup-payload/*.patch` fragments and `validate-canonical-cleanup-patch.yml`
workflow were removed from `master` on 2026-07-18.

Reason: the assembled patch no longer applies to current theme CSS/PHP (it still
searched for resolved merge-conflict markers in `nvx-header.css` and other
superseded hunks). Cleanup work is already integrated into the live theme tree.

Do not reintroduce stale fragment validation against `master`.
