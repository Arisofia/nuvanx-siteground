# CSS audit enforcement note

The automated audit is repository-wide. Any stylesheet outside the canonical active stack must be either referenced by runtime code or removed. Typography is limited to the canonical serif and sans roles, icon colors must inherit or use semantic tokens, and spacing/color exceptions must be emitted with file and line evidence.
