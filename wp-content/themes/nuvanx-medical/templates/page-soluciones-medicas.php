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

// Avoid nested output-buffer callbacks on this route: they have produced
// HTTP 200 + Content-Length 0 for this slug under staging2 PHP-FPM.
if ( function_exists( 'nvx_document_governance_start' ) ) {
	// header.php always starts governance; nothing to disable here beyond
	// ensuring we do not add additional buffers ourselves.
}

get_header();

echo "\n<!-- nvx-solutions-template-active -->\n";

if ( is_readable( $partial ) ) {
	// Capture partial without touching outer document buffers.
	$level = ob_get_level();
	ob_start();
	include $partial;
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
