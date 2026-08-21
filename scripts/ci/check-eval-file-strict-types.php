#!/usr/bin/env php
<?php
/**
 * Check for strict_types declarations in eval-file scripts.
 *
 * This helper prevents accidental reintroduction of declare(strict_types=1)
 * into scripts executed via wp eval-file, which causes fatal errors
 * because the declaration is no longer the first statement.
 *
 * Usage:
 *   php scripts/ci/check-eval-file-strict-types.php
 *   php scripts/ci/check-eval-file-strict-types.php tools/migrations/
 *
 * @package NVX\CI
 */

$targetDir = $argv[1] ?? 'tools/migrations';
$pattern = '/declare\s*\(\s*strict_types\s*=\s*1\s*\)/';
$exitCode = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        // Strip block comments (/* ... */) and line comments (// ...) before
        // matching so that declare() appearing only inside doc-blocks does not
        // trigger a false positive.
        $stripped = preg_replace('!/\*.*?\*/!s', '', $content);   // block comments
        $stripped = preg_replace('!//.*$!m', '', $stripped);       // line comments
        if (preg_match($pattern, $stripped)) {
            echo "ERROR: Found declare(strict_types=1) in {$file->getPathname()}\n";
            echo "       This will cause fatal errors when executed via wp eval-file.\n";
            $exitCode = 1;
        }
    }
}

exit($exitCode);
