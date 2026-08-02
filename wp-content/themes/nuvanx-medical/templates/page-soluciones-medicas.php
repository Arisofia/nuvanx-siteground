<?php
/**
 * Template Name: Soluciones médicas
 * Template Post Type: page
 *
 * Dedicated route template for /soluciones-medicas/.
 * Renders the GitHub-owned hub markup without the_content filters.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

// Fail closed with visible diagnostics if the canonical partial is missing.
$partial = get_template_directory() . '/template-parts/content/nvx-soluciones-medicas-github.php';

// Do not nest extra document buffers on this route: nested buffers have
// produced HTTP 200 + Content-Length 0 under staging2 PHP-FPM for this slug.
// header.php owns governance buffering; this template only captures the partial.

get_header();

echo "\n<!-- nvx-solutions-template-active -->\n";

if ( is_readable( $partial ) ) {
	// Capture partial without touching outer document buffers.
	$level = ob_get_level();
	ob_start();
	include_once $partial;
	$markup = (string) ob_get_clean();
	while ( ob_get_level() > $level ) {
		ob_end_clean();
	}
	if ( '' !== trim( $markup ) ) {
		// Partial builds HTML with escaped helpers; do not re-escape compound markup.
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed from escaped theme partial.
	} else {
		echo '<div class="nvx-shell"><h1 class="nvx-heading">Soluciones médicas</h1><p>Plantilla de soluciones vacía.</p></div>';
	}
} else {
	echo '<div class="nvx-shell"><h1 class="nvx-heading">Soluciones médicas</h1><p>Falta el partial versionado de soluciones.</p></div>';
}

get_footer();
