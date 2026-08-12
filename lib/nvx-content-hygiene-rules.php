<?php
/**
 * Backward-compatible loader for the canonical content hygiene rules.
 *
 * New migration tooling must require the canonical file directly from
 * tools/migrations/lib/. This shim remains temporarily for any retained tooling
 * that still resolves the historical repository-root lib path.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

require_once __DIR__ . '/../tools/migrations/lib/nvx-content-hygiene-rules.php';
